<?php

namespace App\Services;

use App\DTO\LoginDTO;
use App\DTO\RegisterUserDTO;
use App\Models\User;
use App\Exceptions\InvalidCredentialsException;
use Illuminate\Support\Facades\Hash;
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

    public function login(LoginDTO $data): User
    {
        $user = User::where('email', $data->email)->first();

        if (! $user || ! Hash::check($data->password, $user->password)) {
            throw new InvalidCredentialsException();
        }

        return $user;
    }

    public function createToken(User $user): string
    {
        /** @var string $token */
        $token = JWTAuth::fromUser($user);

        return $token;
    }
}
