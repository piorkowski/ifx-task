<?php
declare(strict_types=1);

namespace App\Tests\unit\Application\Service;

use App\Application\DTO\TransactionRequest;
use App\Application\DTO\TransactionResponse;
use App\Application\Factory\BankAccountFactoryInterface;
use App\Application\Service\BankAccountService;
use App\Domain\BankAccount;
use App\Domain\Currency;
use App\Application\Repository\BankAccountRepositoryInterface;
use App\Domain\Exception\BadCurrencyException;
use App\Domain\Exception\DailyDebitLimitReachedException;
use App\Domain\Exception\NoFundsException;
use App\Infrastructure\Service\BankAccountServiceInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BankAccountServiceTest extends TestCase
{
    private BankAccountServiceInterface $service;
    private BankAccountRepositoryInterface|MockObject $repository;
    private BankAccountFactoryInterface|MockObject $bankAccountFactory;
    private string $accountId;
    private Currency $usd;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(BankAccountRepositoryInterface::class);
        $this->bankAccountFactory = $this->createMock(BankAccountFactoryInterface::class);
        $this->service = new BankAccountService($this->repository, $this->bankAccountFactory);
        $this->accountId = 'test-account';
        $this->usd = new Currency('USD');
    }

    public function testOpenAccount(): void
    {
        $currencyCode = 'USD';

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(BankAccount::class));

        $this->service->openAccount($currencyCode);
    }

    public function testCreditIncreasesBalance(): void
    {
        $account = new BankAccount($this->accountId, $this->usd);
        $this->repository->method('findById')->with($this->accountId)->willReturn($account);
        $this->repository->expects($this->once())->method('save')->with($account);

        $request = new TransactionRequest(10000, 'USD', new DateTimeImmutable());
        $this->service->credit($this->accountId, $request);

        $this->assertEquals(10000, $this->service->getBalance($this->accountId));
    }

    public function testDebitWithSufficientFundsIncludesTransactionFee(): void
    {
        $account = new BankAccount($this->accountId, $this->usd);
        $this->repository->method('findById')->with($this->accountId)->willReturn($account);
        $this->repository->expects($this->exactly(2))->method('save')->with($account);

        $this->service->credit(
            $this->accountId,
            new TransactionRequest(100000, 'USD', new DateTimeImmutable())
        );

        $date = new DateTimeImmutable('2025-03-13');
        $request = new TransactionRequest(10000, 'USD', $date);
        $response = $this->service->debit($this->accountId, $request);

        $this->assertInstanceOf(TransactionResponse::class, $response);
        $this->assertEquals(10000, $response->amount);
        $this->assertEquals(50, $response->fee);
        $this->assertEquals('USD', $response->currencyCode);
        $this->assertEquals(89950, $response->remainingBalanceInCents);
    }

    public function testDebitWithInsufficientFundsThrowsException(): void
    {
        $account = new BankAccount($this->accountId, $this->usd);
        $this->repository->method('findById')->with($this->accountId)->willReturn($account);

        $this->expectException(NoFundsException::class);

        $request = new TransactionRequest(10000, 'USD', new DateTimeImmutable());
        $this->service->debit($this->accountId, $request);
    }

    public function testDebitWithDifferentCurrencyThrowsException(): void
    {
        $account = new BankAccount($this->accountId, $this->usd);
        $this->repository->method('findById')->with($this->accountId)->willReturn($account);
        $this->repository->expects($this->once())->method('save')->with($account);

        $this->service->credit(
            $this->accountId,
            new TransactionRequest(100000, 'USD', new DateTimeImmutable())
        );

        $this->expectException(BadCurrencyException::class);

        $request = new TransactionRequest(10000, 'EUR', new DateTimeImmutable());
        $this->service->debit($this->accountId, $request);
    }

    public function testExceedingDailyDebitLimitThrowsException(): void
    {
        $account = new BankAccount($this->accountId, $this->usd);
        $this->repository->method('findById')->with($this->accountId)->willReturn($account);
        $this->repository->expects($this->exactly(4))->method('save')->with($account);

        $date = new DateTimeImmutable('2025-03-13');
        $this->service->credit(
            $this->accountId,
            new TransactionRequest(100000, 'USD', $date)
        );

        $request = new TransactionRequest(10000, 'USD', $date);
        $this->service->debit($this->accountId, $request);
        $this->service->debit($this->accountId, $request);
        $this->service->debit($this->accountId, $request);

        $this->expectException(DailyDebitLimitReachedException::class);
        $this->service->debit($this->accountId, $request);
    }

    public function testDebitExactBalanceFailsDueToFee(): void
    {
        $account = new BankAccount($this->accountId, $this->usd);
        $this->repository->method('findById')->with($this->accountId)->willReturn($account);
        $this->repository->expects($this->once())->method('save')->with($account);

        $this->service->credit(
            $this->accountId,
            new TransactionRequest(10000, 'USD', new DateTimeImmutable())
        );

        $this->expectException(NoFundsException::class);
        $request = new TransactionRequest(10000, 'USD', new DateTimeImmutable());
        $this->service->debit($this->accountId, $request); // 10000 + 50 > 10000
    }

    public function testDebitLimitResetsAcrossDays(): void
    {
        $account = new BankAccount($this->accountId, $this->usd);
        $this->repository->method('findById')->with($this->accountId)->willReturn($account);
        $this->repository->expects($this->exactly(5))->method('save')->with($account);

        $date1 = new DateTimeImmutable('2025-03-13');
        $this->service->credit(
            $this->accountId,
            new TransactionRequest(100000, 'USD', $date1)
        );

        $request = new TransactionRequest(10000, 'USD', $date1);
        $this->service->debit($this->accountId, $request);
        $this->service->debit($this->accountId, $request);
        $this->service->debit($this->accountId, $request);

        $date2 = new DateTimeImmutable('2025-03-14');
        $requestNextDay = new TransactionRequest(10000, 'USD', $date2);
        $response = $this->service->debit($this->accountId, $requestNextDay);

        $this->assertEquals(10000, $response->amount);
    }

    public function testDebitMinimalAmountSucceeds(): void
    {
        $account = new BankAccount($this->accountId, $this->usd);
        $this->repository->method('findById')->with($this->accountId)->willReturn($account);
        $this->repository->expects($this->exactly(2))->method('save')->with($account);

        $this->service->credit(
            $this->accountId,
            new TransactionRequest(100, 'USD', new DateTimeImmutable())
        );

        $request = new TransactionRequest(1, 'USD', new DateTimeImmutable());
        $response = $this->service->debit($this->accountId, $request);

        $this->assertEquals(1, $response->amount);
        $this->assertEquals(0, $response->fee);
        $this->assertEquals(99, $response->remainingBalanceInCents);
    }

    public function testCreditMaxIntDoesNotOverflow(): void
    {
        $account = new BankAccount($this->accountId, $this->usd);
        $this->repository->method('findById')->with($this->accountId)->willReturn($account);
        $this->repository->expects($this->once())->method('save')->with($account);

        $maxInt = PHP_INT_MAX;
        $request = new TransactionRequest($maxInt, 'USD', new DateTimeImmutable());
        $this->service->credit($this->accountId, $request);

        $this->assertEquals($maxInt, $this->service->getBalance($this->accountId));
    }
}
