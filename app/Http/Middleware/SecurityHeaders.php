<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header ความปลอดภัยพื้นฐานทุกหน้า
 * (ไม่ทับค่าที่ controller ตั้งเอง เช่น Referrer-Policy: no-referrer ของ portal callback)
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $defaults = [
            'X-Frame-Options'        => 'SAMEORIGIN',                       // กันการฝังหน้าในเว็บอื่น (clickjacking)
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy'        => 'strict-origin-when-cross-origin',
            'Permissions-Policy'     => 'camera=(), microphone=(), geolocation=()',
        ];

        foreach ($defaults as $name => $value) {
            if (!$response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        return $response;
    }
}
