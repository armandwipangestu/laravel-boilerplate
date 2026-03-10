<?php

namespace App\Repositories\Auth;

use App\Models\User;
use App\Models\RefreshToken;
use App\Repositories\Auth\Interfaces\AuthRepositoryInterface;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
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
        // This will be handled in AuthService using the manual JWT signing for refresh token
        return true;
    }

    public function storeRefreshToken($userId, $token, $expiresAt)
    {
        return RefreshToken::create([
            'user_id' => $userId,
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);
    }

    public function deleteRefreshToken($token)
    {
        return RefreshToken::where('token', $token)->delete();
    }

    public function findRefreshToken($token)
    {
        return RefreshToken::where('token', $token)->first();
    }
}
