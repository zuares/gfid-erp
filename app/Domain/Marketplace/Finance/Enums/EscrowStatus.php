<?php

namespace App\Domain\Marketplace\Finance\Enums;

enum EscrowStatus: string
{
    case PENDING = 'pending';
    case SYNCED = 'synced';
    case FINALIZED = 'finalized';
    case UNKNOWN = 'unknown';
}
