<?php

namespace App\Http\Requests\Auth;

use App\DTO\RegisterUserDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function toDTO(): RegisterUserDTO
    {
        /** @var array{name: string, email: string, password: string} $validated */
        $validated = $this->validated();

        return RegisterUserDTO::fromArray($validated);
    }
}
