<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ImageNotFoundException extends HttpException
{
    public function __construct()
    {
        parent::__construct(404, 'Image not found');
    }
}
