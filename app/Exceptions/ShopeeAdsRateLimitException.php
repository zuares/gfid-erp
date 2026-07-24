<?php

namespace App\Exceptions;

use RuntimeException;

class ShopeeAdsRateLimitException extends RuntimeException
{
    public readonly int $retryAfter;

    public function __construct(
        int $retryAfter = 300,
        string $message = 'Shopee Ads API rate limit reached'
    ) {
        $this->retryAfter = $retryAfter;
        parent::__construct($message, 429);
    }
}
