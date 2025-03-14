<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\BadCurrencyException;
use App\Domain\Exception\NegativeAmountException;
use App\Domain\Exception\NoFundsException;

readonly class Money
{
    public function __construct(
        private int      $amount,
        private Currency $currency
    ) {
        if ($amount < 0) {
            throw new NegativeAmountException();
        }
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function add(Money $other): Money
    {
        $this->assertSameCurrency($other);
        return new Money($this->amount + $other->amount, $this->currency);
    }

    public function subtract(Money $other): Money
    {
        $this->assertSameCurrency($other);
        if ($this->amount < $other->amount) {
            throw new NoFundsException();
        }
        return new Money($this->amount - $other->amount, $this->currency);
    }

    private function assertSameCurrency(Money $other): void
    {
        if (!$this->currency->equals($other->currency)) {
            throw new BadCurrencyException();
        }
    }
}
