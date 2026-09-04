<?php

namespace App\Services\Auth;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA;
    }

    /**
     * Generate a new base32 RFC 6238 TOTP secret key.
     */
    public function generateSecretKey(): string
    {
        return $this->google2fa->generateSecretKey(16, '');
    }

    /**
     * Get the standard otpauth:// URI for authenticator applications.
     */
    public function getOtpAuthUri(string $company, string $holder, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl($company, $holder, $secret);
    }

    /**
     * Render an inline SVG QR code for the otpauth URI in pure PHP.
     */
    public function getQrCodeSvg(string $company, string $holder, string $secret, int $size = 200): string
    {
        $otpAuthUrl = $this->getOtpAuthUri($company, $holder, $secret);

        $renderer = new ImageRenderer(
            new RendererStyle($size),
            new SvgImageBackEnd
        );

        $writer = new Writer($renderer);

        return $writer->writeString($otpAuthUrl);
    }

    /**
     * Verify a 6-digit TOTP code against the secret key.
     */
    public function verifyKey(string $secret, string $code, int $window = 1): bool
    {
        $cleanCode = preg_replace('/\s+/', '', $code);

        if (! is_string($cleanCode) || strlen($cleanCode) !== 6 || ! ctype_digit($cleanCode)) {
            return false;
        }

        return (bool) $this->google2fa->verifyKey($secret, $cleanCode, $window);
    }
}
