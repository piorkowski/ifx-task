<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use Throwable;

class NoFundsException extends \DomainException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Insufficient funds to process the transaction', 0, $previous);
    }
}
