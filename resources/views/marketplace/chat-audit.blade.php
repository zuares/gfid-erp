@extends('layouts.app')
@section('title', 'Marketplace • Chat Audit')

@push('head')
<style>
    .audit-shell {
        background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
        border: 1px solid rgba(226, 232, 240, 0.9);
        border-radius: 18px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
    }
    .audit-toolbar {
        background: rgba(255,255,255,0.9);
        backdrop-filter: blur(8px);
        border-bottom: 1px solid rgba(226, 232, 240, 0.8);
        border-top-left-radius: 18px;
        border-top-right-radius: 18px;
    }
    .audit-table th {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #64748b;
        border-bottom: 1px solid #e2e8f0 !important;
        white-space: nowrap;
    }
    .audit-table td {
        vertical-align: top;
        font-size: .85rem;
    }
    .audit-preview {
        max-width: 360px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .audit-json {
        max-height: 360px;
        overflow: auto;
        background: #0f172a;
        color: #e2e8f0;
        border-radius: 12px;
        padding: 14px;
        font-size: .78rem;
        line-height: 1.5;
    }
    .audit-meta-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }
    .audit-meta-card {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 10px 12px;
        background: #fff;
    }
    .audit-meta-label {
        display: block;
        font-size: .68rem;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #64748b;
        margin-bottom: 4px;
    }
    .audit-meta-value {
        font-size: .85rem;
        color: #0f172a;
        word-break: break-word;
    }
</style>
@endpush

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <div class="text-uppercase text-muted" style="font-size:.72rem;letter-spacing:.08em;">Marketplace</div>
        <h5 class="fw-black mb-0">Audit raw payload chat</h5>
        <div class="text-muted mt-1" style="font-size:.85rem">Lihat raw payload webhook, raw context, dan link ke webhook log per message.</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('marketplace.chat') }}" class="btn btn-outline-secondary btn-sm">Kembali ke chat</a>
        <a href="{{ route('marketplace.chat.audit') }}" class="btn btn-outline-primary btn-sm">Refresh</a>
    </div>
</div>

<div class="card audit-shell mb-3">
    <div class="card-body audit-toolbar">
        <form method="GET" action="{{ route('marketplace.chat.audit') }}" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted mb-1">Toko</label>
                <select name="store_id" class="form-select form-select-sm">
                    <option value="">Semua toko</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}" @selected((int) $storeId === (int) $store->id)>{{ $store->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted mb-1">Conversation ID</label>
                <input type="text" name="conversation_id" value="{{ $conversationId }}" class="form-control form-control-sm" placeholder="conv-123">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label small text-muted mb-1">Cari</label>
                <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="message id, text, source, from id">
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label small text-muted mb-1">Arah</label>
                <select name="direction" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <option value="buyer" @selected(($direction ?? '') === 'buyer')>Buyer</option>
                    <option value="seller" @selected(($direction ?? '') === 'seller')>Seller</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label small text-muted mb-1">Source</label>
                <select name="source" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <option value="webhook" @selected(($source ?? '') === 'webhook')>webhook</option>
                    <option value="sync_api" @selected(($source ?? '') === 'sync_api')>sync_api</option>
                    <option value="send_api" @selected(($source ?? '') === 'send_api')>send_api</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label small text-muted mb-1">Webhook log ID</label>
                <input type="number" min="1" name="webhook_log_id" value="{{ $webhookLogId ?? '' }}" class="form-control form-control-sm" placeholder="123">
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button class="btn btn-primary btn-sm flex-fill" type="submit">Filter</button>
                <a href="{{ route('marketplace.chat.audit') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>

    <div class="card-body pt-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle audit-table mb-0">
                <thead>
                    <tr>
                        <th style="min-width:150px">Waktu</th>
                        <th style="min-width:150px">Toko</th>
                        <th style="min-width:180px">Percakapan</th>
                        <th style="min-width:110px">Arah</th>
                        <th style="min-width:110px">Source</th>
                        <th style="min-width:220px">Pesan</th>
                        <th style="min-width:140px">Webhook log</th>
                        <th style="min-width:90px"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($messages as $message)
                        <tr id="chat-message-row-{{ $message->id }}" class="{{ (int) ($focusMessageId ?? 0) === (int) $message->id ? 'table-warning' : '' }}">
                            <td>
                                <div class="fw-semibold">{{ optional($message->sent_at)->format('Y-m-d H:i') ?? '-' }}</div>
                                <div class="text-muted small">{{ $message->external_message_id }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $message->store?->name ?? '-' }}</div>
                                <div class="text-muted small">Store #{{ $message->store_id }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $message->conversation?->buyer_username ?? $message->conversation?->conversation_id ?? $message->external_conversation_id ?? '-' }}</div>
                                <div class="text-muted small">{{ $message->external_conversation_id ?? '-' }}</div>
                            </td>
                            <td>
                                <span class="badge {{ $message->from_role === 'seller' ? 'bg-primary' : 'bg-secondary' }}">
                                    {{ $message->from_role ?? '-' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $message->source ?? '-' }}</span>
                            </td>
                            <td>
                                <div class="audit-preview">{{ $message->text ?: data_get($message->content, 'text') ?: '[' . ($message->message_type ?? 'message') . ']' }}</div>
                            </td>
                            <td>
                                @if($message->webhook_log_id)
                                    <div class="fw-semibold">
                                        <a href="{{ route('marketplace.toko', ['log_id' => $message->webhook_log_id]) }}" target="_blank" rel="noopener" class="text-decoration-none">
                                            #{{ $message->webhook_log_id }}
                                        </a>
                                    </div>
                                    <div class="text-muted small">{{ $message->webhookLog?->event_type ?? '-' }}</div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary me-1" onclick="copyAuditRowLink({{ $message->id }}, this)">
                                    Copy link
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="openAuditMessage({{ $message->id }})">
                                    Lihat raw
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                Belum ada pesan yang cocok dengan filter ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($messages->hasPages())
        <div class="card-footer bg-white">
            {{ $messages->links() }}
        </div>
    @endif
