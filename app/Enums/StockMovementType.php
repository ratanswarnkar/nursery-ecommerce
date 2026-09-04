<?php

namespace App\Enums;

enum StockMovementType: string
{
    case INBOUND = 'inbound';
    case OUTBOUND = 'outbound';
    case RESERVATION = 'reservation';
    case RELEASE = 'release';
    case ADJUSTMENT = 'adjustment';
}
