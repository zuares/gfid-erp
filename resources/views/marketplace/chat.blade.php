@extends('layouts.app')
@section('title', 'Marketplace • Chat')

@push('head')
<style>
    .chat-wrap { display:flex; gap:0; height:calc(100vh - 140px); min-height:480px; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; background:#fff; }
    .chat-list { width:320px; min-width:260px; border-right:1px solid #e5e7eb; display:flex; flex-direction:column; }
    .chat-list-head { padding:10px 12px; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; gap:8px; }
    .chat-list-body { flex:1; overflow-y:auto; }
    .conv-item { padding:10px 12px; border-bottom:1px solid #f8fafc; cursor:pointer; display:flex; gap:10px; align-items:flex-start; }
    .conv-item:hover { background:#f8fafc; }
    .conv-item.active { background:#eef2ff; }
    .conv-avatar { width:36px; height:36px; border-radius:50%; background:#e2e8f0; display:flex; align-items:center; justify-content:center; font-weight:800; color:#64748b; flex-shrink:0; overflow:hidden; }
    .conv-avatar img { width:100%; height:100%; object-fit:cover; }
    .conv-name { font-weight:700; font-size:.82rem; color:#0f172a; }
    .conv-store { font-size:.65rem; color:#94a3b8; }
    .conv-preview { font-size:.72rem; color:#64748b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:190px; }
    .conv-time { font-size:.62rem; color:#94a3b8; margin-left:auto; text-align:right; flex-shrink:0; }
    .conv-unread { background:#ef4444; color:#fff; font-size:.62rem; font-weight:800; border-radius:99px; padding:1px 6px; display:inline-block; margin-top:3px; }
    .chat-pane { flex:1; display:flex; flex-direction:column; background:#f8fafc; }
    .chat-pane-head { padding:10px 14px; background:#fff; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; gap:10px; }
    .chat-msgs { flex:1; overflow-y:auto; padding:14px; display:flex; flex-direction:column; gap:8px; }
    .msg { max-width:70%; padding:8px 12px; border-radius:12px; font-size:.8rem; line-height:1.45; word-break:break-word; }
    .msg.buyer  { background:#fff; border:1px solid #e5e7eb; align-self:flex-start; border-bottom-left-radius:4px; }
    .msg.seller { background:#dbeafe; align-self:flex-end; border-bottom-right-radius:4px; }
    .msg .msg-time { font-size:.6rem; color:#94a3b8; margin-top:3px; text-align:right; }
    .msg img { max-width:220px; border-radius:8px; display:block; }
    .chat-input { padding:10px 12px; background:#fff; border-top:1px solid #e5e7eb; display:flex; gap:8px; }
    .chat-input textarea { flex:1; resize:none; border:1px solid #e2e8f0; border-radius:10px; padding:8px 12px; font-size:.8rem; height:42px; }
    .chat-empty { flex:1; display:flex; align-items:center; justify-content:center; color:#94a3b8; font-size:.85rem; flex-direction:column; gap:6px; }
    .rt-dot { width:8px; height:8px; border-radius:50%; display:inline-block; }
    .rt-on  { background:#22c55e; } .rt-off { background:#f59e0b; }
    @media (max-width: 768px) {
        .chat-wrap { flex-direction:column; height:auto; }
        .chat-list { width:100%; max-height:40vh; }
        .chat-pane { min-height:50vh; }
    }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-2">
    <h5 class="fw-black mb-0">💬 Chat Marketplace</h5>
    <div class="d-flex align-items-center gap-2" style="font-size:.7rem;color:#64748b">
        <span class="rt-dot rt-off" id="rtDot"></span><span id="rtLabel">menghubungkan…</span>
        <button class="btn btn-sm btn-outline-secondary" style="font-size:.7rem" onclick="loadConversations(true)">⟳ Sync</button>
    </div>
</div>

<div class="chat-wrap">
    <div class="chat-list">
        <div class="chat-list-head">
            <input type="text" class="form-control form-control-sm" placeholder="Cari pembeli…" id="convSearch" oninput="renderConversations()">
        </div>
        <div class="chat-list-body" id="convList">
            <div class="p-3 text-center text-muted" style="font-size:.75rem">Memuat…</div>
        </div>
    </div>
    <div class="chat-pane" id="chatPane">
        <div class="chat-empty" id="chatEmpty">
            <div style="font-size:2rem">💬</div>
            <div>Pilih percakapan di sebelah kiri</div>
        </div>
        <div class="chat-pane-head" id="chatHead" style="display:none">
            <div class="conv-avatar" id="chatAvatar">?</div>
            <div>
                <div class="conv-name" id="chatName">—</div>
                <div class="conv-store" id="chatStore">—</div>
            </div>
        </div>
        <div class="chat-msgs" id="chatMsgs" style="display:none"></div>
        <div class="chat-input" id="chatInput" style="display:none">
            <textarea id="msgText" placeholder="Tulis pesan… (Enter untuk kirim)" onkeydown="onMsgKey(event)"></textarea>
            <button class="btn btn-primary btn-sm px-3" onclick="sendMessage()" id="btnSend">Kirim</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const API = '/api/marketplace/chat';
    let conversations = [];
    let activeConv = null;
    let pendingCompose = null; // {storeId, orderSn, buyerUsername} → chat baru (cold-start)
    let echoConnected = false;
    let pollTimer = null;

    const $ = id => document.getElementById(id);
    const esc = s => (s ?? '').toString().replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    async function api(url, opts = {}) {
        const res = await fetch(url, { headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' }, ...opts });
        if (!res.ok) throw new Error((await res.json().catch(() => ({})))?.message || ('HTTP ' + res.status));
        return res.json();
    }

    function timeAgo(iso) {
        if (!iso) return '';
        const d = new Date(iso), now = new Date(), diff = (now - d) / 60000;
        if (diff < 1) return 'baru';
        if (diff < 60) return Math.floor(diff) + 'm';
        if (diff < 1440) return Math.floor(diff / 60) + 'j';
        return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
    }

    // ── Conversations ────────────────────────────────────────────────────────
    window.loadConversations = async function (sync = false) {
        try {
            conversations = await api(`${API}/conversations${sync ? '?sync=1' : ''}`);
            renderConversations();
        } catch (e) {
            $('convList').innerHTML = `<div class="p-3 text-center text-danger" style="font-size:.75rem">${esc(e.message)}</div>`;
        }
    };

    window.renderConversations = function () {
        const q = ($('convSearch').value || '').toLowerCase();
        const rows = conversations.filter(c => !q || (c.buyer_username || '').toLowerCase().includes(q));

        if (!rows.length) {
            $('convList').innerHTML = '<div class="p-3 text-center text-muted" style="font-size:.75rem">Belum ada percakapan.<br>Klik ⟳ Sync untuk menarik dari Shopee.</div>';
            return;
        }

        $('convList').innerHTML = rows.map(c => `
            <div class="conv-item ${activeConv?.id === c.id ? 'active' : ''}" onclick="openConversation(${c.id})">
                <div class="conv-avatar">${c.buyer_avatar ? `<img src="${esc(c.buyer_avatar)}">` : esc((c.buyer_username || '?')[0].toUpperCase())}</div>
                <div style="min-width:0">
                    <div class="conv-name">${esc(c.buyer_username || 'Pembeli')}</div>
                    <div class="conv-store">${esc(c.store?.name || '')}</div>
                    <div class="conv-preview">${esc(c.last_message_text || '')}</div>
                </div>
                <div class="conv-time">
                    ${timeAgo(c.last_message_at)}
                    ${c.unread_count > 0 ? `<br><span class="conv-unread">${c.unread_count}</span>` : ''}
                    ${(c.unread_count === 0 && c.is_answered === 0) ? `<br><span style="color:#ef4444;font-size:0.6rem;font-weight:700">Belum dibalas</span>` : ''}
                </div>
            </div>`).join('');
    };

    // ── Thread ───────────────────────────────────────────────────────────────
    window.openConversation = async function (id, syncFirst = true) {
        pendingCompose = null;
        activeConv = conversations.find(c => c.id === id) || activeConv;
        renderConversations();

        $('chatEmpty').style.display = 'none';
        $('chatHead').style.display = '';
        $('chatMsgs').style.display = '';
        $('chatInput').style.display = '';
        $('chatName').textContent = activeConv?.buyer_username || 'Pembeli';
        $('chatStore').textContent = activeConv?.store?.name || '';
        $('chatAvatar').innerHTML = activeConv?.buyer_avatar ? `<img src="${esc(activeConv.buyer_avatar)}">` : esc((activeConv?.buyer_username || '?')[0].toUpperCase());
        $('chatMsgs').innerHTML = '<div class="text-center text-muted" style="font-size:.75rem">Memuat…</div>';

        try {
            const data = await api(`${API}/conversations/${id}/messages${syncFirst ? '?sync=1' : ''}`);
            renderMessages(data.messages);
            // Tandai terbaca (non-blocking)
            api(`${API}/conversations/${id}/read`, { method: 'POST' }).then(() => {
                const c = conversations.find(x => x.id === id);
                if (c) { c.unread_count = 0; renderConversations(); }
            }).catch(() => {});
        } catch (e) {
            $('chatMsgs').innerHTML = `<div class="text-center text-danger" style="font-size:.75rem">${esc(e.message)}</div>`;
        }
    };

    function renderMessages(messages) {
        if (!messages.length) {
            $('chatMsgs').innerHTML = '<div class="text-center text-muted" style="font-size:.75rem">Belum ada pesan.</div>';
            return;
        }
        $('chatMsgs').innerHTML = messages.map(m => {
            let body = esc(m.text || '');
            if (m.message_type === 'image') {
                // Spec: content.url = URL penuh; thumb_url hanya hash (bukan URL)
                const url = (m.content?.url || '').startsWith('http') ? m.content.url : null;
                body = url ? `<img src="${esc(url)}" loading="lazy">` : '🖼 [gambar]';
            } else if (m.message_type === 'video') {
                body = '🎬 [video]';
            } else if (m.message_type === 'item') {
                body = '🛍 [produk]';
            } else if (!m.text && m.message_type !== 'text') {
                body = `[${esc(m.message_type)}]`;
            }
            const t = m.sent_at ? new Date(m.sent_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '';
            return `<div class="msg ${m.from_role === 'seller' ? 'seller' : 'buyer'}">${body}<div class="msg-time">${t}</div></div>`;
        }).join('');
        $('chatMsgs').scrollTop = $('chatMsgs').scrollHeight;
    }

    // ── Kirim pesan ──────────────────────────────────────────────────────────
    window.sendMessage = async function () {
        const text = $('msgText').value.trim();
        if (!text || (!activeConv && !pendingCompose)) return;
        $('btnSend').disabled = true;
        try {
            if (activeConv) {
                await api(`${API}/conversations/${activeConv.id}/send`, { method: 'POST', body: JSON.stringify({ text }) });
                $('msgText').value = '';
                await openConversation(activeConv.id, false);
            } else {
                // Cold-start: kirim pesan pertama via send_message (to_id = buyer),
                // percakapan baru otomatis terbentuk
                const res = await api(`${API}/start-from-order`, {
                    method: 'POST',
                    body: JSON.stringify({
                        store_id: pendingCompose.storeId,
                        order_sn: pendingCompose.orderSn,
                        text
                    })
                });
                $('msgText').value = '';
                pendingCompose = null;
                await loadConversations(false);
                if (res.conversation) {
                    openConversation(res.conversation.id, true);
                } else {
                    // Terkirim tapi conversation_id belum terbaca → sync penuh
                    await loadConversations(true);
                }
            }
        } catch (e) {
            alert('Gagal kirim: ' + e.message);
        } finally {
            $('btnSend').disabled = false;
        }
    };

    window.onMsgKey = function (ev) {
        if (ev.key === 'Enter' && !ev.shiftKey) { ev.preventDefault(); sendMessage(); }
    };

    // Panel percakapan BARU (belum ada di Shopee) — pesan pertama membentuk percakapan
    function openComposePane(storeId, orderSn, buyerUsername) {
        activeConv = null;
        pendingCompose = { storeId, orderSn, buyerUsername };
        renderConversations();

        $('chatEmpty').style.display = 'none';
        $('chatHead').style.display = '';
        $('chatMsgs').style.display = '';
        $('chatInput').style.display = '';
        $('chatName').textContent = buyerUsername || 'Pembeli';
        $('chatStore').textContent = `Order ${orderSn} • percakapan baru`;
        $('chatAvatar').innerHTML = esc((buyerUsername || '?')[0].toUpperCase());
        $('chatMsgs').innerHTML =
            '<div class="text-center text-muted" style="font-size:.75rem;padding:20px">' +
            'Belum ada percakapan dengan pembeli ini.<br>Kirim pesan pertama untuk memulai chat. 👇</div>';
    }

    // ── Realtime via Reverb ──────────────────────────────────────────────────
    function setRtState(on) {
        echoConnected = on;
        $('rtDot').className = 'rt-dot ' + (on ? 'rt-on' : 'rt-off');
        $('rtLabel').textContent = on ? 'realtime' : 'polling';
    }

    if (window.Echo) {
        try {
            window.Echo.channel('marketplace')
                .listen('ChatMessageReceived', (e) => {
                    loadConversations(false);
                    if (activeConv && e.conversation_id === activeConv.id) {
                        openConversation(activeConv.id, false);
                    }
                });
            const conn = window.Echo.connector?.pusher?.connection;
            if (conn) {
                conn.bind('connected',    () => setRtState(true));
                conn.bind('disconnected', () => setRtState(false));
                conn.bind('unavailable',  () => setRtState(false));
                setRtState(conn.state === 'connected');
            }
        } catch (e) { setRtState(false); }
    } else {
        setRtState(false);
    }

    // Fallback polling: 15 dtk saat websocket putus, 60 dtk saat konek (safety net)
    let lastPoll = Date.now();
    
    setInterval(async () => {
        const interval = echoConnected ? 60000 : 15000;
        if (Date.now() - lastPoll >= interval) {
            lastPoll = Date.now();
            
            // Panggil API dengan sync=true jika websocket putus agar selalu update dari Shopee
            await loadConversations(!echoConnected);
            
            if (activeConv) await openConversation(activeConv.id, !echoConnected);
        }
    }, 2500);

    // ── Deep-link dari halaman orders: ?store_id=&order_sn= ─────────────────
    async function handleDeepLink() {
        const p = new URLSearchParams(location.search);
        const storeId = p.get('store_id'), orderSn = p.get('order_sn');
        if (!storeId || !orderSn) return;
        try {
            const res = await api(`${API}/start-from-order`, {
                method: 'POST',
                body: JSON.stringify({ store_id: parseInt(storeId), order_sn: orderSn })
            });
            await loadConversations(false);
            if (res.conversation) {
                await openConversation(res.conversation.id);
            } else if (res.buyer_user_id) {
                // Belum ada percakapan → buka panel tulis pesan pertama (cold-start)
                openComposePane(parseInt(storeId), orderSn, res.buyer_username);
            } else {
                alert(`Data buyer untuk order ${orderSn} belum lengkap. ` +
                      `Sync ulang order ini dulu, atau hubungi via Seller Centre.`);
                return;
            }
            $('msgText').value = `Halo kak ${res.buyer_username || ''}, mengenai pesanan ${orderSn} `.trimEnd() + ' ';
            $('msgText').focus();
        } catch (e) { console.warn('Deep-link chat gagal:', e); }
    }

    // Init
    loadConversations(true).then(handleDeepLink);
})();
</script>
@endpush