</div>

<div class="modal fade" id="auditMessageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0">Detail raw payload</h5>
                    <div class="text-muted small" id="auditModalSubtitle">Memuat...</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-dark" onclick="copyAuditSection('payload')">Copy raw payload</button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
            </div>
            <div class="modal-body">
                <div class="audit-meta-grid mb-3">
                    <div class="audit-meta-card">
                        <span class="audit-meta-label">Message</span>
                        <div class="audit-meta-value" id="auditMetaMessage">-</div>
                    </div>
                    <div class="audit-meta-card">
                        <span class="audit-meta-label">Webhook log</span>
                        <div class="audit-meta-value" id="auditMetaWebhookLog">-</div>
                    </div>
                    <div class="audit-meta-card">
                        <span class="audit-meta-label">Conversation</span>
                        <div class="audit-meta-value" id="auditMetaConversation">-</div>
                    </div>
                    <div class="audit-meta-card">
                        <span class="audit-meta-label">Store</span>
                        <div class="audit-meta-value" id="auditMetaStore">-</div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-lg-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="fw-semibold">Raw payload</div>
                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="copyAuditSection('payload')">Copy</button>
                        </div>
                        <pre class="audit-json mb-0" id="auditRawPayload">{}</pre>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="fw-semibold">Raw context</div>
                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="copyAuditSection('context')">Copy</button>
                        </div>
                        <pre class="audit-json mb-0" id="auditRawContext">{}</pre>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="fw-semibold">Webhook log payload</div>
                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="copyAuditSection('webhook')">Copy</button>
                        </div>
                        <pre class="audit-json mb-0" id="auditWebhookPayload">{}</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const modalEl = document.getElementById('auditMessageModal');
    const modal = new bootstrap.Modal(modalEl);
    let currentAuditMessage = null;
    let currentAuditRawPayload = null;
    let currentAuditRawContext = null;
    let currentAuditWebhookPayload = null;
    const focusedMessageId = new URLSearchParams(window.location.search).get('message_id');

    function prettyPrint(value) {
        try {
            return JSON.stringify(value ?? {}, null, 2);
        } catch (e) {
            return String(value ?? '');
        }
    }

    async function copyText(text) {
        if (navigator.clipboard?.writeText) {
            await navigator.clipboard.writeText(text);
            return;
        }

        const ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', 'readonly');
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
    }

    function flashCopiedButton(button, copiedLabel = 'Tersalin') {
        if (!button) return;

        const prev = button.textContent;
        button.textContent = copiedLabel;
        button.disabled = true;
        setTimeout(() => {
            button.textContent = prev;
            button.disabled = false;
        }, 1200);
    }

    function flashCopied(selector, copiedLabel = 'Tersalin') {
        const btn = document.querySelector(selector);
        flashCopiedButton(btn, copiedLabel);
    }

    window.openAuditMessage = async function (messageId) {
        document.getElementById('auditModalSubtitle').textContent = 'Memuat message #' + messageId + '...';
        document.getElementById('auditMetaMessage').textContent = '-';
        document.getElementById('auditMetaWebhookLog').textContent = '-';
        document.getElementById('auditMetaConversation').textContent = '-';
        document.getElementById('auditMetaStore').textContent = '-';
        document.getElementById('auditRawPayload').textContent = '{}';
        document.getElementById('auditRawContext').textContent = '{}';
        document.getElementById('auditWebhookPayload').textContent = '{}';
        currentAuditMessage = null;
        currentAuditRawPayload = null;
        currentAuditRawContext = null;
        currentAuditWebhookPayload = null;

        modal.show();

        try {
            const res = await fetch(`/api/marketplace/chat/messages/${messageId}/raw`, {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) {
                throw new Error('HTTP ' + res.status);
            }

            const data = await res.json();
            const message = data.message || {};
            const store = data.store || {};
            const conversation = data.conversation || {};
            const webhookLog = data.webhook_log || {};
            currentAuditMessage = message;
            currentAuditRawPayload = message.raw_payload || {};
            currentAuditRawContext = message.raw_context || {};
            currentAuditWebhookPayload = webhookLog.payload || {};

            document.getElementById('auditModalSubtitle').textContent = [
                message.external_message_id || ('message #' + messageId),
                message.source ? ('source: ' + message.source) : null,
                message.webhook_log_id ? ('webhook log #' + message.webhook_log_id) : null
            ].filter(Boolean).join(' • ') + (message.audit_state && message.audit_state !== 'stored' ? ` • ${message.audit_state}` : '');

            document.getElementById('auditMetaMessage').textContent = [
                message.external_message_id || '-',
                message.message_type || '-',
                message.from_role || '-'
            ].join(' | ');
            document.getElementById('auditMetaWebhookLog').textContent = webhookLog.id ? ('#' + webhookLog.id + ' ' + (webhookLog.event_type || '')) : '-';
            document.getElementById('auditMetaConversation').textContent = conversation.conversation_id || message.external_conversation_id || '-';
            document.getElementById('auditMetaStore').textContent = store.name || message.store?.name || '-';

            document.getElementById('auditRawPayload').textContent = prettyPrint(currentAuditRawPayload);
            document.getElementById('auditRawContext').textContent = prettyPrint(currentAuditRawContext);
            document.getElementById('auditWebhookPayload').textContent = prettyPrint(currentAuditWebhookPayload);
        } catch (err) {
            document.getElementById('auditModalSubtitle').textContent = 'Gagal memuat detail: ' + err.message;
        }
    };

    window.copyAuditSection = async function (section) {
        const map = {
            payload: currentAuditRawPayload,
            context: currentAuditRawContext,
            webhook: currentAuditWebhookPayload,
        };
        const selectors = {
            payload: '#auditMessageModal [onclick="copyAuditSection(\'payload\')"]',
            context: '#auditMessageModal [onclick="copyAuditSection(\'context\')"]',
            webhook: '#auditMessageModal [onclick="copyAuditSection(\'webhook\')"]',
        };

        const payload = map[section] ?? {};
        try {
            await copyText(JSON.stringify(payload, null, 2));
            flashCopied(selectors[section] ?? selectors.payload);
        } catch (e) {
            alert('Gagal menyalin JSON: ' + e.message);
        }
    };

    window.copyAuditRowLink = async function (messageId, button) {
        try {
            const url = new URL(window.location.href);
            url.searchParams.set('message_id', messageId);
            url.hash = 'chat-message-row-' + messageId;
            await copyText(url.toString());
            flashCopiedButton(button);
        } catch (e) {
            alert('Gagal menyalin link: ' + e.message);
        }
    };

    if (focusedMessageId) {
        window.addEventListener('load', () => {
            const row = document.getElementById('chat-message-row-' + focusedMessageId);
            if (!row) return;
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            row.classList.add('table-warning');
            setTimeout(() => row.classList.remove('table-warning'), 2500);
        });
    }
})();
</script>
@endpush
