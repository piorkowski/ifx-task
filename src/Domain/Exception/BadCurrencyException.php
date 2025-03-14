<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use Throwable;

class BadCurrencyException extends \DomainException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Currency mismatch', 0, $previous);
    }
}
