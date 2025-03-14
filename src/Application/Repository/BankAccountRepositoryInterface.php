<?php

declare(strict_types=1);

namespace App\Application\Repository;

use App\Domain\BankAccount;

interface BankAccountRepositoryInterface
{
    public function findById(string $id): ?BankAccount;
    public function save(BankAccount $account): void;
}
