            let fulfillBtn = '';
            let logisticsBtn = '';

            // Logistics Buttons
            if (o.order_status === 'READY_TO_SHIP') {
                logisticsBtn = `<button class="btn btn-sm btn-outline-primary mt-1" style="font-size:0.7rem;padding:0.15rem 0.5rem" onclick="openArrangeShipment(${o.store_id}, '${o.channel_order_id}')">🚚 Atur Pengiriman</button>`;
            } else if (o.order_status === 'PROCESSED' || o.order_status === 'SHIPPED') {
                logisticsBtn = `<button class="btn btn-sm btn-outline-secondary mt-1" style="font-size:0.7rem;padding:0.15rem 0.5rem" onclick="printDocument(${o.store_id}, '${o.channel_order_id}')">🖨 Cetak Resi</button>`;
            }
