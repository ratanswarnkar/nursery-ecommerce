<?php

namespace App\Enums;

enum TenderRequirementSourceType: string
{
    case MANUAL = 'manual';
    case EXCEL_IMPORT = 'excel_import';
    case DOCUMENT_EXTRACTION = 'document_extraction';
}
