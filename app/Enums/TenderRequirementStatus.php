<?php

namespace App\Enums;

enum TenderRequirementStatus: string
{
    case RECEIVED = 'received';
    case UNDER_REVIEW = 'under_review';
    case PROCESSING = 'processing';
    case FULFILLED = 'fulfilled';
    case CANCELLED = 'cancelled';
}
