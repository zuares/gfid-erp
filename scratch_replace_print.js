    window.printDocument = async function (storeId, orderSn) {
        // Shopee API usually takes a few seconds to generate the document if it hasn't been generated yet.
        // We can just open the window directly to the endpoint.
        const url = `/api/marketplace/stores/${storeId}/orders/${orderSn}/document`;
        
        // Show a loading alert briefly
        const alertHtml = `<div id="printAlert" style="position:fixed;top:20px;right:20px;background:#3b82f6;color:white;padding:10px 20px;border-radius:8px;z-index:9999;box-shadow:0 4px 6px rgba(0,0,0,0.1)">⏳ Meminta dokumen resi dari Marketplace...</div>`;
        document.body.insertAdjacentHTML('beforeend', alertHtml);
        
        setTimeout(() => {
            const el = document.getElementById('printAlert');
            if (el) el.remove();
            window.open(url, '_blank');
        }, 1500);
    };
