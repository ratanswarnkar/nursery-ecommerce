<?php

namespace App\Enums;

enum StockReservationStatus: string
{
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case EXPIRED = 'expired';
    case RELEASED = 'released';
}
