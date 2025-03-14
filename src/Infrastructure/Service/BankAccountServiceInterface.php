<?php
declare(strict_types=1);


namespace App\Infrastructure\Service;


use App\Application\DTO\TransactionRequest;
use App\Application\DTO\TransactionResponse;

interface BankAccountServiceInterface
{
    public function openAccount(string $currencyCode): string;
    public function credit(string $accountId, TransactionRequest $request): void;
    public function debit(string $accountId, TransactionRequest $request): TransactionResponse;
    public function getBalance(string $accountId): int;
}
