<?php

namespace Tests\Feature;

use App\Services\VerificationService;
use Mockery;
use Tests\TestCase;

class VerificationControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
    }

    /**
     * 發送簡訊驗證碼：目前尚未實作，應回傳 501。
     */
    public function test_send_sms_verification_code_returns_501_not_implemented(): void
    {
        $response = $this->postJson('/api/verifications/phone/send');

        $response->assertStatus(501)
            ->assertJson([
                'message' => '簡訊驗證碼發送流程待實作。',
            ]);
    }

    /**
     * 驗證手機：目前尚未實作，應回傳 501。
     */
    public function test_verify_phone_returns_501_not_implemented(): void
    {
        $response = $this->postJson('/api/verifications/phone/verify');

        $response->assertStatus(501)
            ->assertJson([
                'message' => '手機驗證流程待實作。',
            ]);
    }

    /**
     * 發送電子郵件驗證連結：資料格式錯誤時應回傳 422。
     */
    public function test_send_email_verification_link_returns_422_when_validation_fails(): void
    {
        $response = $this->postJson('/api/verifications/email/send', [
            'email' => 'invalid-email-format',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * 發送電子郵件驗證連結：驗證失敗時不應呼叫 service。
     */
    public function test_send_email_verification_link_does_not_call_service_when_validation_fails(): void
    {
        $verificationService = Mockery::mock(VerificationService::class);
        $verificationService->shouldReceive('sendEmailVerificationLink')->never();

        $this->app->instance(VerificationService::class, $verificationService);

        $response = $this->postJson('/api/verifications/email/send', [
            'email' => 'invalid-email-format',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * 發送電子郵件驗證連結：應回傳自訂驗證錯誤訊息（required、max）。
     */
    public function test_send_email_verification_link_returns_custom_messages_for_required_and_max_rules(): void
    {
        $response = $this->postJson('/api/verifications/email/send', [
            'email' => str_repeat('a', 244) . '@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', '電子郵件長度不能超過 255 字元。');

        $emptyResponse = $this->postJson('/api/verifications/email/send', [
            'email' => '',
        ]);

        $emptyResponse->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', '電子郵件為必填。');
    }

    /**
     * 發送電子郵件驗證連結：驗證通過時應呼叫 service 並透傳回應內容。
     */
    public function test_send_email_verification_link_returns_service_result_when_payload_is_valid(): void
    {
        $verificationService = Mockery::mock(VerificationService::class);
        $verificationService->shouldReceive('sendEmailVerificationLink')
            ->once()
            ->with('member@example.com')
            ->andReturn([
                'status' => 200,
                'message' => '電子郵件驗證連結發送成功。',
            ]);

        $this->app->instance(VerificationService::class, $verificationService);

        $response = $this->postJson('/api/verifications/email/send', [
            'email' => 'member@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => '電子郵件驗證連結發送成功。',
            ]);
    }

    /**
     * 發送電子郵件驗證連結：service 失敗狀態應原樣回傳。
     */
    public function test_send_email_verification_link_propagates_service_failure_status_and_message(): void
    {
        $verificationService = Mockery::mock(VerificationService::class);
        $verificationService->shouldReceive('sendEmailVerificationLink')
            ->once()
            ->with('member@example.com')
            ->andReturn([
                'status' => 500,
                'message' => '電子郵件驗證連結發送失敗，請稍後再試。',
            ]);

        $this->app->instance(VerificationService::class, $verificationService);

        $response = $this->postJson('/api/verifications/email/send', [
            'email' => 'member@example.com',
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'message' => '電子郵件驗證連結發送失敗，請稍後再試。',
            ]);
    }

    /**
     * 驗證電子郵件：資料格式錯誤時應回傳 422。
     */
    public function test_verify_email_returns_422_when_validation_fails(): void
    {
        $response = $this->postJson('/api/verifications/email/verify', [
            'token' => 'invalid token with spaces',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token']);
    }

    /**
     * 驗證電子郵件：驗證失敗時不應呼叫 service。
     */
    public function test_verify_email_does_not_call_service_when_validation_fails(): void
    {
        $verificationService = Mockery::mock(VerificationService::class);
        $verificationService->shouldReceive('verifyEmail')->never();

        $this->app->instance(VerificationService::class, $verificationService);

        $response = $this->postJson('/api/verifications/email/verify', [
            'token' => 'invalid token with spaces',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token']);
    }

    /**
     * 驗證電子郵件：應回傳自訂驗證錯誤訊息（required、size、regex）。
     */
    public function test_verify_email_returns_custom_validation_messages(): void
    {
        $requiredResponse = $this->postJson('/api/verifications/email/verify', [
            'token' => '',
        ]);

        $requiredResponse->assertStatus(422)
            ->assertJsonValidationErrors(['token'])
            ->assertJsonPath('errors.token.0', '驗證 Token 為必填。');

        $sizeResponse = $this->postJson('/api/verifications/email/verify', [
            'token' => str_repeat('a', 42),
        ]);

        $sizeResponse->assertStatus(422)
            ->assertJsonValidationErrors(['token'])
            ->assertJsonPath('errors.token.0', '驗證 Token 長度錯誤。');

        $regexResponse = $this->postJson('/api/verifications/email/verify', [
            'token' => str_repeat('a', 42) . '+',
        ]);

        $regexResponse->assertStatus(422)
            ->assertJsonValidationErrors(['token'])
            ->assertJsonPath('errors.token.0', '驗證 Token 僅允許 Base64URL 字元。');
    }

    /**
     * 驗證電子郵件：驗證通過時應呼叫 service 並透傳回應內容。
     */
    public function test_verify_email_returns_service_result_when_payload_is_valid(): void
    {
        $token = str_repeat('A', 43);

        $verificationService = Mockery::mock(VerificationService::class);
        $verificationService->shouldReceive('verifyEmail')
            ->once()
            ->with($token)
            ->andReturn([
                'status' => 200,
                'message' => '電子郵件驗證成功。',
            ]);

        $this->app->instance(VerificationService::class, $verificationService);

        $response = $this->postJson('/api/verifications/email/verify', [
            'token' => $token,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => '電子郵件驗證成功。',
            ]);
    }

    /**
     * 驗證電子郵件：service 回傳失敗狀態時應原樣回傳。
     */
    public function test_verify_email_propagates_service_failure_status_and_message(): void
    {
        $token = str_repeat('A', 43);

        $verificationService = Mockery::mock(VerificationService::class);
        $verificationService->shouldReceive('verifyEmail')
            ->once()
            ->with($token)
            ->andReturn([
                'status' => 422,
                'message' => '驗證連結無效或已過期。',
            ]);

        $this->app->instance(VerificationService::class, $verificationService);

        $response = $this->postJson('/api/verifications/email/verify', [
            'token' => $token,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => '驗證連結無效或已過期。',
            ]);
    }
}
