<?php

namespace App\Domain\Marketplace\Finance\Enums;

enum IncomeStatus: string
{
    case PENDING = 'pending';
    case TO_RELEASE = 'to_release';
    case RELEASED = 'released';
    case UNKNOWN = 'unknown';
}
