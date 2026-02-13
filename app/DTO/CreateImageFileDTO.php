<?php

namespace App\DTO;

final readonly class CreateImageFileDTO
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

    /**
     * @param array{contentHash: string, storagePath: string, thumbnailPath: string, mimeType: string, size: int, width: int, height: int} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            contentHash: $data['contentHash'],
            storagePath: $data['storagePath'],
            thumbnailPath: $data['thumbnailPath'],
            mimeType: $data['mimeType'],
            size: $data['size'],
            width: $data['width'],
            height: $data['height'],
        );
    }
}
