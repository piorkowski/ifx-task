<?php

declare(strict_types=1);

namespace App\Tests\unit\Application\Factory;

use App\Application\Factory\BankAccountFactory;
use App\Domain\BankAccount;
use App\Domain\Exception\BadCurrencyCodeException;
use PHPUnit\Framework\TestCase;

class BankAccountFactoryTest extends TestCase
{
    private BankAccountFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new BankAccountFactory();
    }

    public function testCreateAccountWithValidCurrency(): void
    {
        $account = $this->factory->create('USD');
        $this->assertInstanceOf(BankAccount::class, $account);
        $this->assertEquals('USD', $account->getCurrency()->code);
        $this->assertNotEmpty($account->getId());
    }

    public function testCreateAccountWithInvalidCurrency(): void
    {
        $this->expectException(BadCurrencyCodeException::class);
        $this->factory->create('INVALID');
    }

    public function testUniqueAccountIds(): void
    {
        $account1 = $this->factory->create('USD');
        $account2 = $this->factory->create('USD');
        $this->assertNotEquals($account1->getId(), $account2->getId());
    }
}
