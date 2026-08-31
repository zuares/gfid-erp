<?php

namespace App\Domain\Marketplace\Finance\Enums;

enum ComponentDirection: string
{
    case DEBIT = 'debit';
    case CREDIT = 'credit';
}
