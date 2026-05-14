<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PasswordResetService;

class PasswordResetController extends Controller
{
    /**
     * 建構子
     * 
     * @param \App\Services\PasswordResetService $passwordResetService
     * @return void
     */
    public function __construct(
        private PasswordResetService $passwordResetService
    ) {

    }

    /**
     * 發送重設密碼連結
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendResetLink(Request $request)
    {
        // 資料格式驗證
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => '電子郵件為必填。',
            'email.email' => '電子郵件格式錯誤。',
        ]);

        $result = $this->passwordResetService->sendResetLink($validated['email']);

        return response()->json(
            ['message' => $result['message']],
            $result['status']
        );
    }

    /**
     * 重設密碼
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(Request $request)
    {
        // 資料格式驗證
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', 'confirmed'],
        ], [
            'token.required' => '重設密碼 token 為必填。',
            'token.string' => '重設密碼 token 格式錯誤。',
            'email.required' => '電子郵件為必填。',
            'email.string' => '電子郵件格式錯誤。',
            'email.email' => '電子郵件格式錯誤。',
            'password.required' => '新密碼為必填。',
            'password.string' => '新密碼格式錯誤。',
            'password.min' => '新密碼至少需要 8 個字元。',
            'password.max' => '新密碼最多只能有 255 個字元。',
            'password.regex' => '新密碼必須包含至少一個大寫字母、一個小寫字母和一個數字。',
            'password.confirmed' => '新密碼與確認密碼不匹配。',
        ]);

        $result = $this->passwordResetService->resetPassword($validated['token'], $validated['email'], $validated['password']);

        return response()->json(
            ['message' => $result['message']],
            $result['status']
        );
    }
}
