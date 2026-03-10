<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request)
    {
        $user = $this->authService->register($request->validated());

        return response()->json([
            'message' => 'User successfully registered',
            'user' => $user
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $result = $this->authService->login($request->validated());

        if (!$result) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json([
            'message' => 'Login successful',
            'data' => [
                'access_token' => $result['access_token'],
                'token_type' => 'bearer',
                'expires_in' => $result['expires_in'],
                'user' => $result['user']
            ]
        ])->cookie(
            'refresh_token',
            $result['refresh_token'],
            config('jwt.refresh_ttl'),
            '/',
            config('session.domain'), // domain
            config('session.secure'), // secure
            true, // httpOnly
            false,
            config('session.same_site') // same site
        );
    }

    public function logout(Request $request)
    {
        $refreshToken = $request->cookie('refresh_token');

        $this->authService->logout($refreshToken);

        return response()
            ->json(['message' => 'Successfully logged out'])
            ->withoutCookie('refresh_token');
    }

    public function refresh(Request $request)
    {
        $refreshToken = $request->cookie('refresh_token');

        if (!$refreshToken) {
            return response()->json(['error' => 'Refresh token not found'], 401);
        }

        $result = $this->authService->refresh($refreshToken);

        if (!$result) {
            return response()->json(['error' => 'Invalid or expired refresh token'], 401);
        }

        return response()->json([
            'message' => 'Token refreshed successfully',
            'data' => [
                'access_token' => $result['access_token'],
                'token_type' => 'bearer',
                'expires_in' => $result['expires_in']
            ]
        ]);
    }

    public function me()
    {
        return response()->json(auth('api')->user());
    }
}
