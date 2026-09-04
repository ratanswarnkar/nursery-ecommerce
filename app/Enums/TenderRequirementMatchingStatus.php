<?php

namespace App\Enums;

enum TenderRequirementMatchingStatus: string
{
    case PENDING = 'pending';
    case MATCHED = 'matched';
    case UNMATCHED = 'unmatched';
    case MANUAL_OVERRIDE = 'manual_override';
}
