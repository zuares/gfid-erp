<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\StorefrontCustomer;
use App\Models\StorefrontOauthIdentity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OAuthLoginController extends Controller
{
    public function redirect(Request $request, string $provider = 'google')
    {
        $config = $this->providerConfig($provider);

        if (! $config) {
            return redirect()->route('storefront.login')->with('error', 'Provider OAuth belum dikonfigurasi.');
        }

        if ($request->session()->has('storefront_customer_id')) {
            return redirect()->route('storefront.user');
        }

        $state = Str::random(48);
        $request->session()->put($this->stateSessionKey($provider), $state);

        $query = array_filter([
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect'],
            'response_type' => 'code',
            'scope' => $config['scopes'],
            'state' => $state,
            'access_type' => $config['access_type'] ?? null,
            'prompt' => $config['prompt'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        return redirect()->away($config['auth_url'] . '?' . http_build_query($query));
    }

    public function callback(Request $request, string $provider = 'google')
    {
        $config = $this->providerConfig($provider);

        if (! $config) {
            return redirect()->route('storefront.login')->with('error', 'Provider OAuth belum dikonfigurasi.');
        }

        $expectedState = (string) $request->session()->pull($this->stateSessionKey($provider), '');
        $state = (string) $request->query('state', '');
        $code = (string) $request->query('code', '');

        if ($expectedState === '' || ! hash_equals($expectedState, $state)) {
            return redirect()->route('storefront.login')->with('error', 'Sesi OAuth tidak valid. Silakan coba lagi.');
        }

        if ($code === '') {
            return redirect()->route('storefront.login')->with('error', 'Kode OAuth tidak ditemukan dari provider.');
        }

        $tokenResponse = Http::asForm()
            ->acceptJson()
            ->timeout(30)
            ->post($config['token_url'], $this->tokenPayload($config, $code));

        $tokenJson = $tokenResponse->json() ?? [];
        $accessToken = data_get($tokenJson, 'access_token');

        if (! $tokenResponse->successful() || ! $accessToken) {
            return redirect()->route('storefront.login')->with('error', 'Gagal menukar kode OAuth menjadi access token.');
        }

        $profile = $this->fetchProfile($provider, $config, $accessToken);

        if ($profile === null) {
            return redirect()->route('storefront.login')->with('error', 'Profil akun OAuth tidak bisa dibaca.');
        }

        $providerUserId = (string) ($profile['provider_user_id'] ?? '');
        $email = strtolower(trim((string) ($profile['email'] ?? '')));
        $name = trim((string) ($profile['name'] ?? 'OAuth User'));
        $avatarUrl = (string) ($profile['avatar_url'] ?? '');

        if ($providerUserId === '') {
            return redirect()->route('storefront.login')->with('error', 'Profil akun OAuth tidak bisa dibaca.');
        }

        $customer = $this->resolveCustomer($provider, $providerUserId, $email, $name);

        StorefrontOauthIdentity::updateOrCreate(
            [
                'provider' => $provider,
                'provider_user_id' => $providerUserId,
            ],
            [
                'customer_id' => $customer->id,
                'email' => $email !== '' ? $email : null,
                'name' => $name !== '' ? $name : $customer->name,
                'avatar_url' => $avatarUrl !== '' ? $avatarUrl : null,
                'profile_json' => $profile['raw'] ?? [],
                'last_login_at' => now(),
            ]
        );

        $customer->forceFill([
            'name' => $name !== '' ? $name : $customer->name,
            'email' => $email !== '' ? $email : $customer->email,
            'phone_verified_at' => $customer->phone_verified_at ?: now(),
        ])->save();

        $request->session()->regenerate();
        $request->session()->forget([
            $this->stateSessionKey($provider),
            'otp_phone',
            'otp_is_new_registration',
        ]);
        $request->session()->put('storefront_customer_id', $customer->id);

        return redirect()->route('storefront.user');
    }

    protected function resolveCustomer(string $provider, string $providerUserId, string $email, string $name): StorefrontCustomer
    {
        $identity = StorefrontOauthIdentity::where('provider', $provider)
            ->where('provider_user_id', $providerUserId)
            ->first();

        if ($identity && $identity->customer) {
            return $identity->customer;
        }

        $existingCustomer = null;
        if ($email !== '') {
            $existingCustomer = StorefrontCustomer::query()
                ->whereRaw('lower(email) = ?', [$email])
                ->first();
        }

        if ($existingCustomer) {
            return $existingCustomer;
        }

        return StorefrontCustomer::create([
            'name' => $name !== '' ? $name : 'OAuth Customer',
            'email' => $email !== '' ? $email : null,
            'phone' => $this->generateCustomerPhone($provider, $providerUserId),
            'phone_verified_at' => now(),
        ]);
    }

    protected function generateCustomerPhone(string $provider, string $providerUserId): string
    {
        $seed = $provider . '|' . $providerUserId;
        $base = '62' . str_pad((string) ((int) sprintf('%u', crc32($seed)) % 10000000000), 10, '0', STR_PAD_LEFT);
        $phone = $base;
        $suffix = 0;

        while (StorefrontCustomer::query()->where('phone', $phone)->exists()) {
            $suffix++;
            $phone = $base . $suffix;
        }

        return substr($phone, 0, 24);
    }

    protected function providerConfig(string $provider): ?array
    {
        $providers = (array) config('services.oauth.providers', []);
        $config = $providers[$provider] ?? null;

        if (! is_array($config)) {
            return null;
        }

        $clientId = trim((string) ($config['client_id'] ?? ''));
        $clientSecret = trim((string) ($config['client_secret'] ?? ''));
        $redirect = trim((string) ($config['redirect'] ?? ''));

        if ($clientId === '' || $clientSecret === '' || $redirect === '') {
            return null;
        }

        return array_merge($config, [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect' => $redirect,
            'scopes' => trim((string) ($config['scopes'] ?? 'openid email profile')),
        ]);
    }

    protected function stateSessionKey(string $provider): string
    {
        return "storefront.oauth.{$provider}.state";
    }

    protected function tokenPayload(array $config, string $code): array
    {
        $payload = [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $config['redirect'],
        ];

        if (($config['token_auth_style'] ?? null) === 'basic') {
            unset($payload['client_secret']);
        }

        return $payload;
    }

    protected function fetchProfile(string $provider, array $config, string $accessToken): ?array
    {
        $profileResponse = Http::timeout(30)
            ->withToken($accessToken)
            ->acceptJson()
            ->withHeaders($config['profile_headers'] ?? [])
            ->get($config['userinfo_url']);

        if (! $profileResponse->successful()) {
            return null;
        }

        $profile = $profileResponse->json() ?? [];

        if ($provider === 'github') {
            $email = $this->resolveGithubEmail($accessToken, $config, $profile);
            $name = trim((string) data_get($profile, 'name', ''));
            if ($name === '') {
                $name = trim((string) data_get($profile, 'login', 'OAuth Customer'));
            }

            return [
                'provider_user_id' => (string) data_get($profile, 'id', ''),
                'email' => $email,
                'name' => $name !== '' ? $name : 'OAuth Customer',
                'avatar_url' => (string) data_get($profile, 'avatar_url', ''),
                'raw' => $profile,
            ];
        }

        return [
            'provider_user_id' => (string) data_get($profile, $config['profile_id_key'] ?? 'sub', ''),
            'email' => strtolower(trim((string) data_get($profile, $config['email_key'] ?? 'email', ''))),
            'name' => trim((string) (
                data_get($profile, $config['name_key'] ?? 'name')
                ?: data_get($profile, 'given_name')
                ?: data_get($profile, 'email')
                ?: 'OAuth Customer'
            )),
            'avatar_url' => (string) data_get($profile, $config['avatar_key'] ?? 'picture', ''),
            'raw' => $profile,
        ];
    }

    protected function resolveGithubEmail(string $accessToken, array $config, array $profile): string
    {
        $email = strtolower(trim((string) data_get($profile, 'email', '')));
        if ($email !== '') {
            return $email;
        }

        $emailUrl = (string) ($config['email_url'] ?? 'https://api.github.com/user/emails');
        $emailResponse = Http::timeout(30)
            ->withToken($accessToken)
            ->acceptJson()
            ->withHeaders([
                'X-GitHub-Api-Version' => '2022-11-28',
            ])
            ->get($emailUrl);

        if (! $emailResponse->successful()) {
            return '';
        }

        $emails = $emailResponse->json() ?? [];
        $primary = collect($emails)->first(function ($item) {
            return (bool) data_get($item, 'primary') && (bool) data_get($item, 'verified');
        });

        if ($primary) {
            return strtolower(trim((string) data_get($primary, 'email', '')));
        }

        $fallback = collect($emails)->firstWhere('verified', true);

        return strtolower(trim((string) data_get($fallback, 'email', '')));
    }
}
