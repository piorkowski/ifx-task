<?php

declare(strict_types=1);

namespace App\Application\DTO;

use DateTimeImmutable;

readonly class TransactionRequest
{
    public function __construct(
        public int               $amount,
        public string            $currencyCode,
        public DateTimeImmutable $date
    ) {
    }
}
