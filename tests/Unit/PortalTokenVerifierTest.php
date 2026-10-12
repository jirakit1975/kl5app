<?php

namespace Tests\Unit;

use App\Services\PortalLogin\PortalTokenException;
use App\Services\PortalLogin\PortalTokenVerifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PortalTokenVerifierTest extends TestCase
{
    private const SECRET = 'test-secret-0123456789abcdef0123456789abcdef';
    private const NOW = 1_800_000_000;

    private function token(string $email, int $ts, string $secret = self::SECRET): string
    {
        $b64 = base64_encode($email);

        return $b64 . '.' . $ts . '.' . hash_hmac('sha256', $b64 . '.' . $ts, $secret);
    }

    private function verifier(string $secret = self::SECRET): PortalTokenVerifier
    {
        return new PortalTokenVerifier($secret);
    }

    public function test_valid_token_returns_lowercased_email(): void
    {
        $claims = $this->verifier()->verify($this->token('Somchai.J@RITTA.co.th', self::NOW - 10), self::NOW);

        $this->assertSame('somchai.j@ritta.co.th', $claims['email']);
    }

    public function test_uppercase_hex_signature_is_accepted(): void
    {
        [$a, $b, $sig] = explode('.', $this->token('a@ritta.co.th', self::NOW));

        $claims = $this->verifier()->verify("{$a}.{$b}." . strtoupper($sig), self::NOW);
        $this->assertSame('a@ritta.co.th', $claims['email']);
    }

    public function test_token_at_exactly_300_seconds_is_accepted(): void
    {
        $claims = $this->verifier()->verify($this->token('a@ritta.co.th', self::NOW - 300), self::NOW);
        $this->assertSame('a@ritta.co.th', $claims['email']);
    }

    #[DataProvider('rejectedTokens')]
    public function test_rejected_tokens(string $token, string $reason): void
    {
        try {
            $this->verifier()->verify($token, self::NOW);
            $this->fail("Token should be rejected ({$reason})");
        } catch (PortalTokenException $e) {
            $this->assertSame($reason, $e->reason);
        }
    }

    public static function rejectedTokens(): array
    {
        $t = fn (string $email, int $ts, string $secret = self::SECRET) => (function () use ($email, $ts, $secret) {
            $b64 = base64_encode($email);
            return $b64 . '.' . $ts . '.' . hash_hmac('sha256', $b64 . '.' . $ts, $secret);
        })();
        $valid = $t('a@ritta.co.th', self::NOW);
        [$b64, $ts, $sig] = explode('.', $valid);

        return [
            'empty'                 => ['', 'bad_length'],
            'two parts'             => ["{$b64}.{$ts}", 'bad_format'],
            'four parts'            => ["{$valid}.x", 'bad_format'],
            'wrong secret'          => [$t('a@ritta.co.th', self::NOW, 'other-secret'), 'bad_signature'],
            'tampered email'        => [base64_encode('boss@ritta.co.th') . ".{$ts}.{$sig}", 'bad_signature'],
            'tampered timestamp'    => ["{$b64}." . (self::NOW + 1) . ".{$sig}", 'bad_signature'],
            'short signature'       => ["{$b64}.{$ts}." . substr($sig, 0, 63), 'bad_signature_format'],
            'non-hex signature'     => ["{$b64}.{$ts}." . str_repeat('z', 64), 'bad_signature_format'],
            'milliseconds'          => ["{$b64}." . (self::NOW * 1000) . ".{$sig}", 'bad_timestamp'],
            'expired (301s)'        => [$t('a@ritta.co.th', self::NOW - 301), 'expired'],
            'future (> skew)'       => [$t('a@ritta.co.th', self::NOW + 31), 'timestamp_in_future'],
            'other domain'          => [$t('a@gmail.com', self::NOW), 'domain_not_allowed'],
            'subdomain'             => [$t('a@mail.ritta.co.th', self::NOW), 'domain_not_allowed'],
            'lookalike domain'      => [$t('a@evilritta.co.th', self::NOW), 'domain_not_allowed'],
            'suffix trick'          => [$t('a@ritta.co.th.evil.com', self::NOW), 'domain_not_allowed'],
            'two @'                 => [$t('a@b@ritta.co.th', self::NOW), 'bad_email'],
            'newline in email'      => [$t("a@ritta.co.th\nx", self::NOW), 'bad_email'],
            'not an email'          => [$t('ritta.co.th', self::NOW), 'bad_email'],
        ];
    }

    public function test_empty_secret_rejects_everything(): void
    {
        $this->expectException(PortalTokenException::class);
        $this->verifier('')->verify($this->token('a@ritta.co.th', self::NOW, ''), self::NOW);
    }
}
