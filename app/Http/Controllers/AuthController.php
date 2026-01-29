<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\AuthTokenResource;
use App\Http\Resources\TokenResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * @tags Auth
 */
class AuthController extends Controller
{
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

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        /** @var string $token */
        $token = JWTAuth::fromUser($user);

        return (new AuthTokenResource($user, $token))
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
        $credentials = $request->validated();

        /** @var string|false $token */
        $token = JWTAuth::attempt($credentials);

        if (! $token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return new TokenResource([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => (int) config('jwt.ttl') * 60,
        ]);
    }

    /**
     * Получить текущего пользователя.
     */
    public function me(): UserResource
    {
        /** @var User $user */
        $user = JWTAuth::user();

        return new UserResource($user);
    }
}
