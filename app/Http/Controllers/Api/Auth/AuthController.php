<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $result = $this->authService->login(
            $request->validated(),
            $request->ip(),
            $request->userAgent()
        );

        if (!$result) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json([
            'message' => 'Successfully logged in',
            'data' => [
                'access_token' => $result['access_token'],
                'expires_in' => $result['expires_in'],
                'user' => $result['user']
            ]
        ])->cookie(
            'refresh_token',
            $result['refresh_token'],
            $result['refresh_token_ttl'],
            '/',
            config('session.domain'),
            config('session.secure'),
            true, // httpOnly
            false,
            config('session.same_site')
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
            return response()->json(['error' => 'Refresh token empty'], 401);
        }

        $result = $this->authService->refresh(
            $refreshToken,
            $request->ip(),
            $request->userAgent()
        );

        if (!$result) {
            return response()->json(['error' => 'Invalid or expired refresh token'], 401)
                ->withoutCookie('refresh_token');
        }

        return response()->json([
            'data' => [
                'access_token' => $result['access_token'],
                'expires_in' => $result['expires_in']
            ]
        ])->cookie(
            'refresh_token',
            $result['refresh_token'],
            $result['refresh_token_ttl'],
            '/',
            config('session.domain'),
            config('session.secure'),
            true, // httpOnly
            false,
            config('session.same_site')
        );
    }

    public function me()
    {
        return response()->json(Auth::guard('api')->user());
    }
}
