{{--
  Storefront Behaviour Tracker
  ----------------------------
  Events yang dikirim ke POST /storefront/track:
    • page_view_duration  — berapa detik user di halaman ini
    • click               — klik pada elemen penting (product card, CTA, WA, nav)
  Event dikirim via navigator.sendBeacon (fire-and-forget, tidak blokir navigasi).
--}}
<script>
(function () {
    var TOKEN  = '{{ $_sfToken ?? "" }}';
    var CSRF   = '{{ csrf_token() }}';
    var TRACK  = '{{ route("storefront.track") }}';
    var PAGE   = '{{ $pageRoute ?? (request()->route()?->getName() ?? "unknown") }}';
    var URL    = window.location.href;
    var start  = Date.now();

    if (!TOKEN) return; // middleware belum set token, skip

    /* ── Kirim event ke server ──────────────────────────────────── */
    function send(eventType, payload) {
        var data = new FormData();
        data.append('_token',     CSRF);
        data.append('visitor_token', TOKEN);
        data.append('event_type', eventType);
        data.append('payload',    JSON.stringify(payload));

        if (navigator.sendBeacon) {
            navigator.sendBeacon(TRACK, data);
        } else {
            // Fallback: sync XHR saat beacon tidak tersedia
            var x = new XMLHttpRequest();
            x.open('POST', TRACK, false);
            x.setRequestHeader('X-CSRF-TOKEN', CSRF);
            x.send(data);
        }
    }

    /* ── page_view_duration — kirim saat user tinggalkan halaman ── */
    function sendDuration() {
        var seconds = Math.round((Date.now() - start) / 1000);
        if (seconds < 1) return;
        send('page_view_duration', {
            url:     URL,
            route:   PAGE,
            seconds: seconds
        });
    }

    // visibilitychange: tab di-hide/switch (paling reliable di mobile)
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') sendDuration();
    });

    // pagehide: user navigasi keluar (lebih reliable dari beforeunload di Safari)
    window.addEventListener('pagehide', sendDuration);

    /* ── Click tracking ─────────────────────────────────────────── */
    // Selector → label yang dikirim ke server
    var TRACKED_SELECTORS = [
        { sel: 'a[href*="/products/"]',              label: 'product_link'   },
        { sel: '[data-track="add-to-cart"]',          label: 'add_to_cart_cta'},
        { sel: 'a[href*="wa.me"], a[href*="api.whatsapp"]', label: 'wa_button' },
        { sel: 'a[href*="/checkout"]',               label: 'checkout_link'  },
        { sel: 'button[type="submit"]',               label: 'submit_button'  },
        { sel: '.product-card, [data-track="product-card"]', label: 'product_card' },
        { sel: 'a[href="/cart"], a[href*="/cart"]',   label: 'cart_link'      },
        { sel: 'a[href="/"], a[href*="/products"]',   label: 'nav_link'       },
    ];

    document.addEventListener('click', function (e) {
        var el = e.target;
        // Naik ke parent hingga max 4 level untuk cari elemen yang match
        for (var i = 0; i < 4; i++) {
            if (!el || el === document.body) break;
            for (var j = 0; j < TRACKED_SELECTORS.length; j++) {
                try {
                    if (el.matches(TRACKED_SELECTORS[j].sel)) {
                        send('click', {
                            label:   TRACKED_SELECTORS[j].label,
                            text:    (el.innerText || el.textContent || '').trim().slice(0, 60),
                            href:    el.href || el.getAttribute('href') || '',
                            route:   PAGE,
                            url:     URL
                        });
                        return; // hanya track 1 event per klik
                    }
                } catch(ex) {}
            }
            el = el.parentElement;
        }
    }, { passive: true });

})();
</script>
