<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Member;
use App\Repositories\MemberRepository;
use App\Services\VerificationService;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Psr\Log\LoggerInterface;
use RuntimeException;

class VerificationServiceTest extends TestCase
{
    /**
     * 發送驗證連結：會員不存在時，仍回 200 並記錄 warning（防帳號枚舉）。
     */
    public function test_send_email_verification_link_returns_success_when_member_not_found(): void
    {
        $memberRepository = $this->createMock(MemberRepository::class);
        $memberRepository->expects($this->once())
            ->method('first')
            ->with(['email', 'missing@example.com'])
            ->willReturn(null);

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->never())->method('pull');

        $config = $this->createMock(ConfigRepository::class);
        $config->expects($this->never())->method('get');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with('嘗試發送電子郵件驗證連結，但找不到對應的會員資料', ['email' => 'missing@example.com']);

        $service = new VerificationService($memberRepository, $cache, $config, $logger);

        $result = $service->sendEmailVerificationLink('missing@example.com');

        $this->assertSame(200, $result['status']);
        $this->assertSame('電子郵件驗證連結發送成功。', $result['message']);
    }

    /**
     * 發送驗證連結：應清除舊 token 並建立新 token 映射且發送通知。
     */
    public function test_send_email_verification_link_replaces_old_token_and_notifies_member(): void
    {
        $member = new class extends Member {
            public bool $notified = false;

            public function notify($instance): void
            {
                $this->notified = true;
            }
        };
        $member->id = 1001;
        $member->email = 'member1001@example.com';

        $memberRepository = $this->createMock(MemberRepository::class);
        $memberRepository->expects($this->once())
            ->method('first')
            ->with(['email', 'member1001@example.com'])
            ->willReturn($member);

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('pull')
            ->with('email_verify:member:1001')
            ->willReturn('old-token-hash');
        $cache->expects($this->once())
            ->method('forget')
            ->with('email_verify:token:old-token-hash')
            ->willReturn(true);
        $cache->expects($this->exactly(2))
            ->method('put')
            ->with(
                $this->callback(fn (string $key): bool => str_starts_with($key, 'email_verify:')),
                $this->callback(fn (mixed $value): bool => is_int($value) || (is_string($value) && strlen($value) === 64)),
                $this->anything(),
            )
            ->willReturn(true);

        $config = $this->createMock(ConfigRepository::class);
        $config->expects($this->once())
            ->method('get')
            ->with('services.frontend_url')
            ->willReturn('https://frontend.example.com');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');
        $logger->expects($this->never())->method('warning');

        $service = new VerificationService($memberRepository, $cache, $config, $logger);

        $result = $service->sendEmailVerificationLink('member1001@example.com');

        $this->assertSame(200, $result['status']);
        $this->assertSame('電子郵件驗證連結發送成功。', $result['message']);
        $this->assertTrue($member->notified);
    }

    /**
     * 發送驗證連結：token 快取儲存失敗時應回 500 並清理快取與記錄錯誤。
     */
    public function test_send_email_verification_link_returns_500_when_cache_put_fails(): void
    {
        $member = new Member();
        $member->id = 1002;
        $member->email = 'member1002@example.com';

        $memberRepository = $this->createMock(MemberRepository::class);
        $memberRepository->expects($this->once())
            ->method('first')
            ->with(['email', 'member1002@example.com'])
            ->willReturn($member);

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('pull')
            ->with('email_verify:member:1002')
            ->willReturn(null);
        $cache->expects($this->exactly(2))
            ->method('put')
            ->with(
                $this->callback(fn (string $key): bool => str_starts_with($key, 'email_verify:')),
                $this->callback(fn (mixed $value): bool => is_int($value) || (is_string($value) && strlen($value) === 64)),
                $this->anything(),
            )
            ->willReturnOnConsecutiveCalls(false, true);
        $cache->expects($this->exactly(2))
            ->method('forget')
            ->with($this->callback(fn (string $key): bool => str_starts_with($key, 'email_verify:')))
            ->willReturn(true);

        $config = $this->createMock(ConfigRepository::class);
        $config->expects($this->never())->method('get');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('電子郵件驗證連結發送失敗', $this->isType('array'));

        $service = new VerificationService($memberRepository, $cache, $config, $logger);

        $result = $service->sendEmailVerificationLink('member1002@example.com');

        $this->assertSame(500, $result['status']);
        $this->assertSame('電子郵件驗證連結發送失敗，請稍後再試。', $result['message']);
    }

    /**
     * 發送驗證連結：前端 URL 缺失時應回 500 並清理快取與記錄錯誤。
     */
    public function test_send_email_verification_link_returns_500_when_frontend_url_missing(): void
    {
        $member = new class extends Member {
            public bool $notified = false;

            public function notify($instance): void
            {
                $this->notified = true;
            }
        };
        $member->id = 1003;
        $member->email = 'member1003@example.com';

        $memberRepository = $this->createMock(MemberRepository::class);
        $memberRepository->expects($this->once())
            ->method('first')
            ->with(['email', 'member1003@example.com'])
            ->willReturn($member);

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('pull')
            ->with('email_verify:member:1003')
            ->willReturn(null);
        $cache->expects($this->exactly(2))
            ->method('put')
            ->with(
                $this->callback(fn (string $key): bool => str_starts_with($key, 'email_verify:')),
                $this->callback(fn (mixed $value): bool => is_int($value) || (is_string($value) && strlen($value) === 64)),
                $this->anything(),
            )
            ->willReturn(true);
        $cache->expects($this->exactly(2))
            ->method('forget')
            ->with($this->callback(fn (string $key): bool => str_starts_with($key, 'email_verify:')))
            ->willReturn(true);

        $config = $this->createMock(ConfigRepository::class);
        $config->expects($this->once())
            ->method('get')
            ->with('services.frontend_url')
            ->willReturn('');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('電子郵件驗證連結發送失敗', $this->isType('array'));

        $service = new VerificationService($memberRepository, $cache, $config, $logger);

        $result = $service->sendEmailVerificationLink('member1003@example.com');

        $this->assertSame(500, $result['status']);
        $this->assertSame('電子郵件驗證連結發送失敗，請稍後再試。', $result['message']);
        $this->assertFalse($member->notified);
    }

    /**
     * 發送驗證連結：通知丟例外時應回 500 並清理快取與記錄錯誤。
     */
    public function test_send_email_verification_link_returns_500_when_notify_throws_exception(): void
    {
        $member = new class extends Member {
            public function notify($instance): void
            {
                throw new RuntimeException('notify failed');
            }
        };
        $member->id = 1004;
        $member->email = 'member1004@example.com';

        $memberRepository = $this->createMock(MemberRepository::class);
        $memberRepository->expects($this->once())
            ->method('first')
            ->with(['email', 'member1004@example.com'])
            ->willReturn($member);

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('pull')
            ->with('email_verify:member:1004')
            ->willReturn(null);
        $cache->expects($this->exactly(2))
            ->method('put')
            ->with(
                $this->callback(fn (string $key): bool => str_starts_with($key, 'email_verify:')),
                $this->callback(fn (mixed $value): bool => is_int($value) || (is_string($value) && strlen($value) === 64)),
                $this->anything(),
            )
            ->willReturn(true);
        $cache->expects($this->exactly(2))
            ->method('forget')
            ->with($this->callback(fn (string $key): bool => str_starts_with($key, 'email_verify:')))
            ->willReturn(true);

        $config = $this->createMock(ConfigRepository::class);
        $config->expects($this->once())
            ->method('get')
            ->with('services.frontend_url')
            ->willReturn('https://frontend.example.com');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('電子郵件驗證連結發送失敗', $this->isType('array'));

        $service = new VerificationService($memberRepository, $cache, $config, $logger);

        $result = $service->sendEmailVerificationLink('member1004@example.com');

        $this->assertSame(500, $result['status']);
        $this->assertSame('電子郵件驗證連結發送失敗，請稍後再試。', $result['message']);
    }

    /**
     * 驗證信 token 不存在或過期時應回 422。
     */
    public function test_verify_email_returns_422_when_token_not_found(): void
    {
        $memberRepository = $this->createMock(MemberRepository::class);
        $memberRepository->expects($this->never())->method('first');

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('pull')
            ->with('email_verify:token:' . hash('sha256', 'missing-token'))
            ->willReturn(null);

        $config = $this->createMock(ConfigRepository::class);
        $logger = $this->createMock(LoggerInterface::class);

        $service = new VerificationService($memberRepository, $cache, $config, $logger);

        $result = $service->verifyEmail('missing-token');

        $this->assertSame(422, $result['status']);
        $this->assertSame('驗證連結無效或已過期。', $result['message']);
    }

    /**
     * 驗證信 token 對應資料非數值時應回 422（陣列格式）。
     */
    public function test_verify_email_returns_422_when_member_id_is_not_numeric_in_array_data(): void
    {
        $token = 'array-bad-member-id-token';

        $memberRepository = $this->createMock(MemberRepository::class);
        $memberRepository->expects($this->never())->method('first');

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('pull')
            ->with('email_verify:token:' . hash('sha256', $token))
            ->willReturn(['member_id' => 'abc']);

        $config = $this->createMock(ConfigRepository::class);
        $logger = $this->createMock(LoggerInterface::class);

        $service = new VerificationService($memberRepository, $cache, $config, $logger);

        $result = $service->verifyEmail($token);

        $this->assertSame(422, $result['status']);
        $this->assertSame('驗證連結無效或已過期。', $result['message']);
    }

    /**
     * 驗證信 token 對應資料非數值時應回 422（純量格式）。
     */
    public function test_verify_email_returns_422_when_member_id_is_not_numeric_scalar(): void
    {
        $token = 'scalar-bad-member-id-token';

        $memberRepository = $this->createMock(MemberRepository::class);
        $memberRepository->expects($this->never())->method('first');

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('pull')
            ->with('email_verify:token:' . hash('sha256', $token))
            ->willReturn('abc');

        $config = $this->createMock(ConfigRepository::class);
        $logger = $this->createMock(LoggerInterface::class);

        $service = new VerificationService($memberRepository, $cache, $config, $logger);

        $result = $service->verifyEmail($token);

        $this->assertSame(422, $result['status']);
        $this->assertSame('驗證連結無效或已過期。', $result['message']);
    }

    /**
     * 驗證信 token 對應會員不存在時應回 500 並刪除 member 索引 key。
     */
    public function test_verify_email_returns_500_when_member_not_found(): void
    {
        $token = 'member-missing-token';
        $tokenHash = hash('sha256', $token);

        $memberRepository = $this->createMock(MemberRepository::class);
        $memberRepository->expects($this->once())
            ->method('first')
            ->with(['id', 2001])
            ->willReturn(null);

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('pull')
            ->with("email_verify:token:$tokenHash")
            ->willReturn(2001);
        $cache->expects($this->once())
            ->method('forget')
            ->with('email_verify:member:2001')
            ->willReturn(true);

        $config = $this->createMock(ConfigRepository::class);
        $logger = $this->createMock(LoggerInterface::class);

        $service = new VerificationService($memberRepository, $cache, $config, $logger);

        $result = $service->verifyEmail($token);

        $this->assertSame(500, $result['status']);
        $this->assertSame('驗證失敗，無法找到對應的會員資料。', $result['message']);
    }

    /**
     * 會員已完成驗證時應回 200 並刪除 member 索引 key。
     */
    public function test_verify_email_returns_200_when_already_verified(): void
    {
        $token = 'already-verified-token';
        $tokenHash = hash('sha256', $token);

        $member = new class extends Member {
            public $email_verified_at = '2026-05-17 00:00:00';
        };
        $member->id = 2002;

        $memberRepository = $this->createMock(MemberRepository::class);
        $memberRepository->expects($this->once())
            ->method('first')
            ->with(['id', 2002])
            ->willReturn($member);
        $memberRepository->expects($this->never())->method('update');

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('pull')
            ->with("email_verify:token:$tokenHash")
            ->willReturn(2002);
        $cache->expects($this->once())
            ->method('forget')
            ->with('email_verify:member:2002')
            ->willReturn(true);

        $config = $this->createMock(ConfigRepository::class);
        $logger = $this->createMock(LoggerInterface::class);

        $service = new VerificationService($memberRepository, $cache, $config, $logger);

        $result = $service->verifyEmail($token);

        $this->assertSame(200, $result['status']);
        $this->assertSame('電子郵件已完成驗證。', $result['message']);
    }

    /**
     * 驗證更新影響筆數為 0 時應回 500。
     */
    public function test_verify_email_returns_500_when_update_affects_zero_rows(): void
    {
        $token = 'update-zero-rows-token';
        $tokenHash = hash('sha256', $token);

        $member = new Member();
        $member->id = 2003;
        $member->email_verified_at = null;

        $memberRepository = $this->createMock(MemberRepository::class);
        $memberRepository->expects($this->once())
            ->method('first')
            ->with(['id', 2003])
            ->willReturn($member);
        $memberRepository->expects($this->once())
            ->method('update')
            ->with(['id', 2003], $this->isType('array'))
            ->willReturn(0);

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('pull')
            ->with("email_verify:token:$tokenHash")
            ->willReturn(['member_id' => 2003]);
        $cache->expects($this->never())->method('forget');

        $config = $this->createMock(ConfigRepository::class);
        
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with(
                '電子郵件驗證失敗：更新驗證狀態時發生例外',
                $this->callback(function (array $context): bool {
                    return ($context['member_id'] ?? null) === 2003
                        && ($context['exception'] ?? null) instanceof RuntimeException
                        && (($context['exception']?->getMessage() ?? '') === '電子郵件驗證失敗：驗證狀態未更新');
                })
            );

        $service = new VerificationService($memberRepository, $cache, $config, $logger);

        $result = $service->verifyEmail($token);

        $this->assertSame(500, $result['status']);
        $this->assertSame('驗證失敗，請稍後再試。', $result['message']);
    }

    /**
     * 驗證更新時若拋出例外，應回 500 並記錄錯誤。
     */
    public function test_verify_email_returns_500_when_update_throws_exception(): void
    {
        $token = 'update-throws-exception-token';
        $tokenHash = hash('sha256', $token);

        $member = new Member();
        $member->id = 2005;
        $member->email_verified_at = null;

        $memberRepository = $this->createMock(MemberRepository::class);
        $memberRepository->expects($this->once())
            ->method('first')
            ->with(['id', 2005])
            ->willReturn($member);
        $memberRepository->expects($this->once())
            ->method('update')
            ->with(['id', 2005], $this->isType('array'))
            ->willThrowException(new RuntimeException('db update failed'));

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('pull')
            ->with("email_verify:token:$tokenHash")
            ->willReturn(2005);
        $cache->expects($this->never())->method('forget');

        $config = $this->createMock(ConfigRepository::class);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with(
                '電子郵件驗證失敗：更新驗證狀態時發生例外',
                $this->callback(function (array $context): bool {
                    return ($context['member_id'] ?? null) === 2005
                        && ($context['exception'] ?? null) instanceof RuntimeException;
                })
            );

        $service = new VerificationService($memberRepository, $cache, $config, $logger);

        $result = $service->verifyEmail($token);

        $this->assertSame(500, $result['status']);
        $this->assertSame('驗證失敗，請稍後再試。', $result['message']);
    }

    /**
     * 驗證成功時應更新 email_verified_at、刪除 member 索引 key，並回 200。
     */
    public function test_verify_email_returns_200_when_verification_succeeds(): void
    {
        $token = 'verify-success-token';
        $tokenHash = hash('sha256', $token);

        $member = new Member();
        $member->id = 2004;
        $member->email_verified_at = null;

        $memberRepository = $this->createMock(MemberRepository::class);
        $memberRepository->expects($this->once())
            ->method('first')
            ->with(['id', 2004])
            ->willReturn($member);
        $memberRepository->expects($this->once())
            ->method('update')
            ->with(['id', 2004], $this->isType('array'))
            ->willReturn(1);

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('pull')
            ->with("email_verify:token:$tokenHash")
            ->willReturn(2004);
        $cache->expects($this->once())
            ->method('forget')
            ->with('email_verify:member:2004')
            ->willReturn(true);

        $config = $this->createMock(ConfigRepository::class);
        $logger = $this->createMock(LoggerInterface::class);

        $service = new VerificationService($memberRepository, $cache, $config, $logger);

        $result = $service->verifyEmail($token);

        $this->assertSame(200, $result['status']);
        $this->assertSame('電子郵件驗證成功。', $result['message']);
    }
}
