<?php

namespace App\Enums;

enum TenderPricingMode: string
{
    case ITEM_WISE = 'item_wise';
    case PERCENTAGE_OVERALL = 'percentage_overall';
    case HYBRID = 'hybrid';
}
