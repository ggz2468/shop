<?php

namespace Tests\Unit;

use App\Repositories\MemberRepository;
use App\Services\MemberService;
use App\Services\VerificationService;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class MemberServiceTest extends TestCase
{
    /**
     * 註冊成功且驗證信寄送成功時應回傳 201。
     */
    public function test_register_returns_201_when_member_created_and_email_verification_sent(): void
    {
        $payload = $this->validRegisterPayload();

        $memberRepository = $this->createMock(MemberRepository::class);
        $memberRepository->expects($this->once())
            ->method('create')
            ->with($payload)
            ->willReturn(null);

        $verificationService = $this->createMock(VerificationService::class);
        $verificationService->expects($this->once())
            ->method('sendEmailVerificationLink')
            ->with($payload['email'])
            ->willReturn([
                'status' => 200,
                'message' => '電子郵件驗證連結發送成功。',
            ]);

        $db = $this->createMock(ConnectionInterface::class);
        $db->expects($this->once())
            ->method('transaction')
            ->willReturnCallback(fn (callable $callback) => $callback());

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $service = new MemberService($memberRepository, $verificationService, $db, $logger);

        $result = $service->register($payload);

        $this->assertSame(201, $result['status']);
        $this->assertSame('會員註冊成功。', $result['message']);
    }

    /**
     * 註冊成功但驗證信寄送失敗時，應回傳降級成功訊息並記錄錯誤。
     */
    public function test_register_returns_201_with_degraded_message_when_email_verification_failed(): void
    {
        $payload = $this->validRegisterPayload();

        $memberRepository = $this->createMock(MemberRepository::class);
        $memberRepository->expects($this->once())
            ->method('create')
            ->with($payload)
            ->willReturn(null);

        $verificationService = $this->createMock(VerificationService::class);
        $verificationService->expects($this->once())
            ->method('sendEmailVerificationLink')
            ->with($payload['email'])
            ->willReturn([
                'status' => 500,
                'message' => '電子郵件驗證連結發送失敗，請稍後再試。',
            ]);

        $db = $this->createMock(ConnectionInterface::class);
        $db->expects($this->once())
            ->method('transaction')
            ->willReturnCallback(fn (callable $callback) => $callback());

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('會員註冊成功，但電子郵件驗證連結發送失敗', [
                'email' => $payload['email'],
                'error' => '電子郵件驗證連結發送失敗，請稍後再試。',
            ]);

        $service = new MemberService($memberRepository, $verificationService, $db, $logger);

        $result = $service->register($payload);

        $this->assertSame(201, $result['status']);
        $this->assertSame('會員註冊成功，但電子郵件驗證連結發送失敗，請稍後重試。', $result['message']);
    }

    /**
     * 會員註冊：唯一鍵衝突時應回傳對應欄位訊息。
     */
    #[DataProvider('uniqueConstraintMessageProvider')]
    public function test_register_returns_409_when_unique_constraint_violated(string $indexName, string $expectedMessage): void
    {
        $payload = $this->validRegisterPayload();
        $queryException = $this->makeUniqueViolationQueryException($indexName);

        $memberRepository = $this->createMock(MemberRepository::class);
        $memberRepository->expects($this->never())->method('create');

        $verificationService = $this->createMock(VerificationService::class);
        $verificationService->expects($this->never())->method('sendEmailVerificationLink');

        $db = $this->createMock(ConnectionInterface::class);
        $db->expects($this->once())
            ->method('transaction')
            ->willThrowException($queryException);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $service = new MemberService($memberRepository, $verificationService, $db, $logger);

        $result = $service->register($payload);

        $this->assertSame(409, $result['status']);
        $this->assertSame($expectedMessage, $result['message']);
    }

    /**
     * 會員註冊：非唯一鍵 QueryException 應回傳 500 並記錄錯誤。
     */
    public function test_register_returns_500_and_logs_error_when_query_exception_is_not_unique_violation(): void
    {
        $payload = $this->validRegisterPayload();

        $previous = new PDOException('syntax error near members table');
        $previous->errorInfo = ['42000', 1064, 'You have an error in your SQL syntax'];
        $queryException = new QueryException('mysql', 'insert into members (...) values (...)', [], $previous);

        $memberRepository = $this->createMock(MemberRepository::class);
        $memberRepository->expects($this->never())->method('create');

        $verificationService = $this->createMock(VerificationService::class);
        $verificationService->expects($this->never())->method('sendEmailVerificationLink');

        $db = $this->createMock(ConnectionInterface::class);
        $db->expects($this->once())
            ->method('transaction')
            ->willThrowException($queryException);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('會員註冊失敗', ['exception' => $queryException]);

        $service = new MemberService($memberRepository, $verificationService, $db, $logger);

        $result = $service->register($payload);

        $this->assertSame(500, $result['status']);
        $this->assertSame('會員註冊失敗，請稍後再試。', $result['message']);
    }

    /**
     * 會員註冊：發生一般例外時應回傳 500 並記錄錯誤。
     */
    public function test_register_returns_500_and_logs_error_when_transaction_throws_generic_exception(): void
    {
        $payload = $this->validRegisterPayload();
        $exception = new RuntimeException('unexpected failure');

        $memberRepository = $this->createMock(MemberRepository::class);
        $memberRepository->expects($this->never())->method('create');

        $verificationService = $this->createMock(VerificationService::class);
        $verificationService->expects($this->never())->method('sendEmailVerificationLink');

        $db = $this->createMock(ConnectionInterface::class);
        $db->expects($this->once())
            ->method('transaction')
            ->willThrowException($exception);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('會員註冊失敗', ['exception' => $exception]);

        $service = new MemberService($memberRepository, $verificationService, $db, $logger);

        $result = $service->register($payload);

        $this->assertSame(500, $result['status']);
        $this->assertSame('會員註冊失敗，請稍後再試。', $result['message']);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function uniqueConstraintMessageProvider(): array
    {
        return [
            'national id duplicated' => ['active_national_id_number', '身分證字號已被註冊。'],
            'email duplicated' => ['active_email', '電子郵件已被註冊。'],
            'phone duplicated' => ['active_phone', '手機號碼已被註冊。'],
            'unknown unique key duplicated' => ['some_other_unique_index', '註冊資料已被使用。'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function validRegisterPayload(): array
    {
        return [
            'national_id_number' => 'A123456789',
            'email' => 'member@example.com',
            'phone' => '0911222333',
            'password' => 'Password123',
        ];
    }

    private function makeUniqueViolationQueryException(string $indexName): QueryException
    {
        $previous = new PDOException("Duplicate entry for key '$indexName'");
        $previous->errorInfo = ['23000', 1062, "Duplicate entry for key '$indexName'"];

        return new QueryException('mysql', 'insert into members (...) values (...)', [], $previous);
    }
}
