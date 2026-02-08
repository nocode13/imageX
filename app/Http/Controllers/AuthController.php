<?php

namespace App\Http\Controllers;

use App\DTO\LoginDTO;
use App\DTO\RegisterUserDTO;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\AuthTokenResource;
use App\Http\Resources\TokenResource;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Auth
 */
class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    /**
     * Регистрация нового пользователя.
     *
     * @unauthenticated
     *
     * @response 201 AuthTokenResource
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        /** @var array{name: string, email: string, password: string} $validated */
        $validated = $request->validated();

        $data = new RegisterUserDTO(
            name: $validated['name'],
            email: $validated['email'],
            password: $validated['password'],
        );

        $user = $this->authService->register($data);
        $tokenData = $this->authService->tokenFromUser($user);

        return (new AuthTokenResource($user, $tokenData))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Вход в систему.
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request): TokenResource|JsonResponse
    {
        /** @var array{email: string, password: string} $validated */
        $validated = $request->validated();

        $data = new LoginDTO(
            email: $validated['email'],
            password: $validated['password'],
        );

        $tokenData = $this->authService->login($data);

        if (! $tokenData) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return new TokenResource($tokenData);
    }

    /**
     * Получить текущего пользователя.
     */
    public function me(Request $request): UserResource
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return new UserResource($user);
    }
}
