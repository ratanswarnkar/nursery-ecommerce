<?php

use App\Models\CustomerOtpChallenge;
use App\Services\Auth\OtpService;
use App\Services\Sms\LogSmsSender;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    LogSmsSender::clear();
});

test('generates secure 6-digit challenge and stores hashed OTP', function () {
    $sender = new LogSmsSender;
    $service = new OtpService($sender);

    $phone = '+919876543210';
    $challenge = $service->generate($phone, '127.0.0.1', 'PHPUnit');

    expect($challenge)->toBeInstanceOf(CustomerOtpChallenge::class)
        ->and($challenge->phone_e164)->toBe($phone)
        ->and($challenge->otp_hash)->not->toBeEmpty()
        ->and($challenge->verified_at)->toBeNull()
        ->and($challenge->attempts)->toBe(0)
        ->and(count(LogSmsSender::$dispatchedMessages))->toBe(1);

    // Verify dispatched message contains 6 digit code
    $dispatched = LogSmsSender::$dispatchedMessages[0]['message'];
    preg_match('/(\d{6})/', $dispatched, $matches);
    expect($matches)->not->toBeEmpty();
    $plainOtp = $matches[1];

    // Verify hash matches plain OTP
    expect(Hash::check($plainOtp, $challenge->otp_hash))->toBeTrue();
});

test('re-requesting an OTP invalidates previous active challenges for same phone', function () {
    $sender = new LogSmsSender;
    $service = new OtpService($sender);

    $phone = '+919876543210';
    $challenge1 = $service->generate($phone);

    // Artificially advance time past 60s cooldown to allow second generation
    $challenge1->update(['resend_available_at' => now()->subSecond()]);

    $challenge2 = $service->generate($phone);

    // Refresh challenge1: must have been invalidated (expires_at set to past/now)
    $challenge1->refresh();
    expect($challenge1->expires_at->isPast())->toBeTrue()
        ->and($challenge2->expires_at->isFuture())->toBeTrue();
});

test('enforces 60-second resend cooldown', function () {
    $sender = new LogSmsSender;
    $service = new OtpService($sender);

    $phone = '+919876543210';
    $service->generate($phone);

    // Immediate second attempt must fail cooldown check
    expect(fn () => $service->generate($phone))->toThrow(ValidationException::class);
});

test('successful OTP verification marks challenge consumed and cannot be reused', function () {
    $sender = new LogSmsSender;
    $service = new OtpService($sender);

    $phone = '+919876543210';
    $challenge = $service->generate($phone);

    $dispatched = LogSmsSender::$dispatchedMessages[0]['message'];
    preg_match('/(\d{6})/', $dispatched, $matches);
    $plainOtp = $matches[1];

    // First verification succeeds
    $verified = $service->verify($phone, $plainOtp);
    expect($verified)->toBeTrue();

    $challenge->refresh();
    expect($challenge->verified_at)->not->toBeNull();

    // Replay attack / reuse of same OTP must fail immediately
    $reused = $service->verify($phone, $plainOtp);
    expect($reused)->toBeFalse();
});

test('wrong OTP increments attempts and 3 failed attempts block challenge', function () {
    $sender = new LogSmsSender;
    $service = new OtpService($sender);

    $phone = '+919876543210';
    $challenge = $service->generate($phone);

    // 1st wrong attempt
    expect($service->verify($phone, '000000'))->toBeFalse();
    expect($challenge->fresh()->attempts)->toBe(1);

    // 2nd wrong attempt
    expect($service->verify($phone, '111111'))->toBeFalse();
    expect($challenge->fresh()->attempts)->toBe(2);

    // 3rd wrong attempt
    expect($service->verify($phone, '222222'))->toBeFalse();
    expect($challenge->fresh()->attempts)->toBe(3);

    // Now even if correct OTP is supplied, challenge is blocked
    $dispatched = LogSmsSender::$dispatchedMessages[0]['message'];
    preg_match('/(\d{6})/', $dispatched, $matches);
    $plainOtp = $matches[1];

    expect($service->verify($phone, $plainOtp))->toBeFalse();
});

test('expired OTP challenge cannot be verified', function () {
    $sender = new LogSmsSender;
    $service = new OtpService($sender);

    $phone = '+919876543210';
    $challenge = $service->generate($phone);

    $dispatched = LogSmsSender::$dispatchedMessages[0]['message'];
    preg_match('/(\d{6})/', $dispatched, $matches);
    $plainOtp = $matches[1];

    // Artificially expire the challenge
    $challenge->update(['expires_at' => now()->subMinute()]);

    expect($service->verify($phone, $plainOtp))->toBeFalse();
});
