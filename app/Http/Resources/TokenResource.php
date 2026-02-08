<?php

namespace App\Http\Resources;

use App\DTO\TokenDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property TokenDTO $resource
 *
 * @mixin TokenDTO
 */
class TokenResource extends JsonResource
{
    public function __construct(TokenDTO $resource)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->resource->token,
            'token_type' => $this->resource->tokenType,
            'expires_in' => $this->resource->expiresIn,
        ];
    }
}
