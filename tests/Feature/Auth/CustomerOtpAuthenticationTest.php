<?php

use App\Models\Customer;
use App\Models\CustomerOtpChallenge;
use App\Services\Sms\LogSmsSender;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    LogSmsSender::clear();
    RateLimiter::clear('customer-otp-request');
    RateLimiter::clear('customer-otp-verify');
});

test('1. customer OTP request succeeds and stores hashed challenge', function () {
    $response = $this->post(route('customer.otp.request'), [
        'phone' => '9876543210',
    ]);

    $response->assertSessionHas('status')
        ->assertSessionHas('otp_requested', true);

    $challenge = CustomerOtpChallenge::where('phone_e164', '+919876543210')->first();
    expect($challenge)->not->toBeNull()
        ->and($challenge->otp_hash)->not->toBe('123456');

    // Message sent via safe SMS abstraction
    expect(LogSmsSender::$dispatchedMessages)->toHaveCount(1);
    $msg = LogSmsSender::$dispatchedMessages[0]['message'];
    preg_match('/(\d{6})/', $msg, $matches);
    $plainOtp = $matches[1];

    expect(Hash::check($plainOtp, $challenge->otp_hash))->toBeTrue();
});

test('2. new OTP request invalidates prior active challenges for that phone', function () {
    $this->post(route('customer.otp.request'), ['phone' => '9876543210']);
    $challenge1 = CustomerOtpChallenge::where('phone_e164', '+919876543210')->latest('id')->first();

    // Advance cooldown
    $challenge1->update(['resend_available_at' => now()->subSecond()]);

    $this->post(route('customer.otp.request'), ['phone' => '9876543210']);
    $challenge2 = CustomerOtpChallenge::where('phone_e164', '+919876543210')->latest('id')->first();

    expect($challenge1->fresh()->expires_at->isPast())->toBeTrue()
        ->and($challenge2->expires_at->isFuture())->toBeTrue()
        ->and($challenge1->id)->not->toBe($challenge2->id);
});

test('3. resend cooldown rejects requests before 60 seconds elapses', function () {
    $this->post(route('customer.otp.request'), ['phone' => '9876543210']);

    // Immediate second attempt within 60s must fail
    $response = $this->post(route('customer.otp.request'), ['phone' => '9876543210']);
    $response->assertSessionHasErrors('phone');
});

test('4. OTP request rate limiter blocks more than 3 requests per 10 minutes', function () {
    $phone = '9876543210';

    for ($i = 0; $i < 3; $i++) {
        $this->post(route('customer.otp.request'), ['phone' => $phone]);
        // bypass cooldown to test rate limiter specifically
        CustomerOtpChallenge::where('phone_e164', '+919876543210')->update(['resend_available_at' => now()->subSecond()]);
    }

    // 4th attempt must hit HTTP 429 rate limiter
    $response = $this->post(route('customer.otp.request'), ['phone' => $phone]);
    $response->assertStatus(429);
});

test('5. valid OTP verifies and authenticates customer on customer guard', function () {
    $this->post(route('customer.otp.request'), ['phone' => '9876543210']);
    $msg = LogSmsSender::$dispatchedMessages[0]['message'];
    preg_match('/(\d{6})/', $msg, $matches);
    $plainOtp = $matches[1];

    $response = $this->post(route('customer.otp.verify'), [
        'phone' => '9876543210',
        'otp' => $plainOtp,
    ]);

    $response->assertRedirect(route('customer.home'));
    expect(Auth::guard('customer')->check())->toBeTrue()
        ->and(Auth::guard('customer')->user()->phone)->toBe('+919876543210');

    // Challenge consumed
    $challenge = CustomerOtpChallenge::where('phone_e164', '+919876543210')->first();
    expect($challenge->verified_at)->not->toBeNull();

    // Replay attack fails
    $replayResponse = $this->post(route('customer.otp.verify'), [
        'phone' => '9876543210',
        'otp' => $plainOtp,
    ]);
    $replayResponse->assertSessionHasErrors('otp');
});

test('6. wrong OTP increments attempts and 3 failed attempts block challenge', function () {
    $this->post(route('customer.otp.request'), ['phone' => '9876543210']);
    $msg = LogSmsSender::$dispatchedMessages[0]['message'];
    preg_match('/(\d{6})/', $msg, $matches);
    $plainOtp = $matches[1];

    // 3 wrong attempts
    $this->post(route('customer.otp.verify'), ['phone' => '9876543210', 'otp' => '000000']);
    $this->post(route('customer.otp.verify'), ['phone' => '9876543210', 'otp' => '111111']);
    $this->post(route('customer.otp.verify'), ['phone' => '9876543210', 'otp' => '222222']);

    $challenge = CustomerOtpChallenge::where('phone_e164', '+919876543210')->first();
    expect($challenge->attempts)->toBe(3);

    // Correct OTP now rejected because challenge is blocked
    $response = $this->post(route('customer.otp.verify'), [
        'phone' => '9876543210',
        'otp' => $plainOtp,
    ]);
    $response->assertSessionHasErrors('otp');
    expect(Auth::guard('customer')->check())->toBeFalse();
});

test('7. blocked/inactive customer cannot authenticate', function () {
    $customer = Customer::factory()->create([
        'phone' => '+919876543210',
        'is_active' => false,
    ]);

    $this->post(route('customer.otp.request'), ['phone' => '9876543210']);
    $msg = LogSmsSender::$dispatchedMessages[0]['message'];
    preg_match('/(\d{6})/', $msg, $matches);
    $plainOtp = $matches[1];

    $response = $this->post(route('customer.otp.verify'), [
        'phone' => '9876543210',
        'otp' => $plainOtp,
    ]);

    $response->assertSessionHasErrors('phone');
    expect(Auth::guard('customer')->check())->toBeFalse();
});

test('8. customer logout terminates session', function () {
    $customer = Customer::factory()->create(['phone' => '+919876543210']);
    Auth::guard('customer')->login($customer);
    session(['customer_auth_token_version' => $customer->auth_token_version]);

    expect(Auth::guard('customer')->check())->toBeTrue();

    $response = $this->post(route('customer.logout'));
    $response->assertRedirect(route('customer.login'));

    expect(Auth::guard('customer')->check())->toBeFalse();
});
