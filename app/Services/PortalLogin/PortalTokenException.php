<?php

namespace App\Services\PortalLogin;

use RuntimeException;

/** เหตุผลที่ token ไม่ผ่าน — ใช้เขียน log เท่านั้น ห้ามแสดงรายละเอียดให้ผู้ใช้ */
final class PortalTokenException extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct("Portal login token rejected: {$reason}");
    }
}
