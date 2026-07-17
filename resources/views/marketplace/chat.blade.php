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
        display:flex; gap:0; height:calc(100vh - 180px); min-height:400px; 
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
    .chat-msgs { flex:1; overflow-y:auto; padding:20px; display:flex; flex-direction:column; gap:4px; z-index: 5; scroll-behavior: smooth; position: relative; }
    .msg { max-width:75%; padding:8px 12px; font-size:.875rem; line-height:1.4; word-break:break-word; position: relative; box-shadow: 0 1px 2px rgba(0,0,0,0.05); border-radius:12px; margin-bottom:2px; }
    .msg.buyer  { background: var(--chat-bubble-buyer); border: 1px solid #e2e8f0; align-self:flex-start; color: var(--chat-text-dark); }
    .msg.seller { background: var(--chat-bubble-seller); align-self:flex-end; border: none; color: #fff; }
    
    .msg.msg-first { margin-top: 8px; }
    .msg.msg-last { margin-bottom: 8px; }
    .msg.buyer.msg-last { border-bottom-left-radius: 2px; }
    .msg.seller.msg-last { border-bottom-right-radius: 2px; }
    
    .msg .msg-time { font-size:.62rem; margin-top:2px; display:flex; align-items:center; gap:4px; opacity: 0.7; }
    .msg.seller .msg-time { color: rgba(255,255,255,0.85); justify-content:flex-end; }
    .msg.buyer .msg-time { color: var(--chat-text-muted); justify-content:flex-end; }
    .msg-tick { font-size:.6rem; letter-spacing: -2px; margin-right: 2px; }
    
    .msg img { max-width:240px; border-radius:8px; display:block; margin-top: 4px; }
    
    /* Input Area */
    .chat-input { padding:14px 20px; background: var(--chat-glass); backdrop-filter: blur(12px); border-top:1px solid var(--chat-border); display:flex; gap:12px; align-items:flex-end; z-index: 10; }
    .chat-input textarea { flex:1; resize:none; border:2px solid #94a3b8; border-radius:16px; padding:10px 14px; font-size:.875rem; min-height:44px; max-height:120px; transition: border-color 0.2s, box-shadow 0.2s; background: #fff; color: #0f172a; font-weight: 500; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02); line-height: 1.4; overflow-y: auto; }
    .chat-input textarea:focus { outline: none; border-color: var(--chat-primary); box-shadow: 0 0 0 3px rgba(59,130,246,0.15), inset 0 2px 4px rgba(0,0,0,0.02); }
    .chat-input textarea::placeholder { color: #94a3b8; font-weight: 400; line-height: 1.5; }
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
    
    /* Scroll Down Button */
    .btn-scroll-down { position: absolute; bottom: 85px; right: 20px; width: 38px; height: 38px; background: #fff; border-radius: 50%; border: 1px solid #e2e8f0; box-shadow: 0 4px 10px rgba(0,0,0,0.1); display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--chat-primary); font-size: 1.2rem; z-index: 15; opacity: 0; pointer-events: none; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); transform: translateY(15px); }
    .btn-scroll-down.visible { opacity: 1; pointer-events: auto; transform: translateY(0); }
    .btn-scroll-down:hover { background: #f8fafc; }
    
    /* Skeleton Loader */
    .skel-item { padding: 12px 16px; display: flex; gap: 12px; border-bottom: 1px solid #f1f5f9; }
    .skel-av { width: 42px; height: 42px; border-radius: 50%; background: #e2e8f0; flex-shrink:0; position: relative; overflow: hidden; }
    .skel-body { flex: 1; display: flex; flex-direction: column; gap: 8px; justify-content: center; position: relative; overflow: hidden; }
    .skel-line { height: 10px; background: #e2e8f0; border-radius: 4px; }
    .skel-av::after, .skel-line::after { content: ""; position: absolute; top: 0; left: -100%; width: 50%; height: 100%; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent); animation: skeleton-sweep 1.2s infinite; }
    @keyframes skeleton-sweep { 100% { left: 200%; } }
    
    .btn-back-list { display:none; align-items:center; justify-content:center; width:36px; height:36px; border:none; background:transparent; font-size:1.2rem; cursor:pointer; color:var(--chat-text-muted); border-radius:50%; margin-right:4px; transition:background 0.2s; }
    .btn-back-list:hover { background: #f1f5f9; }

    @media (max-width: 768px) {
        /* Mencegah body scroll di mobile agar chat terasa seperti aplikasi native */
        body { overflow: hidden !important; }
        
        .chat-wrap { 
            position: fixed;
            top: 115px; /* Jarak dari header & judul */
            bottom: calc(100px + env(safe-area-inset-bottom)) !important; /* Aman dari nav-bottom dan tombol tengahnya */
            left: 0; right: 0;
            height: auto !important; 
            border-radius: 0; border: none; border-top: 1px solid var(--chat-border);
            z-index: 30;
            margin: 0 !important;
            flex-direction: column;
        }
        .chat-list { width:100%; height:100%; max-height:none; border-right: none; }
        .chat-pane { display:none; width:100%; height:100%; min-height:0; }
        
        .chat-wrap.show-chat .chat-list { display:none; }
        .chat-wrap.show-chat .chat-pane { display:flex; }
        
        .btn-back-list { display:flex; }
    }
</style>
@endpush

@section('content')
<div id="react-chat-root"></div>
@endsection

@push('scripts')
@viteReactRefresh
@vite('resources/js/react/chat.jsx')
@endpush
