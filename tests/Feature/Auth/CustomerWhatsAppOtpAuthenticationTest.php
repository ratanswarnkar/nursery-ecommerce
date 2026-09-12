<?php

use App\Models\CustomerOtpChallenge;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    config([
        'services.sms.driver' => 'whatsapp',
        'services.sms.whatsapp.api_url' => 'https://whatsapp.myapi.in.net/send-message',
        'services.sms.whatsapp.api_key' => 'test_feature_key_998877',
        'services.sms.whatsapp.sender' => '919811114365',
        'services.sms.whatsapp.footer' => 'Sent via mpwa',
    ]);

    RateLimiter::clear('customer-otp-request');
    RateLimiter::clear('customer-otp-verify');
});

test('1. customer OTP request with WhatsApp driver dispatches HTTP POST and sets session status', function () {
    Http::fake([
        'https://whatsapp.myapi.in.net/send-message' => Http::response([
            'status' => true,
            'msg' => 'Message sent successfully!',
        ], 200),
    ]);

    $response = $this->post(route('customer.otp.request'), [
        'phone' => '9811114365',
    ]);

    $response->assertSessionHas('status')
        ->assertSessionHas('otp_requested', true);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://whatsapp.myapi.in.net/send-message'
            && $request['api_key'] === 'test_feature_key_998877'
            && $request['sender'] === '919811114365'
            && $request['number'] === '919811114365'
            && str_contains($request['message'], 'verification code')
            && $request['footer'] === 'Sent via mpwa';
    });

    $challenge = CustomerOtpChallenge::where('phone_e164', '+919811114365')->first();
    expect($challenge)->not->toBeNull()
        ->and($challenge->attempts)->toBe(0);
});

test('2. customer can verify OTP dispatched via WhatsApp driver and authenticate', function () {
    $capturedOtp = null;

    Http::fake([
        'https://whatsapp.myapi.in.net/send-message' => function ($request) use (&$capturedOtp) {
            preg_match('/(\d{6})/', $request['message'], $matches);
            $capturedOtp = $matches[1] ?? null;

            return Http::response(['status' => true], 200);
        },
    ]);

    $this->post(route('customer.otp.request'), ['phone' => '9811114365']);
    expect($capturedOtp)->not->toBeNull();

    $verifyResponse = $this->post(route('customer.otp.verify'), [
        'phone' => '9811114365',
        'otp' => $capturedOtp,
    ]);

    $verifyResponse->assertRedirect();
    expect(Auth::guard('customer')->check())->toBeTrue();

    // Replay attack with same OTP must fail
    Auth::guard('customer')->logout();
    $replayResponse = $this->post(route('customer.otp.verify'), [
        'phone' => '9811114365',
        'otp' => $capturedOtp,
    ]);

    $replayResponse->assertSessionHasErrors('otp');
    expect(Auth::guard('customer')->check())->toBeFalse();
});

test('3. WhatsApp provider returning status: false prevents challenge creation and does not authenticate user', function () {
    Http::fake([
        'https://whatsapp.myapi.in.net/send-message' => Http::response([
            'status' => false,
            'msg' => 'Sender gateway unreachable',
        ], 200),
    ]);

    $response = $this->post(route('customer.otp.request'), [
        'phone' => '9811114365',
    ]);

    $response->assertSessionHasErrors('phone');
    $response->assertSessionMissing('otp_requested');

    // No active challenge should exist because transaction was rolled back
    $challenge = CustomerOtpChallenge::where('phone_e164', '+919811114365')->first();
    expect($challenge)->toBeNull();
    expect(Auth::guard('customer')->check())->toBeFalse();
});

test('4. WhatsApp provider HTTP 500 error fails safely without leaking provider internals', function () {
    Http::fake([
        'https://whatsapp.myapi.in.net/send-message' => Http::response('Internal Server Error', 500),
    ]);

    $response = $this->post(route('customer.otp.request'), [
        'phone' => '9811114365',
    ]);

    $response->assertSessionHasErrors('phone');
    $response->assertSessionMissing('otp_requested');

    // The user-facing error message must not expose the API key or provider endpoint
    $errorMsg = session('errors')->first('phone');
    expect($errorMsg)->not->toContain('whatsapp.myapi.in.net')
        ->and($errorMsg)->not->toContain('test_feature_key_998877');
});

test('5. rate limiting continues working when using WhatsApp driver', function () {
    Http::fake([
        'https://whatsapp.myapi.in.net/send-message' => Http::response(['status' => true], 200),
    ]);

    $phone = '9811114365';

    // First request succeeds
    $this->post(route('customer.otp.request'), ['phone' => $phone]);

    // Resend cooldown triggers on immediate second request
    $response2 = $this->post(route('customer.otp.request'), ['phone' => $phone]);
    $response2->assertSessionHasErrors('phone');
});
