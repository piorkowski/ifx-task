<?php

declare(strict_types=1);

namespace App\Application\Factory;

use App\Domain\BankAccount;
use App\Domain\Currency;

final class BankAccountFactory implements BankAccountFactoryInterface
{
    public function create(string $currencyCode): BankAccount
    {
        $accountId = uniqid('account_', true);
        $currency = new Currency($currencyCode);
        return new BankAccount($accountId, $currency);
    }
}
