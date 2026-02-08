<?php

namespace App\DTO;

readonly class TokenDTO
{
    public function __construct(
        public string $token,
        public string $tokenType,
        public int $expiresIn,
    ) {}
}
