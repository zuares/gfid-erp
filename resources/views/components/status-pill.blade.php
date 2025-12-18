@props(['status'])

@php
    $map = [
        'submitted' => ['label' => 'Menunggu PRD', 'cls' => 'pill pill-submitted'],
        'shipped' => ['label' => 'Dikirim ke Transit', 'cls' => 'pill pill-shipped'],
        'partial' => ['label' => 'Sebagian Sampai', 'cls' => 'pill pill-partial'],
        'completed' => ['label' => 'Selesai (Sudah di RTS)', 'cls' => 'pill pill-completed'],
    ];

    $item = $map[$status] ?? ['label' => strtoupper((string) $status), 'cls' => 'pill'];
@endphp

<span class="{{ $item['cls'] }}">{{ $item['label'] }}</span>

@once
    <style>
        .pill {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .22rem .55rem;
            border-radius: 999px;
            font-size: .78rem;
            border: 1px solid rgba(148, 163, 184, .35);
            background: rgba(148, 163, 184, .12);
        }

        .pill-submitted {
            background: rgba(59, 130, 246, .12);
            border-color: rgba(59, 130, 246, .35);
        }

        .pill-shipped {
            background: rgba(245, 158, 11, .14);
            border-color: rgba(245, 158, 11, .35);
        }

        .pill-partial {
            background: rgba(16, 185, 129, .14);
            border-color: rgba(16, 185, 129, .35);
        }

        .pill-completed {
            background: rgba(45, 212, 191, .14);
            border-color: rgba(45, 212, 191, .35);
        }
    </style>
@endonce
