<?php

namespace App\Enum;

enum UserRole: string
{
    case AGENT = 'Agent';
    case MANAGER = 'Manager';
}