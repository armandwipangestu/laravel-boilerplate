<?php

namespace App\Repositories\Auth\Interfaces;

interface AuthRepositoryInterface
{
    public function register(array $data);
    public function login(array $credentials);
    public function logout();
    public function refresh($refreshToken);
    public function storeRefreshToken($userId, $token, $expiresAt);
    public function deleteRefreshToken($token);
    public function findRefreshToken($token);
}
