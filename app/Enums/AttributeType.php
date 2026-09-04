<?php

namespace App\Enums;

enum AttributeType: string
{
    case SELECT = 'select';
    case MULTISELECT = 'multiselect';
    case BOOLEAN = 'boolean';
    case TEXT = 'text';
    case NUMBER = 'number';
}
