    function products(rows) {
        const map = {};
        rows.filter(o => !['CANCELLED','BATAL','RETURNED'].includes(status(o))).forEach(o => {
            const storedItems = Array.isArray(o.items) ? o.items : [];
            storedItems.forEach(i => {
                const marketplaceSku = i.model_sku || i.item_sku || i.external_sku || '—';
                const internalSku = i.internal_sku || '';
                const category = i.internal_category || i.internal_category_name || 'Tanpa kategori';
                const key = skuKey(category);
                const qty = n(i.model_quantity_purchased || i.quantity_purchased || i.qty || i.active_qty);
                const cost = itemCost(i);
                const p = map[key] ||= {category,qty:0,rev:0,cost:0,unitHppWeighted:0,unitHppQty:0,missingQty:0,skuRevenue:{},itemKeys:{}};
                const lineRevenue = discountedLine(i);
                p.qty += qty;
                p.rev += lineRevenue;
                p.cost += cost;
                p.skuRevenue[skuKey(marketplaceSku)] = (p.skuRevenue[skuKey(marketplaceSku)] || 0) + lineRevenue;
                p.itemKeys[skuKey(internalSku || marketplaceSku)] = true;
                if (cost > 0 && qty > 0) { p.unitHppWeighted += itemUnitHpp(i) * qty; p.unitHppQty += qty; }
                else p.missingQty += qty || 1;
            });
        });
        const list = Object.values(map);
        const totalRevenue = list.reduce((sum, p) => sum + p.rev, 0);
        const revenueBySku = list.reduce((carry, p) => {
            Object.entries(p.skuRevenue).forEach(([key, value]) => { carry[key] = (carry[key] || 0) + value; });
            return carry;
        }, {});
        const directAdSpend = Object.entries(adSpendBySku).reduce((sum, [key, value]) => {
            return sum + (revenueBySku[key] > 0 ? n(value) : 0);
        }, 0);
        const adScale = directAdSpend > 0 ? Math.min(1, n(window.__analyticsAdCost || 0) / directAdSpend) : 0;
        const remainingAdSpend = Math.max(0, n(window.__analyticsAdCost || 0) - directAdSpend * adScale);

        return list.map(p => {
            const grossProfit = p.rev - estimatedFees(p.rev).total;
            const directSkuSpend = Object.entries(p.skuRevenue).reduce((sum, [key]) => sum + n(adSpendBySku[key]) * adScale, 0);
            const allocatedAdSpend = directSkuSpend + (totalRevenue > 0 ? remainingAdSpend * (p.rev / totalRevenue) : 0);
            return {
                ...p,
                itemCount: Object.keys(p.itemKeys).length,
                unitHpp: p.unitHppQty > 0 ? p.unitHppWeighted / p.unitHppQty : 0,
                missing: p.missingQty > 0,
                grossProfit,
                adCost: allocatedAdSpend,
                profit: grossProfit - p.cost - allocatedAdSpend,
            };
        });
    }
    function renderProducts(rows) {
        const list=products(rows), sales=[...list].sort((a,b)=>b.rev-a.rev || b.qty-a.qty), worst=[...list].sort((a,b)=>a.profit-b.profit).slice(0,6); const max=Math.max(...sales.map(p=>Math.abs(p.profit)),1);
        $('bestProductBody').innerHTML=sales.length?sales.map((p,i)=>`<tr><td class="an-product-value">${i+1}</td><td><span class="an-product"><span class="an-product-copy"><span class="an-product-name">${esc(p.category)}</span><span class="an-product-sku">${p.itemCount} item</span></span></span></td><td>${p.qty}</td><td class="an-product-value">${money(p.rev)}<span class="an-table-subline">AOV ${money(p.qty ? p.rev / p.qty : 0)}</span></td><td class="an-product-value">${money(p.cost)}<span class="an-table-subline">${p.unitHpp > 0 ? money(p.unitHpp) + '/unit' : '—'}</span></td><td class="an-product-value" style="color:#b45309">${money(p.adCost)}</td><td class="an-product-value" style="color:#15803d">${money(p.grossProfit)}<span class="an-table-subline">${pct(p.grossProfit,p.rev)}</span></td><td class="an-product-value" style="color:${p.profit>=0?'#15803d':'#dc2626'}">${money(p.profit)}</td></tr>`).join(''):'<tr><td colspan="8"><div class="an-empty">Belum ada penjualan produk.</div></td></tr>';
        $('worstProductBody').innerHTML=worst.length?worst.map(p=>`<div class="an-list-row"><div class="an-list-main"><div class="an-list-name"><span class="an-dot ${p.profit<0?'red':''}"></span> ${esc(p.category)}</div><div class="an-list-meta">${p.itemCount} item · ${p.qty} pcs${p.missing?' · HPP belum lengkap':''}</div><div class="an-bar"><span style="width:${Math.max(4,Math.min(100,Math.round(Math.abs(p.profit)/max*100)))}%;background:${p.profit<0?'#ef4444':'#facc15'}"></span></div></div><div class="an-list-value" style="color:${p.profit<0?'#dc2626':'#a16207'}">${money(p.profit)}</div></div>`).join(''):'<div class="an-empty">Belum ada produk untuk ditinjau.</div>';
    }
    function renderProductInsights(rows) {
        const list = rows || [];
        const negative = list.filter(product => Number(product.operating_profit || 0) < 0).length;
        const totalGross = list.reduce((sum, product) => sum + Number(product.gross_sales || 0), 0);
        const totalPayout = list.reduce((sum, product) => sum + Number(product.payout || 0), 0);
        const totalProfit = list.reduce((sum, product) => sum + Number(product.operating_profit || 0), 0);
        $('anProductPulse').innerHTML = [
            ['Produk teratas', Number(list.length).toLocaleString('id-ID'), 'financial-ready · max 100 gross sales'],
            ['Payout bersih', money(totalPayout), 'setelah fee settlement'],
            ['Produk rugi', Number(negative).toLocaleString('id-ID'), negative ? 'perlu review segera' : 'tidak ada'],
            ['Profit operasional', money(totalProfit), `${totalGross > 0 ? (totalProfit / totalGross * 100).toFixed(1) : '0.0'}% vs gross sales`],
        ].map(([label,value,note]) => `<div class="an-pulse"><div class="an-pulse-label">${label}</div><div class="an-pulse-value">${esc(value)}</div><div class="an-pulse-note ${negative && label === 'Produk rugi' ? 'bad' : ''}">${note}</div></div>`).join('');
        const focus = [];
        const best = [...list].sort((a,b) => Number(b.operating_profit || 0) - Number(a.operating_profit || 0))[0];
        const worst = [...list].sort((a,b) => Number(a.operating_profit || 0) - Number(b.operating_profit || 0))[0];
        if (best) focus.push(['','bi-trophy','Profit operasional tertinggi',`${esc(best.product_name)} · ${money(best.operating_profit)}`,'Pertahankan']);
        if (worst && Number(worst.operating_profit || 0) < 0) focus.push(['bad','bi-exclamation-triangle','Produk dengan profit negatif',`${esc(worst.product_name)} · ${money(worst.operating_profit)}`,'Review harga/HPP/iklan']);
        if (!focus.length) focus.push(['','bi-check2-circle','Portofolio sehat','Belum ada produk dengan profit negatif','—']);
        $('anProductFocus').innerHTML = focus.map(([level,icon,title,note,action]) => `<div class="an-alert ${level}"><span class="an-alert-icon"><i class="bi ${icon}"></i></span><div><div class="an-alert-title">${title}</div><div class="an-alert-note">${note}</div></div><span class="an-alert-action">${action}</span></div>`).join('');
    }
    function renderProductSummary(rows) {
        const query = String($('anProductSearch')?.value || '').trim().toLowerCase();
        const sortKey = $('anProductSort')?.value || 'gross_sales';
        const filteredRows = (rows || []).filter(product => !query || `${product.product_name} ${product.sku}`.toLowerCase().includes(query));
        const sales = [...filteredRows].sort((a,b) => Number(b[sortKey] || 0) - Number(a[sortKey] || 0));
        const worst = [...sales].sort((a,b) => a.operating_profit - b.operating_profit).slice(0, 6);
        const max = Math.max(...sales.map(p => Math.abs(Number(p.operating_profit || 0))), 1);
        renderProductInsights(rows || []);
        $('bestProductBody').innerHTML = sales.length ? sales.map((p,i) => `<tr><td class="an-product-value">${i+1}</td><td><span class="an-product"><span class="an-product-copy"><a class="an-product-link" href="${productUrl(p)}" title="Buka produk terkait"> <span class="an-product-name">${esc(p.product_name)}</span></a><span class="an-product-sku">Kode: ${esc(p.sku || '—')}</span></span></span></td><td>${Number(p.qty || 0).toLocaleString('id-ID')}</td><td class="an-product-value">${money(p.gross_sales)}<span class="an-table-subline">basis gross sales eligible</span></td><td class="an-product-value">${money(p.payout)}<span class="an-table-subline">setelah fee marketplace</span></td><td class="an-product-value">${money(p.hpp)}</td><td class="an-product-value" style="color:#b45309">${money(p.ad_cost)}<span class="an-table-subline">alokasi proporsional</span></td><td class="an-product-value" style="color:${p.gross_profit>=0?'#15803d':'#dc2626'}">${money(p.gross_profit)}<span class="an-table-subline">${Number(p.gross_sales || 0) > 0 ? (Number(p.gross_profit || 0) / Number(p.gross_sales) * 100).toFixed(1) : '0.0'}% gross sales</span></td><td class="an-product-value" style="color:${p.operating_profit>=0?'#15803d':'#dc2626'}">${money(p.operating_profit)}<span class="an-table-subline">${Number(p.margin_pct || 0).toFixed(1)}% margin</span></td></tr>`).join('') : '<tr><td colspan="9"><div class="an-empty">Belum ada produk siap profit.</div></td></tr>';
        $('worstProductBody').innerHTML = worst.length ? worst.map(p => `<div class="an-list-row"><div class="an-list-main"><div class="an-list-name"><span class="an-dot ${p.operating_profit<0?'red':''}"></span> <a class="an-product-link" href="${productUrl(p)}" title="Buka produk terkait">${esc(p.product_name)}</a></div><div class="an-list-meta">Kode: ${esc(p.sku || '—')} · ${Number(p.qty || 0).toLocaleString('id-ID')} pcs</div><div class="an-bar"><span style="width:${Math.max(4, Math.min(100, Math.round(Math.abs(Number(p.operating_profit || 0)) / max * 100)))}%;background:${p.operating_profit<0?'#ef4444':'#facc15'}"></span></div></div><div class="an-list-value" style="color:${p.operating_profit<0?'#dc2626':'#a16207'}">${money(p.operating_profit)}</div></div>`).join('') : '<div class="an-empty">Belum ada produk untuk ditinjau.</div>';
    }
