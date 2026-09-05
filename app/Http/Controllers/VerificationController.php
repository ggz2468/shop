<?php

namespace App\Http\Controllers;

use App\Services\VerificationService;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    /**
     * 建構子
     *
     * @return void
     */
    public function __construct(
        private VerificationService $verificationService
    ) {}

    /**
     * 發送簡訊驗證碼
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendSmsVerificationCode(Request $request)
    {
        return response()->json([
            'message' => '簡訊驗證碼發送流程待實作。',
        ], 501);
    }

    /**
     * 驗證手機
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyPhone(Request $request)
    {
        return response()->json([
            'message' => '手機驗證流程待實作。',
        ], 501);
    }

    /**
     * 發送電子郵件驗證連結
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendEmailVerificationLink(Request $request)
    {
        // 資料格式驗證
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ], [
            'email.required' => '電子郵件為必填。',
            'email.string' => '電子郵件格式錯誤。',
            'email.email' => '電子郵件格式錯誤。',
            'email.max' => '電子郵件長度不能超過 255 字元。',
        ]);

        $result = $this->verificationService->sendEmailVerificationLink($validated['email']);

        return response()->json(
            ['message' => $result['message']],
            $result['status'],
        );
    }

    /**
     * 驗證電子郵件
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyEmail(Request $request)
    {
        // 資料格式驗證
        $validated = $request->validate([
            'token' => ['required', 'string', 'size:43', 'regex:/^[A-Za-z0-9_-]+$/'],
        ], [
            'token.required' => '驗證 Token 為必填。',
            'token.string' => '驗證 Token 格式錯誤。',
            'token.size' => '驗證 Token 長度錯誤。',
            'token.regex' => '驗證 Token 僅允許 Base64URL 字元。',
        ]);

        $result = $this->verificationService->verifyEmail($validated['token']);

        return response()->json(
            ['message' => $result['message']],
            $result['status'],
        );
    }
}
