<?php

namespace App\Exceptions;

use RuntimeException;

class ImageDownloadException extends RuntimeException
{
    public function __construct(string $path)
    {
        parent::__construct("Failed to download temp file: {$path}");
    }
}
