<section class="pw-card" aria-label="Status Akuntansi Baru" style="margin-bottom:1rem">
    <div class="pw-h">
        <strong>Akuntansi Baru</strong>
        <span class="pw-chip">{{ $standalonePayroll['label'] }}</span>
    </div>
    <div class="pw-b">
        @if (! config('accounting.standalone_dual_posting_payroll_enabled', true))
            <p class="pw-sub">Posting otomatis payroll sedang dinonaktifkan.</p>
        @endif
        @if ($standalonePayroll['error'])
            <p class="alert alert-warning" role="status">{{ $standalonePayroll['error'] }}</p>
        @endif
        @if ($standalonePayroll['source_alerts'])
            <div class="alert alert-warning" role="status">
                <strong>Perubahan sumber setelah pembayaran.</strong>
                <ul style="margin:.45rem 0 0;padding-left:1.1rem">
                    @foreach ($standalonePayroll['source_alerts'] as $alert)
                        <li>
                            @if ($alert['type'] === 'created_after_payment')
                                Jurnal upah #{{ $alert['journal_id'] }} senilai Rp {{ number_format($alert['amount'], 2, ',', '.') }} dibuat setelah payroll dibayar.
                            @else
                                Jurnal upah #{{ $alert['journal_id'] }} senilai Rp {{ number_format($alert['amount'], 2, ',', '.') }} dibatalkan setelah payroll dibayar.
                                @if ($alert['reason']) Alasan: {{ $alert['reason'] }}.@endif
                            @endif
                        </li>
                    @endforeach
                </ul>
                <p class="pw-sub" style="margin-top:.45rem">Periksa bukti kerja dan pembayaran sebelum membuat koreksi; sistem tidak mengubah saldo otomatis.</p>
            </div>
        @endif
        @if ($standalonePayroll['recovery'] > 0)
            <p class="pw-sub">Tagihan koreksi yang sudah diposting: Rp {{ number_format($standalonePayroll['recovery'], 2, ',', '.') }}. Tagihan ini dapat dipotong pada payroll berikutnya.</p>
        @endif
        @if ($standalonePayroll['production_count'])
            <p class="pw-sub">{{ $standalonePayroll['production_count'] }} jurnal sumber upah produksi sudah dicocokkan. Finalize hanya mencatat selisihnya.</p>
        @endif
        @if ($period->paid_at)
            <dl class="pw-row" style="gap:1.25rem;flex-wrap:wrap">
                @foreach (['gross' => 'Gaji bruto', 'loans' => 'Potongan pinjaman', 'savings' => 'Bonus ke tabungan', 'cash' => 'Dibayar melalui kas/bank'] as $key => $label)
                    <div><dt class="pw-sub">{{ $label }}</dt><dd style="margin:0;font-weight:700">Rp {{ number_format($standalonePayroll[$key], 2, ',', '.') }}</dd></div>
                @endforeach
            </dl>
        @endif
        <div class="pw-row" style="flex-wrap:wrap">
            @forelse ($standalonePayroll['journals'] as $journal)
                @if (auth()->user()->isOwner() || strtolower((string) auth()->user()->role) === 'admin')
                    <a class="pw-btn" href="{{ route('standalone-accounting.journals.show', $journal) }}">{{ $journal->reference }} · {{ $journal->voided_at ? 'Dibatalkan' : 'Posted' }}</a>
                @else
                    <span class="pw-chip">{{ $journal->reference }} · {{ $journal->voided_at ? 'Dibatalkan' : 'Posted' }}</span>
                @endif
            @empty
                <span class="pw-sub">Belum ada jurnal penyesuaian atau pembayaran payroll di Akuntansi Baru.</span>
            @endforelse
        </div>
    </div>
</section>
