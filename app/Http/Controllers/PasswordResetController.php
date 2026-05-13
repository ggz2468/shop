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
}
