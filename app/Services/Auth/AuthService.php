<?php

namespace App\Services\Auth;

use App\Repositories\Auth\Interfaces\AuthRepositoryInterface;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

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
            $this->authRepository->deleteRefreshToken($refreshToken);
        }
        $this->authRepository->logout();
    }

    public function refresh($refreshToken)
    {
        $storedToken = $this->authRepository->findRefreshToken($refreshToken);

        if (!$storedToken || Carbon::now()->greaterThan($storedToken->expires_at)) {
            if ($storedToken) {
                $this->authRepository->deleteRefreshToken($refreshToken);
            }
            return null;
        }

        // Validate the JWT refresh token manually since it uses a different secret
        try {
            $payload = JWTAuth::getJWTProvider()
                ->setSecret(config('jwt.refresh_secret'))
                ->decode($refreshToken);

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

        $payload = [
            'sub' => $user->id,
            'iat' => Carbon::now()->timestamp,
            'exp' => $expiresAt->timestamp,
            'type' => 'refresh'
        ];

        $token = JWTAuth::getJWTProvider()
            ->setSecret(config('jwt.refresh_secret'))
            ->encode($payload);

        $this->authRepository->storeRefreshToken($user->id, $token, $expiresAt);

        return $token;
    }
}
