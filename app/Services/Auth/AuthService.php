<?php

namespace App\Services\Auth;

use App\Repositories\Auth\Interfaces\AuthRepositoryInterface;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

    public function login(array $credentials)
    {
        if (!$token = $this->authRepository->login($credentials)) {
            return null;
        }

        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = Auth::guard('api');
        $user = $guard->user();
        $refreshToken = $this->generateRefreshToken($user);

        return [
            'access_token' => $token,
            'refresh_token' => $refreshToken,
            'user' => $user,
            'expires_in' => (int) config('jwt.ttl') * 60
        ];
    }

    public function logout($refreshToken)
    {
        if ($refreshToken) {
            try {
                $payload = JWTAuth::getJWTProvider()
                    ->setSecret(config('jwt.refresh_secret'))
                    ->decode($refreshToken);

                if (isset($payload['jti'])) {
                    $this->authRepository->deleteRefreshTokenByJti($payload['jti']);
                }
            } catch (\Exception $e) {
                // Silently fail if token is invalid
            }
        }
        $this->authRepository->logout();
    }

    public function refresh($refreshToken)
    {
        try {
            $payload = JWTAuth::getJWTProvider()
                ->setSecret(config('jwt.refresh_secret'))
                ->decode($refreshToken);

            if (!isset($payload['jti']) || !isset($payload['sub'])) {
                return null;
            }

            $storedToken = $this->authRepository->findRefreshTokenByJti($payload['jti']);

            if (!$storedToken || Carbon::now()->greaterThan($storedToken->expires_at)) {
                if ($storedToken) {
                    $this->authRepository->deleteRefreshTokenByJti($payload['jti']);
                }
                return null;
            }

            // Verify the hashed token in database against the provided refresh token string
            if (!Hash::check($refreshToken, $storedToken->token)) {
                return null;
            }

            $user = \App\Models\User::find($payload['sub']);

            if (!$user) return null;

            // Generate new access token
            /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
            $guard = Auth::guard('api');
            $newToken = $guard->login($user);

            return [
                'access_token' => $newToken,
                'expires_in' => (int) config('jwt.ttl') * 60
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function generateRefreshToken($user)
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

        $this->authRepository->storeRefreshToken($user->id, $jti, $token, $expiresAt);

        return $token;
    }
}
