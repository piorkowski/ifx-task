<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\DTO\TransactionRequest;
use App\Application\DTO\TransactionResponse;
use App\Application\Factory\BankAccountFactoryInterface;
use App\Application\Repository\BankAccountRepositoryInterface;
use App\Domain\BankAccount;
use App\Domain\Currency;
use App\Domain\Money;
use App\Infrastructure\Service\BankAccountServiceInterface;
use RuntimeException;

readonly class BankAccountService implements BankAccountServiceInterface
{
    public function __construct(
        private BankAccountRepositoryInterface $repository,
        private BankAccountFactoryInterface $bankAccountFactory,
    ) {
    }

    public function openAccount(string $currencyCode): string
    {
        $account = $this->bankAccountFactory->create($currencyCode);
        $this->repository->save($account);
        return $account->getId();
    }

    public function credit(string $accountId, TransactionRequest $request): void
    {
        $account = $this->getAccount($accountId);
        $money = new Money(
            $request->amount,
            new Currency($request->currencyCode)
        );

        $account->credit($money);
        $this->repository->save($account);
    }

    public function debit(string $accountId, TransactionRequest $request): TransactionResponse
    {
        $account = $this->getAccount($accountId);
        $money = new Money(
            $request->amount,
            new Currency($request->currencyCode)
        );

        $result = $account->debit($money, $request->date);

        $this->repository->save($account);

        return new TransactionResponse(
            $money->getAmount(),
            $money->getCurrency()->code,
            $result->getAmount() - $money->getAmount(),
            $account->getBalance()->getAmount()
        );
    }

    public function getBalance(string $accountId): int
    {
        $account = $this->getAccount($accountId);
        return $account->getBalance()->getAmount();
    }

    private function getAccount(string $accountId): BankAccount
    {
        $account = $this->repository->findById($accountId);
        if ($account === null) {
            throw new RuntimeException("Account $accountId not found");
        }
        return $account;
    }
}
