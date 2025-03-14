<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use Throwable;

class DailyDebitLimitReachedException extends \DomainException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Daily debit limit reached', 0, $previous);
    }
}
