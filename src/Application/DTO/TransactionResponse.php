<?php

declare(strict_types=1);

namespace App\Application\DTO;

readonly class TransactionResponse
{
    public function __construct(
        public int    $amount,
        public string $currencyCode,
        public int    $fee,
        public int    $remainingBalanceInCents
    ) {
    }
}
