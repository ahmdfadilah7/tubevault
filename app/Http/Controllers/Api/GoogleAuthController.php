<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GoogleAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function __construct(
        private readonly GoogleAuthService $googleAuth,
    ) {}

    public function redirect(Request $request): RedirectResponse
    {
        $redirect = $request->string('redirect')->toString() ?: config('app.frontend_url').'/library';

        return Socialite::driver('google')
            ->stateless()
            ->redirectUrl($this->googleRedirectUri())
            ->with(['state' => base64_encode(json_encode(['redirect' => $redirect]))])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        $frontendUrl = rtrim(config('app.frontend_url'), '/');
        $fallbackRedirect = $frontendUrl.'/library';

        if ($request->filled('error')) {
            $error = $request->string('error')->toString();

            return redirect("{$frontendUrl}/login?error=google_{$error}");
        }

        try {
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->redirectUrl($this->googleRedirectUri())
                ->user();

            $user = $this->googleAuth->findOrCreate($googleUser);

            $user->tokens()->delete();
            $token = $user->createToken('api')->plainTextToken;

            $redirect = $fallbackRedirect;
            if ($request->filled('state')) {
                $state = json_decode(base64_decode($request->string('state')->toString()), true);
                if (! empty($state['redirect']) && is_string($state['redirect'])) {
                    $redirect = $state['redirect'];
                }
            }

            $query = http_build_query([
                'token' => $token,
                'redirect' => $redirect,
            ]);

            return redirect("{$frontendUrl}/auth/google/callback?{$query}");
        } catch (\Throwable $e) {
            Log::error('Google OAuth failed', [
                'message' => $e->getMessage(),
                'redirect_uri' => $this->googleRedirectUri(),
            ]);

            return redirect("{$frontendUrl}/login?error=google_auth_failed");
        }
    }

    private function googleRedirectUri(): string
    {
        return config('services.google.redirect');
    }
}
