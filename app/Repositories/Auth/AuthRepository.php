<?php

namespace App\Repositories\Auth;

use App\Models\User;
use App\Models\RefreshToken;
use App\Repositories\Auth\Interfaces\AuthRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthRepository implements AuthRepositoryInterface
{
    public function register(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    public function login(array $credentials)
    {
        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = Auth::guard('api');
        return $guard->attempt($credentials);
    }

    public function logout()
    {
        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = Auth::guard('api');
        $guard->logout();
    }

    public function refresh($refreshToken)
    {
        // Handled in AuthService
        return true;
    }

    public function storeRefreshToken(array $data)
    {
        return RefreshToken::create([
            'user_id' => $data['user_id'],
            'jti' => $data['jti'],
            'token_hash' => hash('sha256', $data['token']),
            'expires_at' => $data['expires_at'],
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
        ]);
    }

    public function findRefreshTokenByJti($jti)
    {
        return RefreshToken::where('jti', $jti)
            ->whereNull('revoked_at')
            ->first();
    }

    public function deleteRefreshTokenByJti($jti)
    {
        return RefreshToken::where('jti', $jti)->delete();
    }

    public function revokeRefreshTokenByJti($jti)
    {
        return RefreshToken::where('jti', $jti)->update([
            'revoked_at' => now(),
        ]);
    }

    public function revokeAllTokensForUser($userId)
    {
        return RefreshToken::where('user_id', $userId)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
            ]);
    }
}
