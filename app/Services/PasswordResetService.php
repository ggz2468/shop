<?php

namespace App\Services;

use App\Notifications\PasswordResetLinkNotification;
use App\Repositories\MemberRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class PasswordResetService
{
    /**
     * 建構子
     *
     * @return void
     */
    public function __construct(
        private MemberRepository $memberRepository,
        private CacheRepository $cache,
        private ConfigRepository $config,
        private LoggerInterface $logger,
    ) {}

    /**
     * 發送重設密碼連結
     *
     * @return array{status: int, message: string}
     */
    public function sendResetLink(string $email)
    {
        // 透過電子郵件取得會員資料。
        $member = $this->memberRepository->first(['email', $email]);

        // 檢查對應會員資料是否存在，若不存在則直接回傳發送成功的訊息，以避免洩漏會員資料的存在與否給潛在攻擊者。
        if ($member === null) {
            return [
                'status' => 200,
                'message' => '重設密碼連結已發送（如果該電子郵件存在）。',
            ];
        }

        // 檢查是否已經有未過期的重設密碼 token，若有則刪除舊的 token 快取資料，確保每次發送重設密碼連結時只有一個有效的 token 留在快取中。
        $cacheKey = "password_reset:member:{$member->id}";
        $existingTokenHash = $this->cache->pull($cacheKey);
        if ($existingTokenHash !== null) {
            $this->cache->forget("password_reset:token:$existingTokenHash");
        }

        // 重新產生重設密碼 token。
        $resetPasswordToken = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $resetPasswordTokenHash = hash('sha256', $resetPasswordToken);

        try {
            // 將 token 與會員編號的對應關係快取起來，設定過期時間為 10 分鐘。
            $expiresAt = now()->addMinutes(10);
            $storedMemberIndex = $this->cache->put($cacheKey, $resetPasswordTokenHash, $expiresAt);
            $storedTokenIndex = $this->cache->put("password_reset:token:$resetPasswordTokenHash", $member->id, $expiresAt);
            if (! $storedMemberIndex || ! $storedTokenIndex) {
                throw new RuntimeException('重設密碼 token 儲存失敗。');
            }

            $frontendUrl = $this->config->get('services.frontend_url');
            if (empty($frontendUrl)) {
                throw new RuntimeException('前端 URL 未設定，請確認服務設定。');
            }

            // 發送重設密碼連結通知信給會員。
            $frontendUrl = rtrim($frontendUrl, '/');
            $resetPasswordUrl = sprintf('%s/auth/password/reset?token=%s', $frontendUrl, $resetPasswordToken);
            $member->notify(new PasswordResetLinkNotification($resetPasswordUrl));
        } catch (Throwable $e) {
            // 如果在儲存 token 或發送通知的過程中發生任何錯誤，確保將相關的快取資料刪除，以避免殘留無效的 token 資料。
            $this->cache->forget($cacheKey);
            $this->cache->forget("password_reset:token:$resetPasswordTokenHash");

            $this->logger->error('發送重設密碼連結失敗', ['exception' => $e]);

            return [
                'status' => 500,
                'message' => '發送重設密碼連結失敗，請稍後再試。',
            ];
        }

        return [
            'status' => 200,
            'message' => '重設密碼連結已發送（如果該電子郵件存在）。',
        ];
    }

    /**
     * 重設密碼
     *
     * @return array{status: int, message: string}
     */
    public function resetPassword(string $token, string $email, string $newPassword)
    {
        $tokenHash = hash('sha256', $token);
        $memberId = $this->cache->pull("password_reset:token:$tokenHash");
        if (! is_numeric($memberId)) {
            return [
                'status' => 400,
                'message' => '重設密碼失敗，請確認輸入的資訊是否正確。',
            ];
        }

        $memberId = (int) $memberId;
        $cacheKey = "password_reset:member:$memberId";

        // 透過 token 對應的會員編號取得會員資料，並確認 email 一致，避免 token 被跨帳號誤用。
        $member = $this->memberRepository->first(['id', $memberId]);
        if ($member === null || ! hash_equals(strtolower((string) $member->email), strtolower($email))) {
            $this->cache->forget($cacheKey);

            return [
                'status' => 400,
                'message' => '重設密碼失敗，請確認輸入的資訊是否正確。',
            ];
        }

        // 更新會員密碼。
        try {
            $updated = $this->memberRepository->updateByEloquentModel($member, [
                'password' => $newPassword,
            ]);

            if (! $updated) {
                throw new RuntimeException('會員密碼重設失敗。');
            }
        } catch (Throwable $e) {
            $this->logger->error('會員密碼重設失敗', ['member_id' => $member->id, 'exception' => $e]);

            return [
                'status' => 500,
                'message' => '重設密碼失敗，請稍後再試。',
            ];
        }

        // 刪除快取中的 token 資料，確保重設密碼連結只能使用一次。
        $this->cache->forget($cacheKey);

        return [
            'status' => 200,
            'message' => '密碼已成功重設。',
        ];
    }
}
