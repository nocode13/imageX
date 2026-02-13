<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\AuthTokenResource;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->toDTO());
        $token = $this->authService->createToken($user);

        return AuthTokenResource::make(['user' => $user, 'token' => $token])
            ->response()
            ->setStatusCode(201);
    }

    public function login(LoginRequest $request): AuthTokenResource|JsonResponse
    {
        $user = $this->authService->login($request->toDTO());

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $token = $this->authService->createToken($user);

        return AuthTokenResource::make(['user' => $user, 'token' => $token]);
    }

    public function me(Request $request): UserResource
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return UserResource::make($user);
    }
}
