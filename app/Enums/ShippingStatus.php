<?php

namespace App\Enums;

enum ShippingStatus: string
{
    case UNFULFILLED = 'unfulfilled';
    case PARTIALLY_FULFILLED = 'partially_fulfilled';
    case FULFILLED = 'fulfilled';
    case RETURNED = 'returned';
}
