<?php

namespace App\Services;

use App\Repositories\MemberRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Notifications\EmailVerificationLinkNotification;
use RuntimeException;
use Throwable;

class VerificationService
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
     * 發送電子郵件驗證連結
     * 
     * @param string $email
     * @return array{status: int, message: string}
     */
    public function sendEmailVerificationLink(string $email)
    {
        // 透過電子郵件取得會員資料
        $member = $this->memberRepository->first(['email', $email]);

        // 檢查對應會員資料是否存在，若不存在則直接回傳發送成功的訊息，以避免洩漏會員資料的存在與否給潛在攻擊者。
        if ($member === null) {
            Log::warning('嘗試發送電子郵件驗證連結，但找不到對應的會員資料', ['email' => $email]);
            return [
                'status' => 200,
                'message' => '電子郵件驗證連結發送成功。'
            ];
        }

        // 透過會員編號取得已發送的驗證 token，並刪除舊的 token 快取資料，確保每次發送驗證連結時只有一個有效的 token 留在快取中。
        $cacheKey = "email_verify:member:{$member->id}";
        $existingTokenHash = Cache::pull($cacheKey);
        if ($existingTokenHash !== null) {
            Cache::delete("email_verify:token:$existingTokenHash");
        }

        // 重新產生驗證 token
        $verificationToken = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $verificationTokenHash = hash('sha256', $verificationToken);

        try {
            // 將 token 與會員編號的對應關係快取起來，設定過期時間為 10 分鐘。
            $expiresAt = now()->addMinutes(10);
            if (!Cache::putMany([
                "email_verify:token:$verificationTokenHash" => $member->id,
                $cacheKey => $verificationTokenHash,
            ], $expiresAt)) {
                throw new RuntimeException('電子郵件驗證 token 儲存失敗。');
            }

            $frontendUrl = config('services.frontend_url');
            if (empty($frontendUrl)) {
                throw new RuntimeException('前端 URL 未設定，請確認服務設定。');
            }

            // 發送電子郵件驗證連結通知信
            $frontendUrl = rtrim($frontendUrl, '/');
            $verificationUrl = sprintf("%s/verifications/email/verify?token=%s", $frontendUrl, $verificationToken);
            $member->notify(new EmailVerificationLinkNotification($verificationUrl));
        } catch (Throwable $e) {
            // 因發送電子郵件驗證連結通知信失敗，故刪除剛剛建立的 token 快取資料，以避免無效的 token 留在快取中。
            Cache::deleteMultiple([
                "email_verify:token:$verificationTokenHash",
                $cacheKey,
            ]);

            // 捕捉發送通知過程中可能發生的例外，並將錯誤訊息紀錄於 Log 中，最後回傳發送失敗的訊息。
            Log::error('電子郵件驗證連結發送失敗', ['exception' => $e]);
            return [
                'status' => 500,
                'message' => '電子郵件驗證連結發送失敗，請稍後再試。'
            ];
        }

        return [
            'status' => 200,
            'message' => '電子郵件驗證連結發送成功。'
        ];
    }

    /**
     * 驗證電子郵件
     * 
     * @param string $token
     * @return array{status: int, message: string}
     */
    public function verifyEmail(string $token)
    {
        $hashedTokenCacheKey = 'email_verify:token:' . hash('sha256', $token);
        $data = Cache::pull($hashedTokenCacheKey);

        if ($data === null) {
            return [
                'status' => 422,
                'message' => '驗證連結無效或已過期。'
            ];
        }

        // 取得會員編號
        if (is_array($data)) {
            $memberId = $data['member_id'] ?? null;
        } else {
            $memberId = $data;
        }

        // 判斷會員編號是否為數值
        if (!is_numeric($memberId)) {
            return [
                'status' => 422,
                'message' => '驗證連結無效或已過期。'
            ];
        }

        $memberId = (int) $memberId;

        $member = $this->memberRepository->first(['id', $memberId]);

        // 確認資料庫中是否存在對應會員資料，若不存在則直接刪除 token 快取並回傳驗證失敗。
        if ($member === null) {
            Cache::delete("email_verify:member:$memberId");

            return [
                'status' => 500,
                'message' => '驗證失敗，無法找到對應的會員資料。'
            ];
        }

        // 確認會員是否已完成驗證，若已驗證則直接刪除 token 快取並回傳驗證成功。
        if ($member->email_verified_at !== null) {
            Cache::delete("email_verify:member:$memberId");

            return [
                'status' => 200,
                'message' => '電子郵件已完成驗證。'
            ];
        }

        // 將會員的電子郵件驗證時間更新為目前時間
        $affectedRowCounts = $this->memberRepository->update(['id', $memberId], [
            'email_verified_at' => now(),
        ]);

        // 確認是否更新成功
        if ($affectedRowCounts === 0) {
            return [
                'status' => 500,
                'message' => '驗證失敗，無法找到對應的會員資料。'
            ];
        }

        // 刪除 token 與 member 索引 key。
        Cache::delete("email_verify:member:$memberId");

        return [
            'status' => 200,
            'message' => '電子郵件驗證成功。'
        ];
    }
}
