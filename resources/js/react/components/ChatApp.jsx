import React, { useState, useEffect, useRef } from 'react';

const API = '/api/marketplace/chat';

export default function ChatApp() {
    const [conversations, setConversations] = useState([]);
    const [activeConv, setActiveConv] = useState(null);
    const [messages, setMessages] = useState([]);
    const [search, setSearch] = useState('');
    const [inputText, setInputText] = useState('');
    const [isLoadingMsgs, setIsLoadingMsgs] = useState(false);
    const [isEchoConnected, setIsEchoConnected] = useState(false);
    
    const msgsRef = useRef(null);
    const inputRef = useRef(null);

    // Fetch initial conversations
    useEffect(() => {
        loadConversations();
        
        // Polling as fallback
        const interval = setInterval(() => {
            loadConversations(false);
            if (activeConv) {
                // Background sync messages
                api(`${API}/conversations/${activeConv.id}/messages`)
                    .then(data => setMessages(data.messages))
                    .catch(() => {});
            }
        }, 15000);
        
        return () => clearInterval(interval);
    }, [activeConv]);

    // Setup Echo Listener
    useEffect(() => {
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
                    conn.bind('connected', () => setIsEchoConnected(true));
                    conn.bind('disconnected', () => setIsEchoConnected(false));
                    conn.bind('unavailable', () => setIsEchoConnected(false));
                    setIsEchoConnected(conn.state === 'connected');
                }
            } catch (e) { setIsEchoConnected(false); }
        }
    }, [activeConv]);

    async function api(url, opts = {}) {
        const res = await fetch(url, { headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' }, ...opts });
        if (!res.ok) throw new Error((await res.json().catch(() => ({})))?.message || ('HTTP ' + res.status));
        return res.json();
    }

    async function loadConversations(sync = false) {
        try {
            const data = await api(`${API}/conversations${sync ? '?sync=1' : ''}`);
            setConversations(data);
        } catch (e) {
            console.error('Failed to load convos', e);
        }
    }

    async function openConversation(id, syncFirst = true) {
        const conv = conversations.find(c => c.id === id);
        setActiveConv(conv);
        window.activeConversationId = id;
        setIsLoadingMsgs(true);
        setMessages([]);
        
        document.querySelector('.chat-wrap')?.classList.add('show-chat');

        try {
            const data = await api(`${API}/conversations/${id}/messages${syncFirst ? '?sync=1' : ''}`);
            setMessages(data.messages);
            
            setTimeout(() => {
                inputRef.current?.focus();
                scrollToBottom();
            }, 100);

            api(`${API}/conversations/${id}/read`, { method: 'POST' }).then(() => {
                setConversations(prev => prev.map(c => c.id === id ? { ...c, unread_count: 0 } : c));
                if (window.refreshChatBadge) window.refreshChatBadge(false);
            }).catch(() => {});
        } catch (e) {
            console.error('Failed to load messages', e);
        } finally {
            setIsLoadingMsgs(false);
        }
    }

    function closeConversation() {
        document.querySelector('.chat-wrap')?.classList.remove('show-chat');
        setActiveConv(null);
        window.activeConversationId = null;
    }

    async function sendMessage() {
        const text = inputText.trim();
        if (!text || !activeConv) return;

        const tempId = 'temp-' + Date.now();
        const newMsg = {
            id: tempId,
            message_type: 'text',
            text: text,
            from_role: 'seller',
            sent_at: new Date().toISOString()
        };

        setMessages(prev => [...prev, newMsg]);
        setInputText('');
        inputRef.current.style.height = '44px';
        setTimeout(scrollToBottom, 50);

        try {
            await api(`${API}/send`, {
                method: 'POST',
                body: JSON.stringify({ conversation_id: activeConv.id, text })
            });
            loadConversations(false);
        } catch (e) {
            alert('Gagal mengirim pesan: ' + e.message);
            setMessages(prev => prev.filter(m => m.id !== tempId));
        }
    }

    function handleKeyDown(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    }

    function scrollToBottom() {
        if (msgsRef.current) {
            msgsRef.current.scrollTop = msgsRef.current.scrollHeight;
        }
    }

    function timeAgo(iso) {
        if (!iso) return '';
        const d = new Date(iso), now = new Date(), diff = (now - d) / 60000;
        if (diff < 1) return 'baru';
        if (diff < 60) return Math.floor(diff) + 'm';
        if (diff < 1440) return Math.floor(diff / 60) + 'j';
        return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
    }

    const filteredConvs = conversations.filter(c => !search || (c.buyer_username || '').toLowerCase().includes(search.toLowerCase()));

    return (
        <div className="chat-wrap">
            <div className="chat-list">
                <div className="chat-list-head" style={{ gap: '10px' }}>
                    <input 
                        type="text" 
                        className="form-control form-control-sm" 
                        placeholder="Cari pembeli…" 
                        value={search}
                        onChange={e => setSearch(e.target.value)}
                        style={{ flex: 1 }}
                    />
                    <button 
                        className="btn btn-sm btn-outline-secondary" 
                        onClick={() => loadConversations(true)} 
                        title="Tarik Chat Shopee Terbaru"
                        style={{ borderRadius: '99px', padding: '0 12px' }}
                    >
                        ⟳
                    </button>
                    <div className={`rt-dot ${isEchoConnected ? 'rt-on' : 'rt-off'}`} title={isEchoConnected ? 'Realtime Aktif' : 'Polling'}></div>
                </div>
                
                <div className="chat-list-body">
                    {filteredConvs.length === 0 ? (
                        <div className="p-3 text-center text-muted" style={{ fontSize: '.75rem' }}>Belum ada percakapan.</div>
                    ) : (
                        filteredConvs.map(c => (
                            <div 
                                key={c.id} 
                                className={`conv-item ${activeConv?.id === c.id ? 'active' : ''}`} 
                                onClick={() => openConversation(c.id)}
                            >
                                <div className="conv-avatar">
                                    {c.buyer_avatar ? <img src={c.buyer_avatar} alt="avatar" /> : (c.buyer_username || '?')[0].toUpperCase()}
                                </div>
                                <div style={{ minWidth: 0 }}>
                                    <div className="conv-name">{c.buyer_username || 'Pembeli'}</div>
                                    <div className="conv-store">{c.store?.name || ''}</div>
                                    <div className="conv-preview">{c.last_message_text || ''}</div>
                                </div>
                                <div className="conv-time">
                                    {timeAgo(c.last_message_at)}
                                    {c.unread_count > 0 && <><br/><span className="conv-unread">{c.unread_count}</span></>}
                                    {c.unread_count === 0 && c.is_answered === 0 && <><br/><span style={{ color: '#ef4444', fontSize: '0.6rem', fontWeight: 700 }}>Belum dibalas</span></>}
                                </div>
                            </div>
                        ))
                    )}
                </div>
            </div>

            <div className="chat-pane">
                {!activeConv ? (
                    <div className="chat-empty">
                        <div className="chat-empty-icon">💬</div>
                        <div>Pilih percakapan untuk mulai mengobrol</div>
                        <div style={{ marginTop: '10px', padding: '5px 15px', background: '#3b82f6', color: '#fff', borderRadius: '20px', fontSize: '0.8rem', fontWeight: 'bold'}}>✨ Powered by React JS</div>
                    </div>
                ) : (
                    <>
                        <div className="chat-pane-head">
                            <button className="btn-back-list" onClick={closeConversation} title="Kembali">←</button>
                            <div className="conv-avatar">
                                {activeConv.buyer_avatar ? <img src={activeConv.buyer_avatar} alt="avatar" /> : (activeConv.buyer_username || '?')[0].toUpperCase()}
                            </div>
                            <div style={{ minWidth: 0 }}>
                                <div className="conv-name" style={{ whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                                    {activeConv.buyer_username || 'Pembeli'}
                                </div>
                                <div className="conv-store" style={{ whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                                    {activeConv.store?.name || ''}
                                </div>
                            </div>
                        </div>

                        <div className="chat-msgs" ref={msgsRef}>
                            {isLoadingMsgs ? (
                                <div className="text-center text-muted" style={{ fontSize: '.75rem' }}>Memuat pesan…</div>
                            ) : messages.length === 0 ? (
                                <div className="text-center text-muted" style={{ fontSize: '.75rem' }}>Belum ada pesan.</div>
                            ) : (
                                messages.map((m, i) => {
                                    let body = m.text || '';
                                    if (m.message_type === 'image') {
                                        const url = m.content?.url?.startsWith('http') ? m.content.url : null;
                                        body = url ? <img src={url} loading="lazy" alt="img" /> : '🖼 [gambar]';
                                    } else if (m.message_type === 'video') body = '🎬 [video]';
                                    else if (m.message_type === 'item') body = '🛍 [produk]';
                                    else if (m.message_type === 'order') body = '📦 [pesanan]';
                                    else if (m.message_type === 'sticker') body = '✨ [stiker]';
                                    else if (['bundle_message', 'faq_liveagent'].includes(m.message_type)) {
                                        body = <><span style={{opacity: 0.8}}>[FAQ]</span> {m.text}</>;
                                    } else if (!m.text && m.message_type !== 'text') {
                                        body = `[${m.message_type.replace(/_/g, ' ')}]`;
                                    }

                                    const isSeller = m.from_role === 'seller';
                                    const roleClass = isSeller ? 'seller' : 'buyer';
                                    const tick = isSeller ? <span className="msg-tick">✓✓</span> : null;
                                    const t = m.sent_at ? new Date(m.sent_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '';

                                    return (
                                        <div key={m.id || i} className={`msg ${roleClass}`}>
                                            {body}
                                            <div className="msg-time">{t}{tick}</div>
                                        </div>
                                    );
                                })
                            )}
                        </div>

                        <div className="chat-input">
                            <textarea 
                                ref={inputRef}
                                placeholder="Tulis pesan… (Enter untuk kirim)" 
                                value={inputText}
                                onChange={e => {
                                    setInputText(e.target.value);
                                    e.target.style.height = '44px';
                                    e.target.style.height = Math.min(e.target.scrollHeight, 120) + 'px';
                                }}
                                onKeyDown={handleKeyDown}
                            />
                            <button className="btn btn-primary btn-sm px-3" onClick={sendMessage}>Kirim</button>
                        </div>
                    </>
                )}
            </div>
        </div>
    );
}
