<?php

namespace App\Services\PortalLogin;

/**
 * ตรวจ token ที่ RITTA Portal ส่งกลับมาทาง ?e=
 *
 *   token = base64(email) . "." . timestamp . "." . hex(hash_hmac('sha256', base64(email) . "." . timestamp, secret))
 *
 * - ลายเซ็นคือการ "รับรอง" ว่า portal ออก token นี้ ไม่ได้เข้ารหัส (ใครเห็น token ก็อ่านอีเมลได้)
 * - คลาสนี้ไม่แตะ DB / cache / session — การกัน token ใช้ซ้ำทำใน PortalLoginController
 * - ทุกกรณีที่ไม่ผ่านจะโยน PortalTokenException พร้อมเหตุผล (ไว้เขียน log ไม่แสดงให้ผู้ใช้เห็น)
 */
final class PortalTokenVerifier
{
    private const MAX_TOKEN_LENGTH = 1024;

    public function __construct(
        private readonly string $secret,
        private readonly string $allowedDomain = 'ritta.co.th',
        private readonly int $ttlSeconds = 300,
        private readonly int $clockSkewSeconds = 30,
    ) {
    }

    /**
     * @return array{email: string, timestamp: int, signature: string}
     *
     * @throws PortalTokenException
     */
    public function verify(string $token, ?int $now = null): array
    {
        $now ??= time();

        // ---- 0. ต้องมี secret เสมอ (ไม่มี = ปฏิเสธทุก token ไม่ใช่ข้ามการตรวจ) ----
        if ($this->secret === '') {
            throw new PortalTokenException('secret_not_configured');
        }

        // ---- 1. รูปแบบ token ----
        if ($token === '' || strlen($token) > self::MAX_TOKEN_LENGTH) {
            throw new PortalTokenException('bad_length');
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new PortalTokenException('bad_format');
        }
        [$emailB64, $timestamp, $signature] = $parts;

        if (!preg_match('/^[A-Za-z0-9+\/_-]+={0,2}$/', $emailB64)) {
            throw new PortalTokenException('bad_email_encoding');
        }
        // Unix time หน่วยวินาที (9–11 หลัก) — ถ้า portal ส่งมิลลิวินาทีมาจะตกที่นี่
        if (!preg_match('/^\d{9,11}$/', $timestamp)) {
            throw new PortalTokenException('bad_timestamp');
        }
        if (!preg_match('/^[0-9a-fA-F]{64}$/', $signature)) {
            throw new PortalTokenException('bad_signature_format');
        }

        // ---- 2. ลายเซ็น: คำนวณจากข้อความที่ได้รับมาตรงตัว แล้วเทียบแบบ timing-safe ----
        $expected = hash_hmac('sha256', $emailB64 . '.' . $timestamp, $this->secret);
        if (!hash_equals($expected, strtolower($signature))) {
            throw new PortalTokenException('bad_signature');
        }

        // ---- 3. อายุ token (ตรวจหลังลายเซ็น เพราะ timestamp เชื่อได้ก็ต่อเมื่อเซ็นถูก) ----
        $ts = (int) $timestamp;
        if ($ts > $now + $this->clockSkewSeconds) {
            throw new PortalTokenException('timestamp_in_future');
        }
        if ($now - $ts > $this->ttlSeconds) {
            throw new PortalTokenException('expired');
        }

        // ---- 4. อีเมล ----
        $email = $this->decodeEmail($emailB64);

        return [
            'email'     => $email,
            'timestamp' => $ts,
            'signature' => strtolower($signature),
        ];
    }

    private function decodeEmail(string $emailB64): string
    {
        $decoded = base64_decode($emailB64, true);
        if ($decoded === false) {
            // เผื่อ portal ใช้ base64 แบบ URL-safe (- _ แทน + /)
            $decoded = base64_decode(strtr($emailB64, '-_', '+/'), true);
        }
        if ($decoded === false || $decoded === '') {
            throw new PortalTokenException('bad_email_encoding');
        }

        $email = strtolower(trim($decoded));

        if (strlen($email) > 254
            || preg_match('/[\x00-\x1F\x7F\s]/', $email)
            || filter_var($email, FILTER_VALIDATE_EMAIL) === false
            || substr_count($email, '@') !== 1) {
            throw new PortalTokenException('bad_email');
        }

        // รับเฉพาะ @ritta.co.th ตรงตัว (ไม่รับ subdomain หรือโดเมนที่ลงท้ายคล้ายกัน)
        $domain = substr($email, strrpos($email, '@') + 1);
        if (!hash_equals(strtolower($this->allowedDomain), $domain)) {
            throw new PortalTokenException('domain_not_allowed');
        }

        return $email;
    }
}
