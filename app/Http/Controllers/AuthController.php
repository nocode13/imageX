<?php

namespace App\Http\Controllers;

use App\DTO\LoginDTO;
use App\DTO\RegisterUserDTO;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
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

        $dto = RegisterUserDTO::fromArray($request->validated());
        $user = $this->authService->register($dto);
        $token = $this->authService->createToken($user);

        return UserResource::make($user)
            ->additional(['token' => $token])
            ->response()
            ->setStatusCode(201);
    }

    public function login(LoginRequest $request): UserResource
    {
        $dto = LoginDTO::fromArray($request->validated());
        $user = $this->authService->login($dto);
        $token = $this->authService->createToken($user);

        return UserResource::make($user)->additional(['token' => $token]);
    }

    public function me(Request $request): UserResource
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return UserResource::make($user);
    }
}
