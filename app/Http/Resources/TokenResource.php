<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property string $token
 * @property string $token_type
 * @property int $expires_in
 */
class TokenResource extends JsonResource
{
    /**
     * @param  array{token: string, token_type: string, expires_in: int}  $resource
     */
    public function __construct(array $resource)
    {
        parent::__construct((object) $resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->token,
            'token_type' => $this->token_type,
            'expires_in' => $this->expires_in,
        ];
    }
}
