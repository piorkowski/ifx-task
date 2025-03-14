<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Application\Repository\BankAccountRepositoryInterface;
use App\Domain\BankAccount;

class InMemoryBankAccountRepository implements BankAccountRepositoryInterface
{
    private array $accounts = [];

    public function findById(string $id): ?BankAccount
    {
        return $this->accounts[$id] ?? null;
    }

    public function save(BankAccount $account): void
    {
        $this->accounts[$account->getId()] = $account;
    }
}
