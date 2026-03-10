<?php

namespace App\Services\Auth;

use App\Repositories\Auth\Interfaces\AuthRepositoryInterface;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuthService
{
    protected $authRepository;

    public function __construct(AuthRepositoryInterface $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    public function register(array $data)
    {
        return $this->authRepository->register($data);
    }

    public function login(array $credentials, ?string $ipAddress = null, ?string $userAgent = null)
    {
        if (!$token = $this->authRepository->login($credentials)) {
            return null;
        }

        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = Auth::guard('api');
        $user = $guard->user();

        $refreshData = $this->generateRefreshToken($user, $ipAddress, $userAgent);

        return [
            'access_token' => $token,
            'refresh_token' => $refreshData['token'],
            'refresh_token_ttl' => $refreshData['ttl'],
            'user' => $user,
            'expires_in' => (int) config('jwt.ttl') * 60
        ];
    }

    public function logout($refreshToken)
    {
        if ($refreshToken) {
            $payload = $this->decodeRefreshToken($refreshToken);

            if ($payload && isset($payload['jti']) && ($payload['type'] ?? '') === 'refresh') {
                $this->authRepository->revokeRefreshTokenByJti($payload['jti']);
            }
        }
        $this->authRepository->logout();
    }

    public function refresh($refreshToken, ?string $ipAddress = null, ?string $userAgent = null)
    {
        $payload = $this->decodeRefreshToken($refreshToken);

        if (!$payload || !isset($payload['jti']) || !isset($payload['sub']) || ($payload['type'] ?? '') !== 'refresh') {
            return null;
        }

        $storedToken = $this->authRepository->findRefreshTokenByJti($payload['jti']);

        // Reuse Detection: If the token is already revoked or missing but payload is valid, 
        // it means either it's expired or it was revoked (potentially reused).
        if (!$storedToken) {
            // Check if it's already in the DB but revoked
            $revokedToken = \App\Models\RefreshToken::where('jti', $payload['jti'])->first();
            if ($revokedToken && $revokedToken->revoked_at) {
                // Potential replay attack - revoke all for user
                $this->authRepository->revokeAllTokensForUser($payload['sub']);
            }
            return null;
        }

        // Validate SHA256 hash
        if (!hash_equals($storedToken->token_hash, hash('sha256', $refreshToken))) {
            return null;
        }

        // Verify expiration (double check against DB)
        if (Carbon::now()->greaterThan($storedToken->expires_at)) {
            $this->authRepository->revokeRefreshTokenByJti($payload['jti']);
            return null;
        }

        $user = \App\Models\User::find($payload['sub']);
        if (!$user) return null;

        // Rotation: Revoke old, issue new
        $this->authRepository->revokeRefreshTokenByJti($payload['jti']);
        $refreshData = $this->generateRefreshToken($user, $ipAddress, $userAgent);

        // Generate new access token
        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = Auth::guard('api');
        $newToken = $guard->login($user);

        return [
            'access_token' => $newToken,
            'refresh_token' => $refreshData['token'],
            'refresh_token_ttl' => $refreshData['ttl'],
            'expires_in' => (int) config('jwt.ttl') * 60
        ];
    }

    protected function generateRefreshToken($user, ?string $ipAddress = null, ?string $userAgent = null)
    {
        $ttl = (int) config('jwt.refresh_ttl', 20160); // Default 2 weeks
        $expiresAt = Carbon::now()->addMinutes($ttl);
        $jti = Str::uuid()->toString();

        $payload = [
            'sub' => $user->id,
            'iat' => Carbon::now()->timestamp,
            'exp' => $expiresAt->timestamp,
            'jti' => $jti,
            'type' => 'refresh'
        ];

        $token = JWTAuth::getJWTProvider()
            ->setSecret(config('jwt.refresh_secret'))
            ->encode($payload);

        $this->authRepository->storeRefreshToken([
            'user_id' => $user->id,
            'jti' => $jti,
            'token' => $token,
            'expires_at' => $expiresAt,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        return [
            'token' => $token,
            'ttl' => $ttl
        ];
    }

    private function decodeRefreshToken(string $token)
    {
        try {
            return JWTAuth::getJWTProvider()
                ->setSecret(config('jwt.refresh_secret'))
                ->decode($token);
        } catch (\Exception $e) {
            return null;
        }
    }
}
