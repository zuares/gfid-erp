<?php

namespace App\Http\Controllers\Ai;

use App\Http\Controllers\Controller;
use App\Models\OpenAiConnection;
use App\Services\OpenAI\OpenAiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OpenAiConnectionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $connection = $user?->openAiConnection;

        $defaults = [
            'label' => 'Personal OpenAI',
            'model' => config('services.openai.model', 'gpt-5.6-terra'),
        ];

        return view('ai.openai', [
            'user' => $user,
            'connection' => $connection,
            'defaults' => $defaults,
        ]);
    }

    public function store(Request $request, OpenAiService $service)
    {
        return $this->persist($request, $service);
    }

    public function destroy(Request $request)
    {
        $connection = $request->user()?->openAiConnection;

        if ($connection) {
            $connection->delete();
        }

        return redirect()
            ->route('ai.openai.index')
            ->with('success', 'Koneksi OpenAI berhasil dihapus.');
    }

    protected function persist(Request $request, OpenAiService $service): RedirectResponse
    {
        $user = $request->user();
        $existing = $user?->openAiConnection;

        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:120'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'organization_id' => ['nullable', 'string', 'max:120'],
            'project_id' => ['nullable', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
        ]);

        $apiKey = trim((string) ($data['api_key'] ?? ''));
        $model = trim((string) ($data['model'] ?? '')) ?: config('services.openai.model', 'gpt-5.6-terra');
        $organizationId = trim((string) ($data['organization_id'] ?? '')) ?: null;
        $projectId = trim((string) ($data['project_id'] ?? '')) ?: null;
        $effectiveKey = $apiKey !== '' ? $apiKey : ($existing?->api_key ?? '');

        if ($effectiveKey === '') {
            return back()
                ->withInput($request->except('api_key'))
                ->withErrors(['api_key' => 'API key OpenAI wajib diisi.']);
        }

        if ($effectiveKey !== '') {
            $probe = $service->probeApiKey(
                $effectiveKey,
                $model,
                $organizationId,
                $projectId
            );

            if (! ($probe['ok'] ?? false)) {
                return back()
                    ->withInput($request->except('api_key'))
                    ->withErrors(['api_key' => $probe['message'] ?? 'API key OpenAI gagal diverifikasi.']);
            }
        }

        $payload = [
            'label' => trim((string) ($data['label'] ?? '')) ?: 'Personal OpenAI',
            'api_key' => $effectiveKey,
            'organization_id' => $organizationId,
            'project_id' => $projectId,
            'model' => $model,
            'is_active' => true,
            'last_verified_at' => now(),
            'last_error' => null,
        ];

        if ($existing) {
            $existing->forceFill($payload)->save();
        } else {
            OpenAiConnection::create($payload + ['user_id' => $user->id]);
        }

        return redirect()
            ->route('ai.index')
            ->with('success', $existing
                ? 'OpenAI berhasil diperbarui.'
                : 'OpenAI berhasil terhubung dan diverifikasi.');
    }
}
