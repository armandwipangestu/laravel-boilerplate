<?php

namespace App\Repositories\Auth\Interfaces;

interface AuthRepositoryInterface
{
    public function register(array $data);
    public function login(array $credentials);
    public function logout();
    public function refresh($refreshToken);
    public function storeRefreshToken($userId, $jti, $token, $expiresAt);
    public function findRefreshTokenByJti($jti);
    public function deleteRefreshTokenByJti($jti);
    public function deleteRefreshToken($token);
    public function findRefreshToken($token);
}
