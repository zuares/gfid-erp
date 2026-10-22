<?php

namespace App\Domain\Marketplace\Finance\Enums;

enum SettlementStatus: string
{
    case PENDING = 'pending';
    case WITHDRAWN = 'withdrawn';
    case PAYOUT_COMPLETED = 'payout_completed';
    case RECEIVED = 'received';
    case VOID = 'void';
    case UNKNOWN = 'unknown';
}
