<?php

namespace App\Enums;

enum SchoolRole: string
{
    case ADMIN_ESCOLAR = 'ADMIN_ESCOLAR';
    case DIRECTOR = 'DIRECTOR';
    case CONTROL_ESCOLAR = 'CONTROL_ESCOLAR';
    case PROFESOR = 'PROFESOR';
    case TUTOR = 'TUTOR';
}
