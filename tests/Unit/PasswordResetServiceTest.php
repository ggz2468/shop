<?php

namespace Tests\Unit;

use App\Models\Member;
use App\Repositories\MemberRepository;
use App\Services\PasswordResetService;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class PasswordResetServiceTest extends TestCase
{
    /**
     * 發送重設密碼連結：若會員不存在，仍回傳成功訊息（避免帳號枚舉）。
     */
    public function test_send_reset_link_returns_success_when_member_not_found(): void
    {
        $email = 'member@example.com';

        $memberRepository = $this->createMock(MemberRepository::class);
        $memberRepository->expects($this->once())
            ->method('first')
            ->with(['email', $email])
            ->willReturn(null);

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->never())->method('pull');

        $config = $this->createMock(ConfigRepository::class);
        $config->expects($this->never())->method('get');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $passwordResetService = new PasswordResetService($memberRepository, $cache, $config, $logger);

        $result = $passwordResetService->sendResetLink($email);

        $this->assertEquals([
            'status' => 200,
            'message' => '重設密碼連結已發送（如果該電子郵件存在）。',
        ], $result);
    }

    /**
     * 發送重設密碼連結：應清除舊 token 並建立新 token 映射。
     */
    public function test_send_reset_link_replaces_old_token_and_stores_new_mapping(): void
    {
        $member = new class extends Member
        {
            public bool $notified = false;

            public function notify($instance): void
            {
                $this->notified = true;
            }
        };
        $member->id = 77;
        $member->email = 'member@example.com';

        $memberRepository = $this->createMock(MemberRepository::class);
        $memberRepository->expects($this->once())
            ->method('first')
            ->with(['email', 'member@example.com'])
            ->willReturn($member);

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('pull')
            ->with('password_reset:member:77')
            ->willReturn('old-token-hash');
        $cache->expects($this->once())
            ->method('forget')
            ->with('password_reset:token:old-token-hash')
            ->willReturn(true);
        $cache->expects($this->exactly(2))
            ->method('put')
            ->with(
                $this->callback(fn (string $key): bool => str_starts_with($key, 'password_reset:')),
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

        $service = new PasswordResetService($memberRepository, $cache, $config, $logger);

        $result = $service->sendResetLink('member@example.com');

        $this->assertSame(200, $result['status']);
        $this->assertTrue($member->notified);
    }

    /**
     * 發送重設密碼連結：若通知失敗，應清除新建立的快取並回傳 500。
     */
    public function test_send_reset_link_cleans_cache_and_returns_500_when_notify_fails(): void
    {
        $member = new class extends Member
        {
            public function notify($instance): void
            {
                throw new RuntimeException('notify failed');
            }
        };
        $member->id = 91;
        $member->email = 'member91@example.com';

        $memberRepository = $this->createMock(MemberRepository::class);
        $memberRepository->expects($this->once())
            ->method('first')
            ->with(['email', 'member91@example.com'])
            ->willReturn($member);

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('pull')
            ->with('password_reset:member:91')
            ->willReturn(null);
        $cache->expects($this->exactly(2))
            ->method('put')
            ->with(
                $this->callback(fn (string $key): bool => str_starts_with($key, 'password_reset:')),
                $this->callback(fn (mixed $value): bool => is_int($value) || (is_string($value) && strlen($value) === 64)),
                $this->anything(),
            )
            ->willReturn(true);
        $cache->expects($this->exactly(2))
            ->method('forget')
            ->with($this->callback(fn (string $key): bool => str_starts_with($key, 'password_reset:')))
            ->willReturn(true);

        $config = $this->createMock(ConfigRepository::class);
        $config->expects($this->once())
            ->method('get')
            ->with('services.frontend_url')
            ->willReturn('https://frontend.example.com');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('發送重設密碼連結失敗', $this->isType('array'));

        $service = new PasswordResetService($memberRepository, $cache, $config, $logger);

        $result = $service->sendResetLink('member91@example.com');

        $this->assertSame(500, $result['status']);
        $this->assertSame('發送重設密碼連結失敗，請稍後再試。', $result['message']);
    }

    /**
     * 發送重設密碼連結：若 token 快取儲存失敗，應回傳 500 並清除已寫入索引。
     */
    public function test_send_reset_link_returns_500_when_cache_put_fails(): void
    {
        $member = new Member;
        $member->id = 66;
        $member->email = 'member66@example.com';

        $memberRepository = $this->createMock(MemberRepository::class);
        $memberRepository->expects($this->once())
            ->method('first')
            ->with(['email', 'member66@example.com'])
            ->willReturn($member);

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('pull')
            ->with('password_reset:member:66')
            ->willReturn(null);
        $cache->expects($this->exactly(2))
            ->method('put')
            ->with(
                $this->callback(fn (string $key): bool => str_starts_with($key, 'password_reset:')),
                $this->callback(fn (mixed $value): bool => is_int($value) || (is_string($value) && strlen($value) === 64)),
                $this->anything(),
            )
            ->willReturnOnConsecutiveCalls(false, true);
        $cache->expects($this->exactly(2))
            ->method('forget')
            ->with($this->callback(fn (string $key): bool => str_starts_with($key, 'password_reset:')))
            ->willReturn(true);

        $config = $this->createMock(ConfigRepository::class);
        $config->expects($this->never())->method('get');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('發送重設密碼連結失敗', $this->isType('array'));

        $service = new PasswordResetService($memberRepository, $cache, $config, $logger);

        $result = $service->sendResetLink('member66@example.com');

        $this->assertSame(500, $result['status']);
        $this->assertSame('發送重設密碼連結失敗，請稍後再試。', $result['message']);
    }

    /**
     * 發送重設密碼連結：若前端 URL 未設定，應回傳 500 並清除 token 快取。
     */
    public function test_send_reset_link_returns_500_when_frontend_url_missing(): void
    {
        $member = new class extends Member
        {
            public bool $notified = false;

            public function notify($instance): void
            {
                $this->notified = true;
            }
        };
        $member->id = 67;
        $member->email = 'member67@example.com';

        $memberRepository = $this->createMock(MemberRepository::class);
        $memberRepository->expects($this->once())
            ->method('first')
            ->with(['email', 'member67@example.com'])
            ->willReturn($member);

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('pull')
            ->with('password_reset:member:67')
            ->willReturn(null);
        $cache->expects($this->exactly(2))
            ->method('put')
            ->with(
                $this->callback(fn (string $key): bool => str_starts_with($key, 'password_reset:')),
                $this->callback(fn (mixed $value): bool => is_int($value) || (is_string($value) && strlen($value) === 64)),
                $this->anything(),
            )
            ->willReturn(true);
        $cache->expects($this->exactly(2))
            ->method('forget')
            ->with($this->callback(fn (string $key): bool => str_starts_with($key, 'password_reset:')))
            ->willReturn(true);

        $config = $this->createMock(ConfigRepository::class);
        $config->expects($this->once())
            ->method('get')
            ->with('services.frontend_url')
            ->willReturn('');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('發送重設密碼連結失敗', $this->isType('array'));

        $service = new PasswordResetService($memberRepository, $cache, $config, $logger);

        $result = $service->sendResetLink('member67@example.com');

        $this->assertSame(500, $result['status']);
        $this->assertSame('發送重設密碼連結失敗，請稍後再試。', $result['message']);
        $this->assertFalse($member->notified);
    }

    /**
     * 重設密碼：token 無效時應回傳 400。
     */
    public function test_reset_password_returns_400_when_token_invalid_or_expired(): void
    {
        $memberRepository = $this->createMock(MemberRepository::class);
        $memberRepository->expects($this->never())->method('first');

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('pull')
            ->with('password_reset:token:'.hash('sha256', 'invalid-token'))
            ->willReturn(null);

        $config = $this->createMock(ConfigRepository::class);
        $logger = $this->createMock(LoggerInterface::class);

        $service = new PasswordResetService($memberRepository, $cache, $config, $logger);

        $result = $service->resetPassword('invalid-token', 'member@example.com', 'NewPassword1');

        $this->assertSame(400, $result['status']);
        $this->assertSame('重設密碼失敗，請確認輸入的資訊是否正確。', $result['message']);
    }

    /**
     * 重設密碼：token 對應帳號與輸入 email 不一致時應回傳 400，並清除 member 索引 key。
     */
    public function test_reset_password_returns_400_when_email_not_match_token_member(): void
    {
        $token = 'valid-token';
        $tokenHash = hash('sha256', $token);

        $member = new Member;
        $member->id = 501;
        $member->email = 'correct@example.com';

        $memberRepository = $this->createMock(MemberRepository::class);
        $memberRepository->expects($this->once())
            ->method('first')
            ->with(['id', 501])
            ->willReturn($member);
        $memberRepository->expects($this->never())->method('updateByEloquentModel');

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('pull')
            ->with("password_reset:token:$tokenHash")
            ->willReturn(501);
        $cache->expects($this->once())
            ->method('forget')
            ->with('password_reset:member:501')
            ->willReturn(true);

        $config = $this->createMock(ConfigRepository::class);
        $logger = $this->createMock(LoggerInterface::class);

        $service = new PasswordResetService($memberRepository, $cache, $config, $logger);

        $result = $service->resetPassword($token, 'wrong@example.com', 'NewPassword1');

        $this->assertSame(400, $result['status']);
        $this->assertSame('重設密碼失敗，請確認輸入的資訊是否正確。', $result['message']);
    }

    /**
     * 重設密碼：token 對應不到會員資料時應回傳 400 並清除 member 索引 key。
     */
    public function test_reset_password_returns_400_when_member_not_found(): void
    {
        $token = 'valid-token-member-missing';
        $tokenHash = hash('sha256', $token);

        $memberRepository = $this->createMock(MemberRepository::class);
        $memberRepository->expects($this->once())
            ->method('first')
            ->with(['id', 777])
            ->willReturn(null);
        $memberRepository->expects($this->never())->method('updateByEloquentModel');

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('pull')
            ->with("password_reset:token:$tokenHash")
            ->willReturn(777);
        $cache->expects($this->once())
            ->method('forget')
            ->with('password_reset:member:777')
            ->willReturn(true);

        $config = $this->createMock(ConfigRepository::class);
        $logger = $this->createMock(LoggerInterface::class);

        $service = new PasswordResetService($memberRepository, $cache, $config, $logger);

        $result = $service->resetPassword($token, 'member777@example.com', 'NewPassword1');

        $this->assertSame(400, $result['status']);
        $this->assertSame('重設密碼失敗，請確認輸入的資訊是否正確。', $result['message']);
    }

    /**
     * 重設密碼：更新會員密碼失敗時應回傳 500 並記錄錯誤。
     */
    public function test_reset_password_returns_500_when_update_password_throws_exception(): void
    {
        $token = 'token-update-fails';
        $tokenHash = hash('sha256', $token);

        $member = new Member;
        $member->id = 909;
        $member->email = 'member909@example.com';

        $memberRepository = $this->createMock(MemberRepository::class);
        $memberRepository->expects($this->once())
            ->method('first')
            ->with(['id', 909])
            ->willReturn($member);
        $memberRepository->expects($this->once())
            ->method('updateByEloquentModel')
            ->with($member, ['password' => 'NewPassword1'])
            ->willThrowException(new RuntimeException('db write failed'));

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('pull')
            ->with("password_reset:token:$tokenHash")
            ->willReturn(909);
        $cache->expects($this->never())
            ->method('forget');

        $config = $this->createMock(ConfigRepository::class);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with(
                '會員密碼重設失敗',
                $this->callback(function (array $context): bool {
                    return ($context['member_id'] ?? null) === 909
                        && ($context['exception'] ?? null) instanceof RuntimeException;
                })
            );

        $service = new PasswordResetService($memberRepository, $cache, $config, $logger);

        $result = $service->resetPassword($token, 'member909@example.com', 'NewPassword1');

        $this->assertSame(500, $result['status']);
        $this->assertSame('重設密碼失敗，請稍後再試。', $result['message']);
    }

    /**
     * 重設密碼：更新會員密碼回傳 false 時應回傳 500 並記錄錯誤。
     */
    public function test_reset_password_returns_500_when_update_password_returns_false(): void
    {
        $token = 'token-update-false';
        $tokenHash = hash('sha256', $token);

        $member = new Member;
        $member->id = 910;
        $member->email = 'member910@example.com';

        $memberRepository = $this->createMock(MemberRepository::class);
        $memberRepository->expects($this->once())
            ->method('first')
            ->with(['id', 910])
            ->willReturn($member);
        $memberRepository->expects($this->once())
            ->method('updateByEloquentModel')
            ->with($member, ['password' => 'NewPassword1'])
            ->willReturn(false);

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('pull')
            ->with("password_reset:token:$tokenHash")
            ->willReturn(910);
        $cache->expects($this->never())
            ->method('forget');

        $config = $this->createMock(ConfigRepository::class);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with(
                '會員密碼重設失敗',
                $this->callback(function (array $context): bool {
                    return ($context['member_id'] ?? null) === 910
                        && ($context['exception'] ?? null) instanceof RuntimeException;
                })
            );

        $service = new PasswordResetService($memberRepository, $cache, $config, $logger);

        $result = $service->resetPassword($token, 'member910@example.com', 'NewPassword1');

        $this->assertSame(500, $result['status']);
        $this->assertSame('重設密碼失敗，請稍後再試。', $result['message']);
    }

    /**
     * 重設密碼：成功更新密碼後應清除 member 索引 key 並回傳成功。
     */
    public function test_reset_password_updates_password_and_clears_member_cache_key(): void
    {
        $token = 'valid-token-success';
        $tokenHash = hash('sha256', $token);

        $member = new Member;
        $member->id = 808;
        $member->email = 'member808@example.com';

        $memberRepository = $this->createMock(MemberRepository::class);
        $memberRepository->expects($this->once())
            ->method('first')
            ->with(['id', 808])
            ->willReturn($member);
        $memberRepository->expects($this->once())
            ->method('updateByEloquentModel')
            ->with($member, ['password' => 'NewPassword1'])
            ->willReturn(true);

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('pull')
            ->with("password_reset:token:$tokenHash")
            ->willReturn(808);
        $cache->expects($this->once())
            ->method('forget')
            ->with('password_reset:member:808')
            ->willReturn(true);

        $config = $this->createMock(ConfigRepository::class);
        $logger = $this->createMock(LoggerInterface::class);

        $service = new PasswordResetService($memberRepository, $cache, $config, $logger);

        $result = $service->resetPassword($token, 'member808@example.com', 'NewPassword1');

        $this->assertSame(200, $result['status']);
        $this->assertSame('密碼已成功重設。', $result['message']);
    }
}
