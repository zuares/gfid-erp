    window.executePrintBulk = async function(mode) {
        let printGreeting = true;
        const chk = document.getElementById('chkPrintGreeting');
        if (chk) printGreeting = chk.checked;
        
        const modal = document.getElementById('printOptsModal');
        if (modal) modal.remove();

        let rows = getPackingRows();
        if (mode === 'unprinted_only') {
            rows = rows.filter(o => {
                let printCount = o.print_count || 0;
                if (printCount === 0 && printedOrderIds.has(o.id)) printCount = 1;
                return printCount === 0 && !printedDocOrderSns.has(o.channel_order_id);
            });
        } else if (mode === 'selected') {
            // Check if any selected already printed
            const alreadyPrinted = rows.filter(o => (o.print_count || 0) > 0 || printedOrderIds.has(o.id));
            if (alreadyPrinted.length > 0) {
                if (!confirm(`Ada ${alreadyPrinted.length} order yang terpilih sudah pernah dicetak resinya. Tetap lanjutkan?`)) return;
            }
        }

        if (!rows.length) { alert('Tidak ada order untuk dicetak.'); return; }
        
        const payloadOrders = rows.map((o, idx) => ({
            store_id: o.store_id,
            channel_order_id: o.channel_order_id,
            position: idx
        }));

        const alertHtml = `<div id="printBulkAlert" style="position:fixed;top:20px;right:20px;background:#f59e0b;color:white;padding:15px 25px;border-radius:8px;z-index:99999;box-shadow:0 4px 6px rgba(0,0,0,0.1);font-family:sans-serif;font-weight:bold;">⏳ Meminta dokumen resi dari Server... Mohon tunggu...</div>`;
        document.body.insertAdjacentHTML('beforeend', alertHtml);
        
        try {
            const res = await fetch('/documents/bulk-print', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    orders: payloadOrders,
                    mode: mode
                })
            });
            
            const data = await res.json();
            
            const el = document.getElementById('printBulkAlert');
            if (el) el.remove();

            if (!res.ok) {
                if (data.success_count === 0 && data.failed_orders && data.failed_orders.length > 0) {
                    showFailedPrintModal(data);
                } else {
                    alert('Gagal: ' + (data.error || 'Terjadi kesalahan'));
                }
                return;
            }
            
            showFailedPrintModal(data);
            
            setTimeout(async () => {
                try {
                    const newOrders = await api('/api/marketplace/local-orders');
                    orders = newOrders;
                    fulfillmentStatusMap.clear();
                    orders.forEach(o => {
                        if (o.fulfillment_status) {
                            fulfillmentStatusMap.set(o.id, { id: o.fulfillment_id, status: o.fulfillment_status });
                            if (o.fulfillment_status === 'confirmed') fulfilledOrderIds.add(o.id);
                        }
                    });
                    render();
                } catch(e) {}
            }, 3000);
            
        } catch (err) {
            const el = document.getElementById('printBulkAlert');
            if (el) el.remove();
            alert('Terjadi kesalahan jaringan atau server timeout.');
        }
    };
    
    window.showFailedPrintModal = function(data) {
        let failedListHtml = '';
        if (data.failed_orders && data.failed_orders.length > 0) {
            failedListHtml = `
            <div style="margin-top:16px;max-height:200px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:8px;">
                <table style="width:100%;font-size:0.8rem;border-collapse:collapse;">
                    <thead style="background:#f1f5f9;position:sticky;top:0;">
                        <tr><th style="text-align:left;padding:8px;">Store</th><th style="text-align:left;padding:8px;">Order SN</th><th style="text-align:left;padding:8px;">Alasan</th></tr>
                    </thead>
                    <tbody>
                        ${data.failed_orders.map(f => `<tr>
                            <td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;">${f.store_name}</td>
                            <td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;font-family:monospace;">${f.channel_order_id}</td>
                            <td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;color:#ef4444;">${f.reason}</td>
                        </tr>`).join('')}
                    </tbody>
                </table>
            </div>`;
        }

        const modalHtml = `
            <div id="printResultModal" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:99999;backdrop-filter:blur(2px);">
                <div style="background:white;padding:28px;border-radius:16px;width:500px;max-width:90%;box-shadow:0 20px 25px -5px rgba(0,0,0,0.1);font-family:sans-serif;">
                    <h3 style="margin:0 0 16px 0;font-size:1.2rem;color:#0f172a;font-weight:700;">Hasil Bulk Print</h3>
                    <div style="display:flex;gap:16px;margin-bottom:16px;">
                        <div style="flex:1;background:#f0fdf4;padding:12px;border-radius:8px;border:1px solid #bbf7d0;text-align:center;">
                            <div style="font-size:0.8rem;color:#166534;font-weight:bold;text-transform:uppercase;">Berhasil</div>
                            <div style="font-size:2rem;color:#15803d;font-weight:bold;">${data.success_count || 0}</div>
                        </div>
                        <div style="flex:1;background:#fef2f2;padding:12px;border-radius:8px;border:1px solid #fecaca;text-align:center;">
                            <div style="font-size:0.8rem;color:#991b1b;font-weight:bold;text-transform:uppercase;">Gagal</div>
                            <div style="font-size:2rem;color:#b91c1c;font-weight:bold;">${data.failed_count || 0}</div>
                        </div>
                    </div>
                    ${failedListHtml}
                    <div style="display:flex;gap:12px;margin-top:24px;">
                        ${data.success_count > 0 ? `
                        <button onclick="window.open('${data.download_url}', '_blank'); document.getElementById('printResultModal').remove();" style="flex:1;padding:12px;border-radius:8px;font-weight:600;font-size:0.9rem;border:none;background:#2563eb;color:white;cursor:pointer;box-shadow:0 2px 4px rgba(37,99,235,0.2);">
                            ⬇️ Unduh PDF Berhasil
                        </button>` : ''}
                        <button onclick="document.getElementById('printResultModal').remove()" style="flex:1;padding:12px;border-radius:8px;font-weight:600;font-size:0.9rem;border:1px solid #cbd5e1;background:white;color:#334155;cursor:pointer;">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    };
