<?php

namespace App\DTO;

use Illuminate\Http\UploadedFile;

readonly class UploadImageDTO
{
    public function __construct(
        public UploadedFile $image,
    ) {}

    /**
     * @param array{image: UploadedFile} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            image: $data['image'],
        );
    }
}
