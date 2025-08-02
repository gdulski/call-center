<?php

namespace App\Entity;

enum UserRole: string
{
    case AGENT = 'Agent';
    case MANAGER = 'Manager';
} 