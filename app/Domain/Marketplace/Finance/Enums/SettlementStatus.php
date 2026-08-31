<?php

namespace App\Domain\Marketplace\Finance\Enums;

enum SettlementStatus: string
{
    case PENDING = 'pending';
    case RECEIVED = 'received';
    case VOID = 'void';
    case UNKNOWN = 'unknown';
}
