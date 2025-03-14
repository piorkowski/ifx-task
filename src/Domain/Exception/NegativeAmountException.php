<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use Throwable;

class NegativeAmountException extends \DomainException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Amount cannot be negative', 0, $previous);
    }
}
