<?php

namespace App\DTO;

readonly class CreateImageFileDTO
{
    public function __construct(
        public string $contentHash,
        public string $storagePath,
        public string $thumbnailPath,
        public string $mimeType,
        public int $size,
        public int $width,
        public int $height,
    ) {}
}
