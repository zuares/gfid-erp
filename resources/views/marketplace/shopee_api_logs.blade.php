@extends('layouts.app')

@section('title', 'Shopee API Logs')

@push('head')
    <style>
        .log-table th {
            background-color: #f8fafc;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .log-row-success {
            border-left: 4px solid #10b981;
        }
        .log-row-error {
            border-left: 4px solid #ef4444;
        }
        .log-payload {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer;
            color: #3b82f6;
        }
        .log-modal-pre {
            max-height: 500px;
            overflow-y: auto;
            background: #1e293b;
            color: #e2e8f0;
            padding: 1rem;
            border-radius: 0.5rem;
            font-size: 0.85rem;
        }
    </style>
@endpush

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">
            <i class="bi bi-hdd-network text-primary me-2"></i>Shopee API Logs (Real-time)
        </h4>
        <span class="badge bg-success" id="connection-status">
            <i class="bi bi-circle-fill small me-1"></i> Connected to WebSockets
        </span>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 75vh; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0 log-table">
                    <thead>
                        <tr>
                            <th width="12%">Time</th>
                            <th width="8%">Method</th>
                            <th width="40%">Endpoint</th>
                            <th width="10%">Status</th>
                            <th width="10%">Duration</th>
                            <th width="20%">Payload</th>
                        </tr>
                    </thead>
                    <tbody id="log-table-body">
                        @forelse($logs as $log)
                            <tr class="{{ $log->status_code >= 200 && $log->status_code < 300 ? 'log-row-success' : 'log-row-error' }}">
                                <td class="text-muted small">{{ $log->created_at->format('H:i:s Y-m-d') }}</td>
                                <td>
                                    <span class="badge {{ $log->method === 'POST' ? 'bg-primary' : 'bg-info' }}">
                                        {{ $log->method }}
                                    </span>
                                </td>
                                <td class="font-monospace small" style="word-break: break-all;">
                                    {{ Str::limit(str_replace('https://partner.shopeemobile.com', '', $log->endpoint), 80) }}
                                </td>
                                <td>
                                    <span class="badge {{ $log->status_code >= 200 && $log->status_code < 300 ? 'bg-success' : 'bg-danger' }}">
                                        {{ $log->status_code }}
                                    </span>
                                </td>
                                <td class="text-muted small">{{ number_format($log->duration, 2) }} ms</td>
                                <td>
                                    <div class="log-payload" onclick="showPayloadModal({{ $log->id }})">
                                        View Data <i class="bi bi-box-arrow-up-right ms-1"></i>
                                    </div>
                                    <textarea id="payload-req-{{ $log->id }}" class="d-none">{{ json_encode($log->request_payload, JSON_PRETTY_PRINT) }}</textarea>
                                    <textarea id="payload-res-{{ $log->id }}" class="d-none">{{ json_encode($log->response_payload, JSON_PRETTY_PRINT) }}</textarea>
                                </td>
                            </tr>
                        @empty
                            <tr id="empty-row">
                                <td colspan="6" class="text-center py-4 text-muted">Belum ada log request ke Shopee.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk melihat detail Payload -->
<div class="modal fade" id="payloadModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title font-monospace">Payload Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body pt-3">
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-muted">Request Payload</h6>
                <pre class="log-modal-pre" id="modal-req-content"></pre>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted">Response Payload</h6>
                <pre class="log-modal-pre" id="modal-res-content"></pre>
            </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
    // Modal Logic
    const payloadModal = new bootstrap.Modal(document.getElementById('payloadModal'));
    window.showPayloadModal = function(id) {
        document.getElementById('modal-req-content').textContent = document.getElementById('payload-req-' + id).value || '{}';
        document.getElementById('modal-res-content').textContent = document.getElementById('payload-res-' + id).value || '{}';
        payloadModal.show();
    }

    // Realtime WebSockets Listener
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof window.Echo !== 'undefined') {
            window.Echo.private('shopee-api-logs')
                .listen('.ShopeeApiLogged', (e) => {
                    const emptyRow = document.getElementById('empty-row');
                    if (emptyRow) emptyRow.remove();

                    const isSuccess = e.status_code >= 200 && e.status_code < 300;
                    const methodBadge = e.method === 'POST' ? 'bg-primary' : 'bg-info';
                    const statusBadge = isSuccess ? 'bg-success' : 'bg-danger';
                    const rowClass = isSuccess ? 'log-row-success' : 'log-row-error';
                    const endpointShort = e.endpoint.replace('https://partner.shopeemobile.com', '').substring(0, 80);
                    
                    // We can't send full payload through WS efficiently, so we just show an info badge for new realtime logs
                    const newRow = `
                        <tr class="${rowClass} table-active" style="animation: highlight 2s forwards;">
                            <td class="text-muted small">${e.created_at}</td>
                            <td><span class="badge ${methodBadge}">${e.method}</span></td>
                            <td class="font-monospace small" style="word-break: break-all;">${endpointShort}</td>
                            <td><span class="badge ${statusBadge}">${e.status_code}</span></td>
                            <td class="text-muted small">${parseFloat(e.duration).toFixed(2)} ms</td>
                            <td><span class="text-muted small fst-italic">Refresh to view payload</span></td>
                        </tr>
                    `;
                    
                    const tbody = document.getElementById('log-table-body');
                    tbody.insertAdjacentHTML('afterbegin', newRow);
                    
                    // Limit rows to 100 on client side
                    if (tbody.children.length > 100) {
                        tbody.lastElementChild.remove();
                    }
                });
        } else {
            const status = document.getElementById('connection-status');
            status.className = 'badge bg-secondary';
            status.innerHTML = '<i class="bi bi-circle small me-1"></i> WebSockets Disconnected';
        }
    });
</script>
<style>
    @keyframes highlight {
        0% { background-color: rgba(59, 130, 246, 0.1); }
        100% { background-color: transparent; }
    }
</style>
@endpush
