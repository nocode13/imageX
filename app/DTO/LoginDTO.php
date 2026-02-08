<?php

namespace App\DTO;

readonly class LoginDTO
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}
