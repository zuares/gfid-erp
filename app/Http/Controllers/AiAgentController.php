<?php

namespace App\Http\Controllers;

use App\Services\OpenAI\OpenAiService;
use Illuminate\Http\Request;

class AiAgentController extends Controller
{
    public function index(Request $request)
    {
        $agentModes = [
            [
                'title' => 'Customer Agent',
                'desc' => 'Bantu jawab pertanyaan, arahkan produk, dan follow-up pelanggan secara cepat.',
                'status' => 'Live-ready',
            ],
            [
                'title' => 'Ops Agent',
                'desc' => 'Pantau order, stok, retur, dan issue operasional untuk tim internal.',
                'status' => 'Internal',
            ],
            [
                'title' => 'Growth Agent',
                'desc' => 'Cari peluang peningkatan konversi, iklan, dan repeat order dari data yang ada.',
                'status' => 'Insight',
            ],
        ];

        $capabilities = [
            'Menjawab FAQ produk dan operasional',
            'Meringkas chat, order, dan issue penting',
            'Membantu drafting balasan yang lebih cepat',
            'Menyusun rekomendasi berbasis data toko',
            'Menghubungkan workflow ke halaman internal lain',
            'Siap dikembangkan jadi agent workflow penuh',
        ];

        $useCases = [
            [
                'kicker' => 'Untuk pelanggan',
                'title' => 'AI concierge di halaman depan',
                'body' => 'Jadikan AI sebagai pintu masuk yang ramah: jawab pertanyaan produk, bantu pilih ukuran, dan arahkan ke chat atau katalog.',
            ],
            [
                'kicker' => 'Untuk tim internal',
                'title' => 'AI copilot operasional',
                'body' => 'Beri tim ringkasan otomatis untuk order, chat, stok, atau tugas harian supaya keputusan lebih cepat dan rapi.',
            ],
            [
                'kicker' => 'Untuk owner',
                'title' => 'AI command center',
                'body' => 'Bangun halaman khusus AI sebagai pusat kontrol yang menghubungkan insight, aksi cepat, dan prioritas bisnis.',
            ],
        ];

        return view('ai.agent', compact('agentModes', 'capabilities', 'useCases'));
    }

    public function chat(Request $request)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'min:3', 'max:4000'],
            'mode' => ['nullable', 'in:general,internal,task'],
            'page_context' => ['nullable', 'array'],
            'page_context.page_title' => ['nullable', 'string', 'max:200'],
            'page_context.route' => ['nullable', 'string', 'max:200'],
            'page_context.path' => ['nullable', 'string', 'max:300'],
            'page_context.url' => ['nullable', 'string', 'max:1000'],
        ]);

        $result = app(OpenAiService::class)->generateAgentResponse(
            $data['mode'] ?? 'internal',
            $data['message'],
            array_filter([
                'page' => 'AI Agent',
                'app_name' => config('app.name', 'GFID'),
                'route' => request()->route()?->getName(),
                'user_name' => auth()->user()?->name,
                'user_role' => auth()->user()?->role,
                'available_sections' => [
                    'dashboard',
                    'marketplace',
                    'sales',
                    'inventory',
                    'production',
                    'purchasing',
                    'payroll',
                    'accounting',
                    'owner',
                ],
                'page_context' => $data['page_context'] ?? null,
            ], fn ($value) => $value !== null),
            'Answer as a helpful AI assistant for this website. Keep the reply concise, practical, and friendly.',
            $request->user()
        );

        if (($result['ok'] ?? true) === false) {
            return response()->json($result, (int) ($result['status'] ?? 422));
        }

        return response()->json($result);
    }

    public function task(Request $request)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'min:3', 'max:4000'],
            'page_context' => ['nullable', 'array'],
            'page_context.page_title' => ['nullable', 'string', 'max:200'],
            'page_context.route' => ['nullable', 'string', 'max:200'],
            'page_context.path' => ['nullable', 'string', 'max:300'],
            'page_context.url' => ['nullable', 'string', 'max:1000'],
        ]);

        $result = app(OpenAiService::class)->generateAgentResponse(
            'task',
            $data['message'],
            array_filter([
                'page' => 'AI Agent',
                'app_name' => config('app.name', 'GFID'),
                'route' => request()->route()?->getName(),
                'user_name' => auth()->user()?->name,
                'user_role' => auth()->user()?->role,
                'available_sections' => [
                    'dashboard',
                    'marketplace',
                    'sales',
                    'inventory',
                    'production',
                    'purchasing',
                    'payroll',
                    'accounting',
                    'owner',
                ],
                'page_context' => $data['page_context'] ?? null,
            ], fn ($value) => $value !== null),
            'Turn the instruction into a Codex-ready work brief for a developer. Output a practical task draft.',
            $request->user()
        );

        if (($result['ok'] ?? true) === false) {
            return response()->json($result, (int) ($result['status'] ?? 422));
        }

        return response()->json($result);
    }
}
