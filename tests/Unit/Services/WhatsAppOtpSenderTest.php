<?php

use App\Services\Sms\WhatsAppSmsSender;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    config([
        'services.sms.whatsapp.api_url' => 'https://whatsapp.myapi.in.net/send-message',
        'services.sms.whatsapp.api_key' => 'test_mock_api_key_xyz123',
        'services.sms.whatsapp.sender' => '919811114365',
        'services.sms.whatsapp.footer' => 'Sent via mpwa',
    ]);
});

test('WhatsApp sender dispatches POST request with exact JSON structure to configured endpoint', function () {
    Http::fake([
        'https://whatsapp.myapi.in.net/send-message' => Http::response([
            'status' => true,
            'msg' => 'Message sent successfully!',
        ], 200),
    ]);

    $sender = new WhatsAppSmsSender;
    $result = $sender->send('+919811114365', 'Your Sugandha Farms & Nursery verification code is 481920.');

    expect($result)->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://whatsapp.myapi.in.net/send-message'
            && $request->method() === 'POST'
            && $request->isJson()
            && $request['api_key'] === 'test_mock_api_key_xyz123'
            && $request['sender'] === '919811114365'
            && $request['number'] === '919811114365'
            && str_contains($request['message'], '481920')
            && $request['footer'] === 'Sent via mpwa';
    });
});

test('formats 10-digit Indian phone number by prepending 91 without plus sign', function () {
    Http::fake([
        'https://whatsapp.myapi.in.net/send-message' => Http::response(['status' => true], 200),
    ]);

    $sender = new WhatsAppSmsSender;
    $sender->send('9811114365', 'Code 123456');

    Http::assertSent(function ($request) {
        return $request['number'] === '919811114365';
    });
});

test('handles numbers with existing 91 prefix without creating duplicate 91', function () {
    Http::fake([
        'https://whatsapp.myapi.in.net/send-message' => Http::response(['status' => true], 200),
    ]);

    $sender = new WhatsAppSmsSender;

    // +91 international format
    $sender->send('+919811114365', 'Code 123456');

    // 91 international format without plus
    $sender->send('919811114365', 'Code 654321');

    Http::assertSent(function ($request) {
        return $request['number'] === '919811114365' && ! str_starts_with($request['number'], '9191');
    });

    expect($sender->formatPhoneNumber('+919811114365'))->toBe('919811114365')
        ->and($sender->formatPhoneNumber('919811114365'))->toBe('919811114365')
        ->and($sender->formatPhoneNumber('9811114365'))->toBe('919811114365')
        ->and($sender->formatPhoneNumber('+91 98111 14365'))->toBe('919811114365');
});

test('omits footer property from JSON payload when footer is empty or null', function () {
    config(['services.sms.whatsapp.footer' => '']);

    Http::fake([
        'https://whatsapp.myapi.in.net/send-message' => Http::response(['status' => true], 200),
    ]);

    $sender = new WhatsAppSmsSender;
    $sender->send('+919811114365', 'Code 123456');

    Http::assertSent(function ($request) {
        return ! array_key_exists('footer', $request->data());
    });
});

test('returns false when provider reports status: false', function () {
    Http::fake([
        'https://whatsapp.myapi.in.net/send-message' => Http::response([
            'status' => false,
            'msg' => 'Sender disconnected or invalid session',
        ], 200),
    ]);

    $sender = new WhatsAppSmsSender;
    $result = $sender->send('+919811114365', 'Code 123456');

    expect($result)->toBeFalse();
});

test('returns false when provider returns HTTP error status 400 or 500', function () {
    Http::fake([
        'https://whatsapp.myapi.in.net/send-message' => Http::response('Server Error', 500),
    ]);

    $sender = new WhatsAppSmsSender;
    $result = $sender->send('+919811114365', 'Code 123456');

    expect($result)->toBeFalse();
});

test('returns false when network timeout or connection exception occurs', function () {
    Http::fake([
        'https://whatsapp.myapi.in.net/send-message' => function () {
            throw new ConnectionException('Connection timed out');
        },
    ]);

    $sender = new WhatsAppSmsSender;
    $result = $sender->send('+919811114365', 'Code 123456');

    expect($result)->toBeFalse();
});

test('returns false when provider returns non-JSON or malformed response', function () {
    Http::fake([
        'https://whatsapp.myapi.in.net/send-message' => Http::response('<html>Bad Gateway</html>', 200),
    ]);

    $sender = new WhatsAppSmsSender;
    $result = $sender->send('+919811114365', 'Code 123456');

    expect($result)->toBeFalse();
});

test('returns false and logs error when API key or required configuration is missing', function () {
    config(['services.sms.whatsapp.api_key' => '']);

    Http::fake();

    $sender = new WhatsAppSmsSender;
    $result = $sender->send('+919811114365', 'Code 123456');

    expect($result)->toBeFalse();
    Http::assertNothingSent();
});

test('does not log the raw API key in application logs during dispatch or failure', function () {
    Log::spy();

    Http::fake([
        'https://whatsapp.myapi.in.net/send-message' => Http::response(['status' => false, 'msg' => 'Failed'], 200),
    ]);

    $sender = new WhatsAppSmsSender;
    $sender->send('+919811114365', 'Code 123456');

    Log::shouldHaveReceived('warning')
        ->withArgs(function ($message, $context) {
            $contextStr = json_encode($context);

            return ! str_contains($contextStr, 'test_mock_api_key_xyz123')
                && ! str_contains($contextStr, '9811114365')
                && str_contains($contextStr, '9198')
                && str_contains($contextStr, '365');
        });
});
