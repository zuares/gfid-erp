<?php

namespace App\Services\OpenAI;

use App\Models\OpenAiConnection;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class OpenAiService
{
    public function generateAgentResponse(
        string $mode,
        string $message,
        array $context = [],
        string $purpose = '',
        ?User $user = null
    ): array {
        $connection = $this->resolveConnection($user);
        $apiKey = $this->resolveApiKey($connection);
        $model = $this->resolveModel($connection);

        if (! $apiKey) {
            return [
                'ok' => false,
                'message' => 'OpenAI belum dikonfigurasi. Isi API key app atau connect OpenAI per user dulu.',
            ];
        }

        $system = <<<'TXT'
Kamu adalah AI assistant untuk website internal GreatFit.
- Jawab singkat, jelas, ramah, dan konkret.
- Jika diminta membuat task, ubah permintaan menjadi brief yang siap dikerjakan Codex/developer.
- Jangan mengarang data yang tidak tersedia.
- Jika butuh konteks data website, minta rincian atau jelaskan asumsi.
TXT;

        $payload = [
            'model' => $model,
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
                        [
                            'type' => 'input_text',
                            'text' => json_encode([
                                'input' => [
                                    'mode' => $mode,
                                    'message' => $message,
                                    'purpose' => $purpose,
                                ],
                                'context' => $context,
                                'response_format' => [
                                    'reply' => 'string',
                                    'task_title' => 'string|null',
                                    'task_summary' => 'string|null',
                                    'task_steps' => 'array|null',
                                ],
                            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                        ],
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

        if ($user) {
            $payload['safety_identifier'] = hash_hmac(
                'sha256',
                'user:' . $user->id,
                (string) config('app.key')
            );
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(45)
            ->retry(2, 250)
            ->withHeaders($this->providerHeaders($connection))
            ->post('https://api.openai.com/v1/responses', $payload);

        if (! $response->successful()) {
            $error = $response->json('error');
            $errorMessage = data_get($error, 'message') ?: trim((string) $response->body());

            return [
                'ok' => false,
                'message' => 'OpenAI API gagal dipanggil: ' . ($errorMessage !== '' ? $errorMessage : 'unknown error'),
                'status' => $response->status(),
                'error' => $error ?: $response->body(),
                'error_type' => data_get($error, 'type'),
                'error_code' => data_get($error, 'code'),
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
                'model' => $model ?: data_get($json, 'model'),
                'id' => data_get($json, 'id'),
                'source' => $connection ? 'user' : 'app',
            ],
        ];
    }

    public function probeApiKey(string $apiKey, ?string $model = null, ?string $organizationId = null, ?string $projectId = null): array
    {
        $model = $model ?: config('services.openai.model', 'gpt-5.6-terra');
        $payload = [
            'model' => $model,
            'input' => [
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'input_text', 'text' => 'Reply with exactly: ok'],
                    ],
                ],
            ],
            'max_output_tokens' => 8,
        ];

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(30)
            ->withHeaders(array_filter([
                'OpenAI-Organization' => $organizationId,
                'OpenAI-Project' => $projectId,
            ], fn ($value) => filled($value)))
            ->post('https://api.openai.com/v1/responses', $payload);

        if (! $response->successful()) {
            $error = $response->json('error');
            $errorMessage = data_get($error, 'message') ?: trim((string) $response->body());

            return [
                'ok' => false,
                'message' => $errorMessage !== '' ? $errorMessage : 'OpenAI API key tidak valid.',
                'status' => $response->status(),
                'error' => $error ?: $response->body(),
            ];
        }

        return [
            'ok' => true,
            'model' => data_get($response->json(), 'model', $model),
        ];
    }

    protected function resolveConnection(?User $user): ?OpenAiConnection
    {
        if (! $user) {
            return null;
        }

        $connection = $user->relationLoaded('openAiConnection')
            ? $user->openAiConnection
            : $user->openAiConnection()->first();

        if ($connection && ! $connection->is_active) {
            return null;
        }

        return $connection;
    }

    protected function resolveApiKey(?OpenAiConnection $connection): ?string
    {
        if ($connection && filled($connection->api_key)) {
            return (string) $connection->api_key;
        }

        return trim((string) config('services.openai.key'));
    }

    protected function resolveModel(?OpenAiConnection $connection): string
    {
        if ($connection && filled($connection->model)) {
            return (string) $connection->model;
        }

        return (string) config('services.openai.model', 'gpt-5.6-terra');
    }

    protected function providerHeaders(?OpenAiConnection $connection): array
    {
        if (! $connection) {
            return [];
        }

        return array_filter([
            'OpenAI-Organization' => $connection->organization_id ?: null,
            'OpenAI-Project' => $connection->project_id ?: null,
        ], fn ($value) => filled($value));
    }
}
