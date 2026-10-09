<?php

namespace App\Services\Instagram;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class InstagramOAuthService
{
    public function isConfigured(): bool
    {
        return filled(config('meta.instagram.client_id'))
            && filled(config('meta.instagram.client_secret'))
            && filled($this->redirectUri());
    }

    public function authorizationUrl(string $state): string
    {
        $this->ensureConfigured();

        $query = [
            'client_id' => config('meta.instagram.client_id'),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => implode(',', $this->scopes()),
            'state' => $state,
        ];

        return rtrim((string) config('meta.instagram.authorization_url'), '/')
            .'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    public function exchangeCodeForLongLivedToken(string $code): array
    {
        $this->ensureConfigured();

        $shortLivedResponse = Http::asForm()
            ->acceptJson()
            ->timeout($this->timeout())
            ->post((string) config('meta.instagram.short_lived_token_url'), [
                'client_id' => config('meta.instagram.client_id'),
                'client_secret' => config('meta.instagram.client_secret'),
                'grant_type' => 'authorization_code',
                'redirect_uri' => $this->redirectUri(),
                'code' => $code,
            ]);

        $shortLived = $this->payloadOrFail($shortLivedResponse, 'short-lived token exchange');
        $shortLivedToken = (string) ($shortLived['access_token'] ?? '');

        if ($shortLivedToken === '') {
            throw new RuntimeException('Instagram tidak mengembalikan short-lived access token.');
        }

        $longLivedResponse = Http::acceptJson()
            ->timeout($this->timeout())
            ->get(rtrim((string) config('meta.instagram.graph_url'), '/').'/access_token', [
                'grant_type' => 'ig_exchange_token',
                'client_secret' => config('meta.instagram.client_secret'),
                'access_token' => $shortLivedToken,
            ]);

        $longLived = $this->payloadOrFail($longLivedResponse, 'long-lived token exchange');
        $accessToken = (string) ($longLived['access_token'] ?? '');

        if ($accessToken === '') {
            throw new RuntimeException('Instagram tidak mengembalikan long-lived access token.');
        }

        return [
            'access_token' => $accessToken,
            'expires_in' => (int) ($longLived['expires_in'] ?? 0),
            'token_type' => $longLived['token_type'] ?? 'bearer',
            'scopes' => $this->normalizeScopes(
                $shortLived['permissions'] ?? $shortLived['scope'] ?? $this->scopes()
            ),
        ];
    }

    public function fetchProfile(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->timeout($this->timeout())
            ->get(rtrim((string) config('meta.instagram.graph_url'), '/').'/me', [
                'fields' => 'id,user_id,username,name,account_type,profile_picture_url',
            ]);

        return $this->payloadOrFail($response, 'Instagram profile lookup');
    }

    public function refreshLongLivedToken(string $accessToken): array
    {
        $response = Http::acceptJson()
            ->timeout($this->timeout())
            ->get(rtrim((string) config('meta.instagram.graph_url'), '/').'/refresh_access_token', [
                'grant_type' => 'ig_refresh_token',
                'access_token' => $accessToken,
            ]);

        return $this->payloadOrFail($response, 'long-lived token refresh');
    }

    public function redirectUri(): string
    {
        $configured = trim((string) config('meta.instagram.redirect_uri'));

        return $configured !== ''
            ? $configured
            : route('social-media.instagram.callback');
    }

    public function scopes(): array
    {
        return array_values(array_filter(array_map(
            'trim',
            (array) config('meta.instagram.scopes', [])
        )));
    }

    protected function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException(
                'Meta Instagram belum dikonfigurasi. Isi META_INSTAGRAM_APP_ID, '
                .'META_INSTAGRAM_APP_SECRET, dan META_INSTAGRAM_REDIRECT_URI.'
            );
        }
    }

    protected function timeout(): int
    {
        return max(5, (int) config('meta.instagram.timeout', 30));
    }

    protected function payloadOrFail(Response $response, string $operation): array
    {
        if (! $response->successful()) {
            throw new RuntimeException("Instagram {$operation} gagal (HTTP {$response->status()}).");
        }

        $payload = $response->json();

        if (isset($payload['data'][0]) && is_array($payload['data'][0])) {
            $payload = $payload['data'][0];
        }

        if (! is_array($payload)) {
            throw new RuntimeException("Instagram {$operation} mengembalikan response tidak valid.");
        }

        return $payload;
    }

    protected function normalizeScopes(array|string|null $scopes): array
    {
        if (is_array($scopes)) {
            return array_values(array_filter(array_map('trim', $scopes)));
        }

        return array_values(array_filter(array_map(
            'trim',
            preg_split('/[\s,]+/', (string) $scopes) ?: []
        )));
    }
}
