<?php

namespace App\Services;

use App\DTO\LoginDTO;
use App\DTO\RegisterUserDTO;
use App\DTO\TokenDTO;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function register(RegisterUserDTO $data): User
    {
        return User::create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => $data->password,
        ]);
    }

    public function login(LoginDTO $data): ?TokenDTO
    {
        /** @var string|false $token */
        $token = JWTAuth::attempt([
            'email' => $data->email,
            'password' => $data->password,
        ]);

        if (! $token) {
            return null;
        }

        return new TokenDTO(
            token: $token,
            tokenType: 'bearer',
            expiresIn: (int) config('jwt.ttl') * 60,
        );
    }

    public function tokenFromUser(User $user): TokenDTO
    {
        /** @var string $token */
        $token = JWTAuth::fromUser($user);

        return new TokenDTO(
            token: $token,
            tokenType: 'bearer',
            expiresIn: (int) config('jwt.ttl') * 60,
        );
    }
}
