<?php

namespace App\Enums;

enum AddressType: string
{
    case HOME = 'home';
    case WORK = 'work';
    case OTHER = 'other';
}
