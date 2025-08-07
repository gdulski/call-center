<?php

namespace App\Enum;

enum ScheduleStatus: string
{
    case DRAFT = 'draft';
    case GENERATED = 'generated';
    case PUBLISHED = 'published';
    case FINALIZED = 'finalized';
}