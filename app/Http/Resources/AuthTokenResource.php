<?php

namespace App\Http\Resources;

use App\DTO\TokenDTO;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property User $resource
 *
 * @mixin User
 */
class AuthTokenResource extends JsonResource
{
    private TokenDTO $tokenData;

    public function __construct(User $user, TokenDTO $tokenData)
    {
        parent::__construct($user);
        $this->tokenData = $tokenData;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => new UserResource($this->resource),
            'token' => $this->tokenData->token,
            'token_type' => $this->tokenData->tokenType,
            'expires_in' => $this->tokenData->expiresIn,
        ];
    }
}
