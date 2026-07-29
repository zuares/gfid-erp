<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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
        ]);

        $result = $this->runOpenAi([
            'mode' => $data['mode'] ?? 'internal',
            'message' => $data['message'],
            'purpose' => 'Answer as a helpful AI assistant for this website. Keep the reply concise, practical, and friendly.',
        ]);

        return response()->json($result);
    }

    public function task(Request $request)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'min:3', 'max:4000'],
        ]);

        $result = $this->runOpenAi([
            'mode' => 'task',
            'message' => $data['message'],
            'purpose' => 'Turn the instruction into a Codex-ready work brief for a developer. Output a practical task draft.',
        ]);

        return response()->json($result);
    }

    protected function runOpenAi(array $input): array
    {
        $apiKey = config('services.openai.key');
        if (! $apiKey) {
            return [
                'ok' => false,
                'message' => 'OPENAI_API_KEY belum diset di .env.',
            ];
        }

        $system = <<<'TXT'
Kamu adalah AI assistant untuk website internal GreatFit.
- Jawab singkat, jelas, ramah, dan konkret.
- Jika diminta membuat task, ubah permintaan menjadi brief yang siap dikerjakan Codex/developer.
- Jangan mengarang data yang tidak tersedia.
- Jika butuh konteks data website, minta rincian atau jelaskan asumsi.
TXT;

        $context = [
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
        ];

        $payload = [
            'model' => config('services.openai.model', 'gpt-5.6-terra'),
            'input' => [
                [
                    'role' => 'system',
                    'content' => [
                        ['type' => 'input_text', 'text' => $system],
                    ],
                ],
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'input_text', 'text' => json_encode([
                            'input' => $input,
                            'context' => $context,
                            'response_format' => [
                                'reply' => 'string',
                                'task_title' => 'string|null',
                                'task_summary' => 'string|null',
                                'task_steps' => 'array|null',
                            ],
                        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)],
                    ],
                ],
            ],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'ai_agent_response',
                    'schema' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'reply' => ['type' => 'string'],
                            'task_title' => ['type' => ['string', 'null']],
                            'task_summary' => ['type' => ['string', 'null']],
                            'task_steps' => [
                                'type' => ['array', 'null'],
                                'items' => ['type' => 'string'],
                            ],
                        ],
                        'required' => ['reply', 'task_title', 'task_summary', 'task_steps'],
                    ],
                    'strict' => true,
                ],
            ],
        ];

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(45)
            ->post('https://api.openai.com/v1/responses', $payload);

        if (! $response->successful()) {
            return [
                'ok' => false,
                'message' => 'OpenAI API gagal dipanggil.',
                'status' => $response->status(),
                'error' => $response->json('error.message') ?? $response->body(),
            ];
        }

        $json = $response->json();
        $text = data_get($json, 'output_text');

        if (! $text) {
            $text = collect(data_get($json, 'output', []))
                ->flatMap(fn ($item) => data_get($item, 'content', []))
                ->pluck('text')
                ->filter()
                ->implode('');
        }

        $parsed = json_decode((string) $text, true);
        if (! is_array($parsed)) {
            $parsed = [
                'reply' => trim((string) $text),
                'task_title' => null,
                'task_summary' => null,
                'task_steps' => [],
            ];
        }

        return [
            'ok' => true,
            'reply' => (string) ($parsed['reply'] ?? ''),
            'task' => [
                'title' => $parsed['task_title'] ?? null,
                'summary' => $parsed['task_summary'] ?? null,
                'steps' => array_values(array_filter((array) ($parsed['task_steps'] ?? []))),
            ],
            'meta' => [
                'model' => data_get($json, 'model'),
                'id' => data_get($json, 'id'),
            ],
        ];
    }
}
