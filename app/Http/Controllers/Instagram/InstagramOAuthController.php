<?php

namespace App\Http\Controllers\Instagram;

use App\Http\Controllers\Controller;
use App\Models\InstagramConnection;
use App\Services\Instagram\InstagramOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class InstagramOAuthController extends Controller
{
    public function __construct(
        protected InstagramOAuthService $oauth,
    ) {}

    public function index(Request $request)
    {
        $connections = InstagramConnection::query()
            ->where('user_id', $request->user()->id)
            ->latest('last_connected_at')
            ->get();

        return view('social-media.instagram', [
            'connections' => $connections,
            'configured' => $this->oauth->isConfigured(),
            'scopes' => $this->oauth->scopes(),
        ]);
    }

    public function redirect(Request $request): RedirectResponse
    {
        if (! $this->oauth->isConfigured()) {
            return redirect()->route('social-media.instagram')
                ->with('error', 'Meta Instagram belum dikonfigurasi di environment aplikasi.');
        }

        $state = Str::random(64);

        $request->session()->put('instagram.oauth_state', [
            'value' => $state,
            'user_id' => $request->user()->id,
            'created_at' => now()->timestamp,
        ]);

        return redirect()->away($this->oauth->authorizationUrl($state));
    }

    public function callback(Request $request): RedirectResponse
    {
        $savedState = $request->session()->pull('instagram.oauth_state');
        $incomingState = (string) $request->query('state', '');
        $stateIssuedAt = is_array($savedState)
            ? (int) ($savedState['created_at'] ?? 0)
            : 0;

        if (! is_array($savedState)
            || (int) ($savedState['user_id'] ?? 0) !== (int) $request->user()->id
            || $stateIssuedAt <= 0
            || abs(now()->timestamp - $stateIssuedAt) > 600
            || ! hash_equals((string) ($savedState['value'] ?? ''), $incomingState)) {
            return redirect()->route('social-media.instagram')
                ->with('error', 'Callback Instagram ditolak karena state OAuth tidak valid atau sudah kedaluwarsa.');
        }

        if ($request->filled('error')) {
            return redirect()->route('social-media.instagram')
                ->with('error', 'Otorisasi Instagram dibatalkan oleh pengguna.');
        }

        $code = preg_replace('/#_$/', '', (string) $request->query('code', ''));
        if ($code === '') {
            return redirect()->route('social-media.instagram')
                ->with('error', 'Instagram tidak mengembalikan authorization code.');
        }

        try {
            $token = $this->oauth->exchangeCodeForLongLivedToken($code);
            $profile = $this->oauth->fetchProfile($token['access_token']);
            $instagramUserId = (string) ($profile['user_id'] ?? $profile['id'] ?? '');
            $expiresIn = (int) ($token['expires_in'] ?? 0);

            if ($instagramUserId === '') {
                throw new \RuntimeException('Instagram user ID tidak ditemukan pada profile response.');
            }

            InstagramConnection::updateOrCreate(
                [
                    'user_id' => $request->user()->id,
                    'instagram_user_id' => $instagramUserId,
                ],
                [
                    'username' => $profile['username'] ?? null,
                    'access_token' => $token['access_token'],
                    'token_expires_at' => $expiresIn > 0 ? now()->addSeconds($expiresIn) : null,
                    'scopes' => $token['scopes'],
                    'status' => 'active',
                    'last_connected_at' => now(),
                    'revoked_at' => null,
                    'metadata' => [
                        'account_type' => $profile['account_type'] ?? null,
                        'name' => $profile['name'] ?? null,
                        'token_type' => $token['token_type'] ?? 'bearer',
                    ],
                ]
            );
        } catch (Throwable $e) {
            Log::warning('Instagram OAuth callback failed', [
                'user_id' => $request->user()->id,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return redirect()->route('social-media.instagram')
                ->with('error', 'Instagram gagal dihubungkan. Periksa konfigurasi Meta dan coba lagi.');
        }

        return redirect()->route('social-media.instagram')
            ->with('success', 'Akun Instagram berhasil dihubungkan.');
    }
}
