<?php

namespace App\Http\Middleware;

use App\Models\ApiCredential;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates API requests using a key + secret pair sent as headers:
 *   X-Api-Key:    fem_key_...
 *   X-Api-Secret: fem_sec_...
 *
 * The key identifies the credential; the secret is verified against a stored
 * hash. On success the owning user is bound to the request so downstream
 * policies and services behave exactly as they do for the web app.
 */
class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-Api-Key');
        $secret = $request->header('X-Api-Secret');

        if (! $key || ! $secret) {
            return $this->unauthorized('Missing API key or secret.');
        }

        $credential = ApiCredential::query()
            ->whereNull('revoked_at')
            ->where('key', $key)
            ->first();

        // Same generic message whether the key is unknown or the secret is
        // wrong, so the endpoint can't be used to probe which keys exist.
        if (! $credential || ! $credential->secretMatches($secret)) {
            return $this->unauthorized('Invalid API credentials.');
        }

        $credential->forceFill(['last_used_at' => now()])->saveQuietly();

        // Bind the owner for this request only (the API is stateless).
        Auth::setUser($credential->user);
        $request->setUserResolver(fn () => $credential->user);

        return $next($request);
    }

    private function unauthorized(string $message): JsonResponse
    {
        return response()->json(['message' => $message], 401);
    }
}
