<?php

namespace App\Enum;

enum ScheduleStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case FINALIZED = 'finalized';
}