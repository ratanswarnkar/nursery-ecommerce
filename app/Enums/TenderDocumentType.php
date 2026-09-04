<?php

namespace App\Enums;

enum TenderDocumentType: string
{
    case SOQ_BOQ = 'soq_boq';
    case AWARDED_DOCUMENT = 'awarded_document';
    case RATE_SHEET = 'rate_sheet';
    case DEPARTMENT_REQUIREMENT = 'department_requirement';
    case SUPPORTING_DOCUMENT = 'supporting_document';
    case OTHER = 'other';
}
