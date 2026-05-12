<?php

namespace App\Services;

use App\Repositories\MemberRepository;
use App\Services\VerificationService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class MemberService
{
    /**
     * 建構子
     * 
     * @param \App\Repositories\MemberRepository $memberRepository
     * @param \App\Services\VerificationService $verificationService
     * @return void
     */
    public function __construct(
        private MemberRepository $memberRepository,
        private VerificationService $verificationService
    ) {
        
    }

    /**
     * 會員註冊
     * 
     * @param array<string, mixed> $data
     * @return array{status: int, message: string}
     */
    public function register(array $data)
    {
        try {
            DB::transaction(function () use ($data) {
                // 新增會員資料
                $this->memberRepository->create($data);
            });
        } catch (QueryException $e) {
            // 如果發生資料表唯一鍵違規錯誤，則根據錯誤訊息判斷是哪個欄位違規，並回傳對應的錯誤訊息給前端，以提升使用者體驗。
            if (($e->errorInfo[0] ?? null) === '23000') {
                $message = '註冊資料已被使用。';
                $errorMessage = $e->getMessage();

                if (str_contains($errorMessage, 'active_national_id_number')) {
                    $message = '身分證字號已被註冊。';
                } elseif (str_contains($errorMessage, 'active_email')) {
                    $message = '電子郵件已被註冊。';
                } elseif (str_contains($errorMessage, 'active_phone')) {
                    $message = '手機號碼已被註冊。';
                }

                return [
                    'status' => 409,
                    'message' => $message,
                ];
            }

            Log::error('會員註冊失敗', ['exception' => $e]);
            return [
                'status' => 500,
                'message' => '會員註冊失敗，請稍後再試。'
            ];
        } catch (Throwable $e) {
            Log::error('會員註冊失敗', ['exception' => $e]);
            return [
                'status' => 500,
                'message' => '會員註冊失敗，請稍後再試。'
            ];
        }

        // 發送電子郵件驗證連結
        $emailSendingResult = $this->verificationService->sendEmailVerificationLink($data['email']);

        // 如果發送電子郵件驗證連結失敗，則記錄錯誤日誌，但不影響會員註冊的整體流程，因為會員仍然可以透過其他方式完成驗證。
        if ($emailSendingResult['status'] !== 200) {
            Log::error('會員註冊成功，但電子郵件驗證連結發送失敗', ['email' => $data['email'], 'error' => $emailSendingResult['message']]);
            return [
                'status' => 201,
                'message' => '會員註冊成功，但電子郵件驗證連結發送失敗，請稍後重試。'
            ];
        }

        return [
            'status' => 201,
            'message' => '會員註冊成功。'
        ];
    }
}
