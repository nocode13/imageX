<?php

namespace App\Enums;

enum ImageStatus: string
{
    case Pending = 'pending';
    case Ready = 'ready';
    case Failed = 'failed';
}
