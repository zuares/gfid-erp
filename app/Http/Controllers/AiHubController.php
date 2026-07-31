<?php

namespace App\Http\Controllers;

class AiHubController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $connection = $user?->openAiConnection;

        $cards = [
            [
                'title' => 'Customer Agent',
                'icon' => 'bi-chat-dots',
                'desc' => 'Jawab pertanyaan, bantu arahkan produk, dan siapkan balasan cepat untuk pelanggan.',
            ],
            [
                'title' => 'Ops Agent',
                'icon' => 'bi-diagram-3',
                'desc' => 'Rangkum order, stok, retur, dan issue operasional supaya tim bisa bergerak cepat.',
            ],
            [
                'title' => 'Growth Agent',
                'icon' => 'bi-graph-up-arrow',
                'desc' => 'Cari peluang improvement dari data marketplace, ads, dan performa produk.',
            ],
        ];

        $safeguards = [
            'OpenAI dipanggil server-side saja',
            'API key tidak pernah dikirim ke browser',
            'Akses halaman dibatasi oleh login dan role',
            'Setiap request bisa dilacak per user',
        ];

        $connectionSummary = [
            'connected' => (bool) $connection,
            'label' => $connection?->label ?? 'Belum terhubung',
            'model' => $connection?->model ?? config('services.openai.model', 'gpt-5.6-terra'),
            'verified_at' => $connection?->last_verified_at,
            'source' => $connection ? 'Personal OpenAI' : (filled(config('services.openai.key')) ? 'App OpenAI key' : 'Needs connection'),
        ];

        return view('ai.index', compact('user', 'cards', 'safeguards', 'connectionSummary'));
    }
}
