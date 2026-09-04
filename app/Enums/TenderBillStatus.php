<?php

namespace App\Enums;

enum TenderBillStatus: string
{
    case DRAFT = 'draft';
    case GENERATED = 'generated';
    case APPROVED = 'approved';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';
}
