import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { JSDOM } from 'jsdom';

const blade = readFileSync(new URL('../../resources/views/marketplace/kilat.blade.php', import.meta.url), 'utf8');
const script = blade.match(/<script>([\s\S]*?)<\/script>/)[1];
const flush = () => new Promise(resolve => setImmediate(resolve));

function setup(t) {
    const dom = new JSDOM(blade, { runScripts: 'outside-only', url: 'https://example.test/marketplace/kilat' });
    t.after(() => dom.window.close());
    const requests = [];
    dom.window.setInterval = () => 0;
    dom.window.fetch = (url, options) => new Promise((resolve, reject) => requests.push({
        params: new URL(url, 'https://example.test').searchParams,
        signal: options.signal,
        reply: data => resolve({ ok: true, json: async () => data }),
        fail: () => reject(new Error('Network error')),
    }));
    dom.window.eval(script);
    return { window: dom.window, document: dom.window.document, requests };
}

function response(sn, page = 1, total = 1007) {
    return {
        data: [{ booking_sn: sn, store_id: 1, booking_status: 'PROCESSED', tracking_number: 'SPXID06902531824A', items: [] }],
        counts: { all: 1007, ready: 107, waiting: 900, shipped: 0, warehouse: 0, cancelled: 0 },
        ready_counts: { all: 107, to_arrange: 7, packing: 100, ready_ship: 0 },
        pagination: { current_page: page, last_page: Math.ceil(total / 50), total, from: (page - 1) * 50 + 1, to: Math.min(page * 50, total) },
    };
}

test('initial seller tab uses server counts and server-side pagination', async t => {
    const { document, requests } = setup(t);
    assert.equal(requests[0].params.get('tab'), 'ready');
    assert.equal(requests[0].params.get('page'), '1');
    requests[0].reply(response('261011AAR6JZMLGXZRI'));
    await flush();
    assert.match(document.getElementById('kiltBody').textContent, /261011AAR6JZMLGXZRI/);
    assert.equal(document.getElementById('badge-all').textContent, '1007');
    assert.equal(document.getElementById('badge-sub-ready-packing').textContent, '100');
    assert.equal(document.getElementById('pagePrev').disabled, true);
    document.getElementById('pageNext').click();
    assert.equal(requests[1].params.get('page'), '2');
    requests[1].reply(response('NEXT-PAGE', 2));
    await flush();
    assert.match(document.getElementById('paginationInfo').textContent, /51–100 dari 1007/);
    assert.equal(document.getElementById('pagePrev').disabled, false);
});

test('tab, subtab and store changes reach the server and reset pagination', async t => {
    const { window, document, requests } = setup(t);
    requests[0].reply(response('INITIAL', 3));
    await flush();
    window.switchSubTabReady('packing');
    assert.equal(requests[1].params.get('sub'), 'packing');
    assert.equal(requests[1].params.get('page'), '1');
    requests[1].reply(response('PACKING'));
    await flush();
    window.switchTab('all');
    assert.equal(requests[2].params.get('tab'), 'all');
    requests[2].reply(response('ALL'));
    await flush();
    const stores = document.getElementById('storeFilter');
    stores.add(new window.Option('Insight Corps', '42'));
    stores.value = '42';
    stores.dispatchEvent(new window.Event('change'));
    assert.equal(requests[3].params.get('store_id'), '42');
    assert.equal(requests[3].params.get('page'), '1');
});

test('stale responses cannot replace the newest filter results', async t => {
    const { window, document, requests } = setup(t);
    window.switchTab('warehouse');
    assert.equal(requests[0].signal.aborted, true);
    requests[1].reply(response('NEWEST'));
    await flush();
    requests[0].reply(response('STALE'));
    await flush();
    assert.match(document.getElementById('kiltBody').textContent, /NEWEST/);
    assert.doesNotMatch(document.getElementById('kiltBody').textContent, /STALE/);
    assert.equal(document.getElementById('btnRefresh').disabled, false);
});

test('AWB search and clear search reset page while retaining seller tab', async t => {
    const { window, document, requests } = setup(t);
    requests[0].reply(response('INITIAL', 2));
    await flush();
    // Run the search debounce immediately; no browser/network access in this test.
    window.setTimeout = callback => { callback(); return 0; };
    const search = document.getElementById('searchInput');
    search.value = 'SPXID06902531824A';
    search.dispatchEvent(new window.Event('input'));
    assert.equal(requests[1].params.get('search'), 'SPXID06902531824A');
    assert.equal(requests[1].params.get('page'), '1');
    assert.equal(requests[1].params.get('tab'), 'ready');
    requests[1].reply(response('261011AAR6JZMLGXZRI', 1, 1));
    await flush();
    assert.equal(document.getElementById('pageNext').disabled, true);
    document.getElementById('searchClearBtn').click();
    assert.equal(requests[2].params.has('search'), false);
    assert.equal(requests[2].params.get('page'), '1');
});

test('background refresh keeps visible rows, including on temporary network failure', async t => {
    const { window, document, requests } = setup(t);
    requests[0].reply(response('261011AAR6JZMLGXZRI'));
    await flush();
    window.eval('load({background: true})');
    assert.match(document.getElementById('kiltBody').textContent, /261011AAR6JZMLGXZRI/);
    requests[1].fail();
    await flush();
    assert.match(document.getElementById('kiltBody').textContent, /261011AAR6JZMLGXZRI/);
    assert.match(document.getElementById('paginationInfo').textContent, /Pembaruan gagal/);
    assert.equal(document.getElementById('btnRefresh').disabled, false);
});
