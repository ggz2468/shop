<?php

namespace Tests\Feature;

use App\Services\PasswordResetService;
use Mockery;
use Tests\TestCase;

class PasswordResetControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
    }

    /**
     * 發送重設密碼連結：資料格式錯誤時應回傳 422。
     */
    public function test_send_reset_link_returns_422_when_validation_fails(): void
    {
        $response = $this->postJson('/api/auth/password/forgot', [
            'email' => 'invalid-email-format',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * 發送重設密碼連結：驗證失敗時不應呼叫 service。
     */
    public function test_send_reset_link_does_not_call_service_when_validation_fails(): void
    {
        $passwordResetService = Mockery::mock(PasswordResetService::class);
        $passwordResetService->shouldReceive('sendResetLink')->never();

        $this->app->instance(PasswordResetService::class, $passwordResetService);

        $response = $this->postJson('/api/auth/password/forgot', [
            'email' => 'invalid-email-format',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * 發送重設密碼連結：應回傳自訂驗證訊息（required、email）。
     */
    public function test_send_reset_link_returns_custom_validation_messages(): void
    {
        $requiredResponse = $this->postJson('/api/auth/password/forgot', [
            'email' => '',
        ]);

        $requiredResponse->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', '電子郵件為必填。');

        $emailResponse = $this->postJson('/api/auth/password/forgot', [
            'email' => 'not-an-email',
        ]);

        $emailResponse->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', '電子郵件格式錯誤。');
    }

    /**
     * 發送重設密碼連結：驗證通過時應呼叫 service 並透傳回應。
     */
    public function test_send_reset_link_returns_service_result_when_payload_is_valid(): void
    {
        $passwordResetService = Mockery::mock(PasswordResetService::class);
        $passwordResetService->shouldReceive('sendResetLink')
            ->once()
            ->with('member@example.com')
            ->andReturn([
                'status' => 200,
                'message' => '重設密碼連結已發送（如果該電子郵件存在）。',
            ]);

        $this->app->instance(PasswordResetService::class, $passwordResetService);

        $response = $this->postJson('/api/auth/password/forgot', [
            'email' => 'member@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => '重設密碼連結已發送（如果該電子郵件存在）。',
            ]);
    }

    /**
     * 發送重設密碼連結：service 回傳失敗時應原樣回傳。
     */
    public function test_send_reset_link_propagates_service_failure_status_and_message(): void
    {
        $passwordResetService = Mockery::mock(PasswordResetService::class);
        $passwordResetService->shouldReceive('sendResetLink')
            ->once()
            ->with('member@example.com')
            ->andReturn([
                'status' => 500,
                'message' => '發送重設密碼連結失敗，請稍後再試。',
            ]);

        $this->app->instance(PasswordResetService::class, $passwordResetService);

        $response = $this->postJson('/api/auth/password/forgot', [
            'email' => 'member@example.com',
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'message' => '發送重設密碼連結失敗，請稍後再試。',
            ]);
    }

    /**
     * 重設密碼：資料格式錯誤時應回傳 422。
     */
    public function test_reset_password_returns_422_when_validation_fails(): void
    {
        $response = $this->postJson('/api/auth/password/reset', [
            'token' => '',
            'email' => 'invalid-email',
            'password' => 'weak',
            'password_confirmation' => 'not-match',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'token',
                'email',
                'password',
            ]);
    }

    /**
     * 重設密碼：驗證失敗時不應呼叫 service。
     */
    public function test_reset_password_does_not_call_service_when_validation_fails(): void
    {
        $passwordResetService = Mockery::mock(PasswordResetService::class);
        $passwordResetService->shouldReceive('resetPassword')->never();

        $this->app->instance(PasswordResetService::class, $passwordResetService);

        $response = $this->postJson('/api/auth/password/reset', [
            'token' => '',
            'email' => 'invalid-email',
            'password' => 'weak',
            'password_confirmation' => 'not-match',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'token',
                'email',
                'password',
            ]);
    }

    /**
     * 重設密碼：應回傳自訂驗證訊息（required、email、min、max、regex、confirmed）。
     */
    public function test_reset_password_returns_custom_validation_messages(): void
    {
        $requiredResponse = $this->postJson('/api/auth/password/reset', [
            'token' => '',
            'email' => '',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $requiredResponse->assertStatus(422)
            ->assertJsonValidationErrors(['token', 'email', 'password'])
            ->assertJsonPath('errors.token.0', '重設密碼 token 為必填。')
            ->assertJsonPath('errors.email.0', '電子郵件為必填。')
            ->assertJsonPath('errors.password.0', '新密碼為必填。');

        $emailResponse = $this->postJson('/api/auth/password/reset', [
            'token' => 'valid-token',
            'email' => 'bad-email',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $emailResponse->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', '電子郵件格式錯誤。');

        $minResponse = $this->postJson('/api/auth/password/reset', [
            'token' => 'valid-token',
            'email' => 'member@example.com',
            'password' => 'Aa1',
            'password_confirmation' => 'Aa1',
        ]);

        $minResponse->assertStatus(422)
            ->assertJsonValidationErrors(['password'])
            ->assertJsonPath('errors.password.0', '新密碼至少需要 8 個字元。');

        $maxResponse = $this->postJson('/api/auth/password/reset', [
            'token' => 'valid-token',
            'email' => 'member@example.com',
            'password' => str_repeat('Aa1', 86),
            'password_confirmation' => str_repeat('Aa1', 86),
        ]);

        $maxResponse->assertStatus(422)
            ->assertJsonValidationErrors(['password'])
            ->assertJsonPath('errors.password.0', '新密碼最多只能有 255 個字元。');

        $regexResponse = $this->postJson('/api/auth/password/reset', [
            'token' => 'valid-token',
            'email' => 'member@example.com',
            'password' => 'alllowercase123',
            'password_confirmation' => 'alllowercase123',
        ]);

        $regexResponse->assertStatus(422)
            ->assertJsonValidationErrors(['password'])
            ->assertJsonPath('errors.password.0', '新密碼必須包含至少一個大寫字母、一個小寫字母和一個數字。');

        $confirmedResponse = $this->postJson('/api/auth/password/reset', [
            'token' => 'valid-token',
            'email' => 'member@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password321',
        ]);

        $confirmedResponse->assertStatus(422)
            ->assertJsonValidationErrors(['password'])
            ->assertJsonPath('errors.password.0', '新密碼與確認密碼不匹配。');
    }

    /**
     * 重設密碼：驗證通過時應呼叫 service 並透傳回應。
     */
    public function test_reset_password_returns_service_result_when_payload_is_valid(): void
    {
        $payload = [
            'token' => 'valid-token',
            'email' => 'member@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ];

        $passwordResetService = Mockery::mock(PasswordResetService::class);
        $passwordResetService->shouldReceive('resetPassword')
            ->once()
            ->with($payload['token'], $payload['email'], $payload['password'])
            ->andReturn([
                'status' => 200,
                'message' => '密碼已成功重設。',
            ]);

        $this->app->instance(PasswordResetService::class, $passwordResetService);

        $response = $this->postJson('/api/auth/password/reset', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'message' => '密碼已成功重設。',
            ]);
    }

    /**
     * 重設密碼：service 回傳失敗時應原樣回傳。
     */
    public function test_reset_password_propagates_service_failure_status_and_message(): void
    {
        $payload = [
            'token' => 'valid-token',
            'email' => 'member@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ];

        $passwordResetService = Mockery::mock(PasswordResetService::class);
        $passwordResetService->shouldReceive('resetPassword')
            ->once()
            ->with($payload['token'], $payload['email'], $payload['password'])
            ->andReturn([
                'status' => 400,
                'message' => '重設密碼失敗，請確認輸入的資訊是否正確。',
            ]);

        $this->app->instance(PasswordResetService::class, $passwordResetService);

        $response = $this->postJson('/api/auth/password/reset', $payload);

        $response->assertStatus(400)
            ->assertJson([
                'message' => '重設密碼失敗，請確認輸入的資訊是否正確。',
            ]);
    }
}
