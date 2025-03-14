<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\BadCurrencyCodeException;

readonly class Currency
{
    private const array AVAILABLE_CURRENCY_CODES = ['USD', 'EUR', 'PLN'];

    public function __construct(
        public string $code
    ) {
        if (!in_array($code, self::AVAILABLE_CURRENCY_CODES)) {
            throw new BadCurrencyCodeException();
        }
    }

    public function equals(Currency $other): bool
    {
        return $this->code === $other->code;
    }
}
