<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use App\Services\MemberService;

class AuthController extends Controller
{
    /**
     * 建構子
     * 
     * @param \App\Services\MemberService $memberService
     * @return void
     */
    public function __construct(
        private MemberService $memberService
    ) {
        
    }

    /**
     * 會員登入
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        // 資料格式驗證
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::guard('web')->attempt($credentials)) {
            // 登入成功後，重新產生 Session ID 以防止 Session Fixation 攻擊
            $request->session()->regenerate();

            return response()->json([
                'message' => '登入成功',
            ]);
        }

        return response()->json([
            'message' => '登入失敗'
        ], 401);
    }

    /**
     * 會員登出
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // 將使用者登出 web guard
        Auth::guard('web')->logout();

        // 使目前的 Session 失效
        $request->session()->invalidate();

        // 重新產生 CSRF Token，確保後續請求的安全
        $request->session()->regenerateToken();

        return response()->json([
            'message' => '登出成功'
        ]);
    }

    /**
     * 取得目前登入的會員資訊
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * 會員註冊
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        // 資料格式驗證
        $validated = $request->validate([
            'national_id_number' => [
                'required',
                'string',
                'size:10',
                'regex:/^[A-Z][12]\d{8}$/',
                Rule::unique('members', 'active_national_id_number'),
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:100',
                Rule::unique('members', 'active_email'),
            ],
            'phone' => [
                'required',
                'string',
                'regex:/^09\d{8}$/',
                Rule::unique('members', 'active_phone'),
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:255',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
            ],
        ], [
            'national_id_number.required' => '身分證字號為必填。',
            'national_id_number.string' => '身分證字號格式錯誤。',
            'national_id_number.size' => '身分證字號長度需為 10 碼。',
            'national_id_number.regex' => '身分證字號格式錯誤。',
            'national_id_number.unique' => '身分證字號已被註冊。',
            'email.required' => '電子郵件為必填。',
            'email.string' => '電子郵件格式錯誤。',
            'email.email' => '電子郵件格式錯誤。',
            'email.max' => '電子郵件長度不能超過 100 字元。',
            'email.unique' => '電子郵件已被註冊。',
            'phone.required' => '手機號碼為必填。',
            'phone.string' => '手機號碼格式錯誤。',
            'phone.regex' => '手機號碼格式錯誤，需為 09 開頭共 10 碼。',
            'phone.unique' => '手機號碼已被註冊。',
            'password.required' => '密碼為必填。',
            'password.string' => '密碼格式錯誤。',
            'password.min' => '密碼長度至少需為 8 碼。',
            'password.max' => '密碼長度不能超過 255 碼。',
            'password.regex' => '密碼格式錯誤，需包含大寫字母、小寫字母與數字。',
        ]);

        $result = $this->memberService->register($validated);

        return response()->json(['message' => $result['message']], $result['status']);
    }
}
