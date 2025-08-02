<?php

namespace App\Entity;

enum ScheduleStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case FINALIZED = 'finalized';
}