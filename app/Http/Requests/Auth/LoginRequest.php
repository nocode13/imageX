<?php

namespace App\Http\Requests\Auth;

use App\DTO\LoginDTO;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function toDTO(): LoginDTO
    {
        /** @var array{email: string, password: string} $validated */
        $validated = $this->validated();

        return LoginDTO::fromArray($validated);
    }
}
