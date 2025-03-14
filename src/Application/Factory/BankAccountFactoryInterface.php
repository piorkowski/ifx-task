<?php

declare(strict_types=1);

namespace App\Application\Factory;

use App\Domain\BankAccount;

interface BankAccountFactoryInterface
{
    public function create(string $currencyCode): BankAccount;
}
