<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\BadCurrencyException;
use App\Domain\Exception\DailyDebitLimitReachedException;
use App\Domain\Exception\NoFundsException;
use DateTimeImmutable;

class BankAccount
{
    private Money $balance;
    private array $dailyDebits = [];
    private const float TRANSACTION_FEE_PERCENTAGE = 0.005;
    private const int MAX_DAILY_DEBITS = 3;

    public function __construct(
        private readonly string $id,
        private readonly Currency $currency
    ) {
        $this->balance = new Money(0, $currency);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function credit(Money $money): void
    {
        if (!$this->balance->getCurrency()->equals($money->getCurrency())) {
            throw new BadCurrencyException();
        }
        $this->balance = $this->balance->add($money);
    }

    public function debit(Money $money, DateTimeImmutable $date): Money
    {
        $this->assertCanDebit($money, $date);

        $feeInCents = (int)round($money->getAmount() * self::TRANSACTION_FEE_PERCENTAGE);
        $totalAmount = new Money(
            $money->getAmount() + $feeInCents,
            $money->getCurrency()
        );

        $this->balance = $this->balance->subtract($totalAmount);
        $this->registerDebit($date);

        return $totalAmount;
    }

    public function getBalance(): Money
    {
        return $this->balance;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    private function assertCanDebit(Money $money, DateTimeImmutable $date): void
    {
        if (!$this->balance->getCurrency()->equals($money->getCurrency())) {
            throw new badCurrencyException();
        }

        $dateKey = $date->format('Y-m-d');
        $dailyCount = $this->dailyDebits[$dateKey] ?? 0;
        if ($dailyCount >= self::MAX_DAILY_DEBITS) {
            throw new DailyDebitLimitReachedException();
        }

        $fee = (int)round($money->getAmount() * self::TRANSACTION_FEE_PERCENTAGE);
        $totalAmount = new Money(
            $money->getAmount() + $fee,
            $money->getCurrency()
        );

        if ($this->balance->getAmount() < $totalAmount->getAmount()) {
            throw new NoFundsException();
        }
    }

    private function registerDebit(DateTimeImmutable $date): void
    {
        $dateKey = $date->format('Y-m-d');
        $this->dailyDebits[$dateKey] = ($this->dailyDebits[$dateKey] ?? 0) + 1;
    }
}
