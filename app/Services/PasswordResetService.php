<?php

namespace App\Services;

use App\Repositories\MemberRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Notifications\PasswordResetLinkNotification;
use RuntimeException;
use Throwable;

class PasswordResetService
{
    /**
     * 建構子
     * 
     * @param \App\Repositories\MemberRepository $memberRepository
     * @return void
     */
    public function __construct(
        private MemberRepository $memberRepository
    ) {
        
    }

    /**
     * 發送重設密碼連結
     * 
     * @param string $email
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
                'message' => '重設密碼連結已發送（如果該電子郵件存在）。'
            ];
        }

        // 檢查是否已經有未過期的重設密碼 token，若有則刪除舊的 token 快取資料，確保每次發送重設密碼連結時只有一個有效的 token 留在快取中。
        $cacheKey = "password_reset:member:{$member->id}";
        $existingTokenHash = Cache::pull($cacheKey);
        if ($existingTokenHash !== null) {
            Cache::delete("password_reset:token:$existingTokenHash");
        }

        // 重新產生重設密碼 token。
        $resetPasswordToken = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $resetPasswordTokenHash = hash('sha256', $resetPasswordToken);

        try {
            // 將 token 與會員編號的對應關係快取起來，設定過期時間為 10 分鐘。
            $expiresAt = now()->addMinutes(10);
            if (!Cache::putMany([
                $cacheKey => $resetPasswordTokenHash,
                "password_reset:token:$resetPasswordTokenHash" => $member->id,
            ], $expiresAt)) {
                throw new RuntimeException('重設密碼 token 儲存失敗。');
            }

            $frontendUrl = config('services.frontend_url');
            if (empty($frontendUrl)) {
                throw new RuntimeException('前端 URL 未設定，請確認服務設定。');
            }

            // 發送重設密碼連結通知信給會員。
            $frontendUrl = rtrim($frontendUrl, '/');
            $resetPasswordUrl = sprintf('%s/auth/password/reset?token=%s', $frontendUrl, $resetPasswordToken);
            $member->notify(new PasswordResetLinkNotification($resetPasswordUrl));
        } catch (Throwable $e) {
            // 如果在儲存 token 或發送通知的過程中發生任何錯誤，確保將相關的快取資料刪除，以避免殘留無效的 token 資料。
            Cache::deleteMultiple([
                $cacheKey,
                "password_reset:token:$resetPasswordTokenHash",
            ]);    

            Log::error('發送重設密碼連結失敗', ['exception' => $e]);
            return [
                'status' => 500,
                'message' => '發送重設密碼連結失敗，請稍後再試。'
            ];
        }

        return [
            'status' => 200,
            'message' => '重設密碼連結已發送（如果該電子郵件存在）。'
        ];
    }
}
