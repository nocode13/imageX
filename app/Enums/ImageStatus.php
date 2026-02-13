<?php

namespace App\Enums;

enum ImageStatus: string
{
    case PENDING = 'PENDING';
    case READY = 'READY';
    case FAILED = 'FAILED';
}
