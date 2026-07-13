@extends('layouts.app')
@section('title', 'Marketplace • Chat')

@push('head')
<style>
    /* CSS Variables for Premium Theme */
    :root {
        --chat-bg: #f8fafc;
        --chat-surface: #ffffff;
        --chat-border: rgba(226, 232, 240, 0.8);
        --chat-primary: #3b82f6;
        --chat-primary-hover: #2563eb;
        --chat-bubble-seller: linear-gradient(135deg, #3b82f6, #2563eb);
        --chat-bubble-buyer: #ffffff;
        --chat-text-dark: #0f172a;
        --chat-text-muted: #64748b;
        --chat-glass: rgba(255, 255, 255, 0.85);
    }
    
    /* Scrollbar Customization */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

    /* Layout & Containers */
    .chat-wrap { 
        display:flex; gap:0; height:calc(100vh - 140px); min-height:540px; 
        border: 1px solid var(--chat-border); border-radius: 16px; 
        overflow:hidden; background: var(--chat-surface);
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05), 0 8px 10px -6px rgba(0,0,0,0.01);
    }
    .chat-list { width:320px; min-width:280px; border-right: 1px solid var(--chat-border); display:flex; flex-direction:column; background: #fafbfc; }
    .chat-list-head { padding:14px 16px; border-bottom:1px solid var(--chat-border); background: var(--chat-glass); backdrop-filter: blur(8px); display:flex; align-items:center; z-index: 10; }
    .chat-list-head input { border-radius: 99px; padding-left: 16px; border: 1px solid #e2e8f0; background: #f1f5f9; transition: all 0.2s; }
    .chat-list-head input:focus { background: #fff; box-shadow: 0 0 0 3px rgba(59,130,246,0.15); border-color: var(--chat-primary); }
    .chat-list-body { flex:1; overflow-y:auto; scroll-behavior: smooth; }

    /* Conversation Items */
    .conv-item { padding:12px 16px; border-bottom:1px solid #f1f5f9; cursor:pointer; display:flex; gap:12px; align-items:flex-start; transition: all 0.2s ease; position: relative; }
    .conv-item:hover { background: #f8fafc; }
    .conv-item.active { background: #eff6ff; }
    .conv-item.active::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: var(--chat-primary); border-top-right-radius: 4px; border-bottom-right-radius: 4px; }
    .conv-avatar { width:42px; height:42px; border-radius:50%; background: linear-gradient(135deg, #e2e8f0, #cbd5e1); display:flex; align-items:center; justify-content:center; font-weight:700; color:#475569; flex-shrink:0; overflow:hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    .conv-avatar img { width:100%; height:100%; object-fit:cover; }
    
    /* Typography inside Conversation Item */
    .conv-name { font-weight:700; font-size:.875rem; color:var(--chat-text-dark); letter-spacing: -0.01em; }
    .conv-store { font-size:.65rem; color:var(--chat-text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 2px; }
    .conv-preview { font-size:.75rem; color:var(--chat-text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:190px; margin-top: 4px; }
    .conv-time { font-size:.65rem; color:#94a3b8; margin-left:auto; text-align:right; flex-shrink:0; font-weight: 500; }
    .conv-unread { background:#ef4444; color:#fff; font-size:.65rem; font-weight:800; border-radius:99px; padding:2px 8px; display:inline-block; margin-top:6px; box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3); }
    
    /* Main Chat Area */
    .chat-pane { flex:1; display:flex; flex-direction:column; background: var(--chat-bg); position: relative; }
    .chat-pane::before { content:''; position:absolute; top:0; left:0; right:0; bottom:0; opacity:0.02; pointer-events:none; background-image: radial-gradient(#cbd5e1 1px, transparent 1px); background-size: 20px 20px; }
    .chat-pane-head { padding:14px 20px; background: var(--chat-glass); backdrop-filter: blur(12px); border-bottom:1px solid var(--chat-border); display:flex; align-items:center; gap:12px; z-index: 10; }
    
    /* Chat Messages */
    .chat-msgs { flex:1; overflow-y:auto; padding:20px; display:flex; flex-direction:column; gap:12px; z-index: 5; }
    .msg { max-width:75%; padding:10px 14px; font-size:.875rem; line-height:1.5; word-break:break-word; position: relative; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    .msg.buyer  { background: var(--chat-bubble-buyer); border: 1px solid #e2e8f0; align-self:flex-start; border-radius: 16px 16px 16px 4px; color: var(--chat-text-dark); }
    .msg.seller { background: var(--chat-bubble-seller); align-self:flex-end; border-radius: 16px 16px 4px 16px; color: #fff; }
    .msg .msg-time { font-size:.65rem; margin-top:4px; text-align:right; opacity: 0.7; }
    .msg.seller .msg-time { color: rgba(255,255,255,0.8); }
    .msg.buyer .msg-time { color: var(--chat-text-muted); }
    .msg img { max-width:240px; border-radius:8px; display:block; margin-top: 4px; }
    
    /* Input Area */
    .chat-input { padding:16px 20px; background: var(--chat-glass); backdrop-filter: blur(12px); border-top:1px solid var(--chat-border); display:flex; gap:12px; align-items:flex-end; z-index: 10; }
    .chat-input textarea { flex:1; resize:none; border:2px solid #94a3b8; border-radius:12px; padding:10px 14px; font-size:.875rem; height:46px; transition: all 0.2s; background: #fff; color: #0f172a; font-weight: 500; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02); }
    .chat-input textarea:focus { outline: none; border-color: var(--chat-primary); box-shadow: 0 0 0 3px rgba(59,130,246,0.15), inset 0 2px 4px rgba(0,0,0,0.02); height: 80px; }
    .chat-input textarea::placeholder { color: #94a3b8; font-weight: 400; }
    .chat-input button { border-radius: 10px; font-weight: 600; padding: 0 20px; height: 46px; background: var(--chat-primary); border: none; transition: all 0.2s; color: #fff; }
    .chat-input button:hover { background: var(--chat-primary-hover); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(59,130,246,0.3); }
    .chat-input button:active { transform: translateY(0); }
    
    /* Empty State */
    .chat-empty { flex:1; display:flex; align-items:center; justify-content:center; color:var(--chat-text-muted); font-size:.9rem; flex-direction:column; gap:12px; z-index: 5; }
    .chat-empty-icon { font-size: 3rem; opacity: 0.5; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1)); }
    
    /* Realtime Indicator */
    .rt-dot { width:8px; height:8px; border-radius:50%; display:inline-block; box-shadow: 0 0 0 2px #fff; }
    .rt-on  { background:#10b981; box-shadow: 0 0 0 2px #fff, 0 0 8px rgba(16, 185, 129, 0.6); } 
    .rt-off { background:#f59e0b; }
    
    @media (max-width: 768px) {
        .chat-wrap { flex-direction:column; height:calc(100vh - 100px); border-radius: 0; border-left: none; border-right: none; }
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
            <div class="chat-empty-icon">💬</div>
            <div>Pilih percakapan di sebelah kiri untuk mulai mengobrol</div>
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
            
            // Menggunakan sync=false karena Cron Job background sudah bertugas sinkronisasi.
            // Ini akan membuat UI tidak pernah nge-lag / freeze saat interval berjalan.
            await loadConversations(false);
            
            if (activeConv) await openConversation(activeConv.id, false);
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
