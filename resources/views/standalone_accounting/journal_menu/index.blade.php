@extends('layouts.app')

@section('title', 'Akuntansi Baru • Menu Jurnal')

@push('head')
    <style>
        .journal-menu-page { max-width: 1280px; margin-inline: auto; display: grid; gap: 1rem; }
        .journal-menu-card { height: 100%; border: 1px solid #e6ebf2; border-radius: 14px; background: #fff; box-shadow: 0 5px 18px rgba(15, 23, 42, .04); transition: transform .16s ease, box-shadow .16s ease; }
        .journal-menu-card:hover { transform: translateY(-2px); box-shadow: 0 9px 24px rgba(15, 23, 42, .08); }
        .journal-menu-card a { display: flex; flex-direction: column; height: 100%; padding: 1.15rem; color: #0f172a; text-decoration: none; }
        .journal-menu-icon { display: inline-grid; place-items: center; width: 42px; height: 42px; margin-bottom: .85rem; border-radius: 12px; background: #eef2ff; color: #4338ca; font-size: 1.25rem; }
        .journal-menu-card h6 { margin-bottom: .4rem; font-weight: 800; }
        .journal-menu-card p { margin-bottom: 1rem; color: #64748b; font-size: .86rem; line-height: 1.5; }
        .journal-menu-card .menu-link { margin-top: auto; color: #4338ca; font-size: .82rem; font-weight: 800; }
        .journal-menu-status { display: inline-flex; width: fit-content; padding: .25rem .55rem; border-radius: 999px; background: #f1f5f9; color: #475569; font-size: .72rem; font-weight: 800; }
        .journal-menu-btn { display: inline-flex; align-items: center; min-height: 38px; padding: .45rem .85rem; border: 1px solid #dbe3ee; border-radius: 10px; background: #fff; color: #0f172a; text-decoration: none; font-size: .84rem; font-weight: 700; }
        .journal-menu-btn:hover { background: #f8fafc; color: #0f172a; }
        .journal-menu-btn.primary { background: #0f172a; border-color: #0f172a; color: #fff; }
        .journal-menu-btn.primary:hover { background: #1e293b; color: #fff; }
    </style>
@endpush

@section('content')
    <div class="journal-menu-page">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h4 class="mb-1">Akuntansi Baru — Menu Jurnal</h4>
                <div class="text-muted small">Pilih jenis transaksi atau laporan yang ingin digunakan.</div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a class="journal-menu-btn" href="{{ route('standalone-accounting.journals.index') }}">Jurnal Umum</a>
                <a class="journal-menu-btn primary" href="{{ route('standalone-accounting.journals.create') }}">+ Posting Jurnal</a>
            </div>
        </div>

        <div class="alert alert-info mb-0">
            Menu transaksi ini sudah disiapkan terpisah. Posting jurnal tetap harus memiliki debit dan kredit yang seimbang.
        </div>

        <div class="row g-3">
            @foreach ($modules as $key => $module)
                <div class="col-md-6 col-xl-4">
                    <div class="journal-menu-card">
                        <a href="{{ route('standalone-accounting.journal-menu.show', $key) }}">
                            <span class="journal-menu-icon"><i class="bi {{ $module['icon'] }}" aria-hidden="true"></i></span>
                            <h6>{{ $module['title'] }}</h6>
                            <p>{{ $module['description'] }}</p>
                            <span class="journal-menu-status">Menu tersedia</span>
                            <span class="menu-link mt-3">Buka menu <span aria-hidden="true">→</span></span>
                        </a>
                    </div>
                </div>
            @endforeach
            <div class="col-md-6 col-xl-4">
                <div class="journal-menu-card">
                    <a href="{{ route('standalone-accounting.trial-balance.period-end') }}">
                        <span class="journal-menu-icon"><i class="bi bi-calendar-check" aria-hidden="true"></i></span>
                        <h6>Neraca Saldo Periode Akhir</h6>
                        <p>Menampilkan saldo akun sampai tanggal akhir periode yang dipilih.</p>
                        <span class="journal-menu-status">Laporan tersedia</span>
                        <span class="menu-link mt-3">Buka laporan <span aria-hidden="true">→</span></span>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
