<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use Throwable;

class BadCurrencyCodeException extends \DomainException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Invalid currency code', 0, $previous);
    }
}
