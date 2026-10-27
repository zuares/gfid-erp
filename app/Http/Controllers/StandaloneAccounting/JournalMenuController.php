<?php

namespace App\Http\Controllers\StandaloneAccounting;

use App\Http\Controllers\Controller;

class JournalMenuController extends Controller
{
    private const MODULES = [
        'pembelian' => [
            'title' => 'Jurnal Pembelian',
            'icon' => 'bi-bag-plus',
            'description' => 'Mencatat pembelian barang, bahan, atau jasa secara tunai maupun kredit.',
            'notes' => ['Supplier dan nomor faktur menjadi referensi utama.', 'Utang supplier menggunakan akun 2-110 bila pembelian dilakukan secara kredit.'],
        ],
        'penjualan' => [
            'title' => 'Jurnal Penjualan',
            'icon' => 'bi-receipt',
            'description' => 'Mencatat penjualan tunai atau kredit beserta diskon dan retur penjualan.',
            'notes' => ['Penjualan menggunakan akun 4-110.', 'Diskon dan retur menggunakan akun kontra-pendapatan 4-120 dan 4-130.'],
        ],
        'penerimaan-kas' => [
            'title' => 'Penerimaan Kas',
            'icon' => 'bi-cash-coin',
            'description' => 'Mencatat penerimaan uang melalui bank atau kas kecil.',
            'notes' => ['Pastikan sumber penerimaan dan bukti transaksi dicatat.', 'Kas dan bank tidak boleh dicampur bila perlu rekonsiliasi terpisah.'],
        ],
        'pengeluaran-kas' => [
            'title' => 'Pengeluaran Kas',
            'icon' => 'bi-wallet2',
            'description' => 'Mencatat pembayaran supplier, beban, pajak, gaji, dan pengeluaran usaha lainnya.',
            'notes' => ['Pilih akun beban atau aset sesuai tujuan pembayaran.', 'Pembayaran utang mengurangi akun utang, bukan langsung menjadi beban.'],
        ],
        'kas-kecil' => [
            'title' => 'Kas Kecil',
            'icon' => 'bi-coin',
            'description' => 'Mengelola pembentukan, pengisian kembali, dan penggunaan kas kecil.',
            'notes' => ['Gunakan akun 1-120 untuk kas kecil.', 'Setiap pengisian kembali harus didukung bukti pengeluaran.'],
        ],
        'kartu-persediaan' => [
            'title' => 'Inventory Card / Kartu Persediaan',
            'icon' => 'bi-box-seam',
            'description' => 'Melihat pergerakan masuk, keluar, dan saldo persediaan berdasarkan barang.',
            'notes' => ['Kartu persediaan harus terhubung dengan penerimaan dan pengeluaran barang.', 'Untuk produksi, bahan baku, barang dalam proses, dan barang jadi dapat dipisahkan bertahap.'],
        ],
        'buku-besar-pembantu' => [
            'title' => 'Buku Besar Pembantu',
            'icon' => 'bi-journal-bookmark',
            'description' => 'Menelusuri rincian piutang pelanggan dan utang supplier sampai ke transaksi sumber.',
            'notes' => ['Piutang harus dapat ditelusuri per pelanggan.', 'Utang harus dapat ditelusuri per supplier dan faktur.'],
        ],
    ];

    public function index()
    {
        return view('standalone_accounting.journal_menu.index', [
            'modules' => self::MODULES,
        ]);
    }

    public function show(string $module)
    {
        abort_unless(isset(self::MODULES[$module]), 404);

        return view('standalone_accounting.journal_menu.show', [
            'moduleKey' => $module,
            'module' => self::MODULES[$module],
        ]);
    }
}
