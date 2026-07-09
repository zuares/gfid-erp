{{-- resources/views/purchasing/purchase_orders/print_dot_matrix.blade.php --}}
@extends('layouts.print')

@section('title', 'Cetak PO (Dot Matrix) - ' . $order->code)

@push('head')
<style>
    body { background: #e5e7eb; margin: 0; padding-top: 48px; }

    .no-print {
        position: fixed;
        top: 0; left: 0; right: 0;
        z-index: 999;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: .5rem 1rem;
        background: #fff;
        border-bottom: 1px solid #e5e7eb;
        box-sizing: border-box;
    }

    .sheet {
        width: min-content;
        background: #fff;
        margin: 20px auto;
        padding: 1rem;
        box-sizing: border-box;
        box-shadow: 0 2px 8px rgba(0,0,0,.15);
        border-radius: 8px;
    }

    .setting-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }

    #qzPreviewText {
        width: calc(45ch + 1rem);
        font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
        font-size: 13px;
        white-space: pre;
        resize: none;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        padding: 0.5rem;
        background: #fff;
        outline: none;
        box-sizing: border-box;
        overflow-x: hidden;
    }
    
    #qzPreviewText:focus {
        border-color: #94a3b8;
    }

    /* Print — hidden when printing with normal browser print */
    @media print {
        body { background: #fff; padding-top: 0 !important; }
        .no-print { display: none !important; }
        .page-wrap {
            max-width: 420px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        .sheet {
            box-shadow: none;
            margin: 0;
            padding: 0;
            border-radius: 0;
        }
        .setting-box { display: none !important; }
        #qzPreviewText { border: none; padding: 0; }
    }
</style>
@endpush

@section('content')
<div class="no-print">
    <div>
        <strong style="font-size:.9rem;">Cetak PO (Dot Matrix)</strong>
        <span class="text-muted ms-2" style="font-size:.75rem;">{{ $order->code }}</span>
    </div>
    <div style="display:flex; gap:.5rem;">
        <a href="{{ route('purchasing.purchase_orders.show', $order->id) }}" class="btn btn-sm btn-outline-secondary">← Kembali</a>
        <button id="btnDoPrintDotMatrix" onclick="printDotMatrix()" class="btn btn-sm btn-dark">
            <i class="bi bi-printer"></i> Cetak (QZ Tray)
        </button>
    </div>
</div>

<div class="sheet">
    <div class="setting-box">
        <label class="form-label" style="font-size: 0.8rem; font-weight: 600;">Pilih Printer (Otomatis Deteksi)</label>
        <select id="qzPrinterName" class="form-select form-select-sm">
            <option value="">Memuat daftar printer...</option>
        </select>
    </div>

    <div>
        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: #475569;">Preview Teks</label>
        <textarea id="qzPreviewText" rows="22" spellcheck="false">Memuat layout...</textarea>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/rsvp@3.1.0/dist/rsvp.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/crypto-js@3.1.2/rollups/sha256.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qz-tray@2.2.4/qz-tray.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', async function() {
        // Fetch raw text
        fetch("{{ route('purchasing.purchase_orders.print_raw', $order->id) }}")
            .then(response => {
                if (!response.ok) throw new Error('Gagal mengambil data dari server');
                return response.json();
            })
            .then(data => {
                document.getElementById('qzPreviewText').value = data.raw_text;
            })
            .catch(err => {
                document.getElementById('qzPreviewText').value = 'Gagal memuat preview: ' + err.message;
            });

        // Initialize QZ and load printers
        try {
            if (!qz.websocket.isActive()) {
                await qz.websocket.connect({ retries: 2, delay: 1 });
            }
            
            const printers = await qz.printers.find();
            const select = document.getElementById('qzPrinterName');
            select.innerHTML = '';
            
            let savedPrinter = localStorage.getItem('qz_dotmatrix_printer');
            let defaultSelected = false;
            
            printers.forEach(p => {
                let option = document.createElement('option');
                option.value = p;
                option.text = p;
                
                // Prioritaskan printer yang tersimpan di localStorage
                if (savedPrinter && p === savedPrinter) {
                    option.selected = true;
                    defaultSelected = true;
                } 
                // Jika tidak ada yang tersimpan, cari nama yang mengandung LX atau DOT MATRIX
                else if (!defaultSelected && (p.toUpperCase().includes('LX') || p.toUpperCase().includes('DOT MATRIX'))) {
                    option.selected = true;
                    defaultSelected = true;
                }
                
                select.appendChild(option);
            });
            
            if (printers.length === 0) {
                select.innerHTML = '<option value="">Tidak ada printer terdeteksi</option>';
            }
            
        } catch (err) {
            console.error(err);
            const select = document.getElementById('qzPrinterName');
            select.innerHTML = '<option value="">Gagal mendeteksi printer (QZ Tray tidak aktif)</option>';
        }
    });

    async function printDotMatrix() {
        const printerName = document.getElementById('qzPrinterName').value;
        if (!printerName) {
            alert('Nama printer belum diisi!');
            return;
        }
        
        const rawText = document.getElementById('qzPreviewText').value;
        if (!rawText || rawText.includes('Memuat layout...')) {
            alert('Preview teks kosong atau sedang dimuat!');
            return;
        }
        
        localStorage.setItem('qz_dotmatrix_printer', printerName);

        const btn = document.getElementById('btnDoPrintDotMatrix');
        const originalText = btn.innerHTML;
        btn.innerHTML = 'Mencetak...';
        btn.disabled = true;

        try {
            // Already connected on page load, but just in case:
            if (!qz.websocket.isActive()) {
                await qz.websocket.connect({ retries: 2, delay: 1 });
            }

            const config = qz.configs.create(printerName);
            const printData = [
                '\x1B\x40', // Init printer
                rawText
            ];

            await qz.print(config, printData);
            alert('Berhasil mengirim perintah cetak ke printer!');
        } catch (err) {
            console.error(err);
            alert('Gagal mencetak: ' + (err.message || err));
            if (err.message && err.message.includes('websocket')) {
                alert('Pastikan aplikasi QZ Tray sudah terbuka dan berjalan di komputer ini.');
            }
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }
</script>
@endsection
