<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PortalLogin\PortalTokenException;
use App\Services\PortalLogin\PortalTokenVerifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * เข้าสู่ระบบผ่าน RITTA Portal กลาง
 *
 * GET /login/portal          → ส่งผู้ใช้ไปหน้า login ของ portal
 * GET /auth/portal/callback  → portal ส่งกลับมาพร้อม ?e=<token>
 */
class PortalLoginController extends Controller
{
    /** ข้อความเดียวสำหรับทุกกรณีที่ token ไม่ผ่าน — ไม่บอกเหตุผลจริงให้คนภายนอกเดา */
    private const GENERIC_ERROR = 'ลิงก์เข้าสู่ระบบไม่ถูกต้องหรือหมดอายุ กรุณาเข้าสู่ระบบผ่าน Portal อีกครั้ง';

    public function redirect(): RedirectResponse
    {
        $url = (string) config('services.portal.login_url');
        abort_if($url === '', 404);

        return redirect()->away($url);
    }

    public function callback(Request $request): RedirectResponse
    {
        $config = config('services.portal');

        // base64 มีเครื่องหมาย + ถ้า portal ไม่ได้ urlencode มา PHP จะแปลง + เป็นช่องว่าง → แปลงกลับ
        $token = str_replace(' ', '+', (string) $request->query('e', ''));

        $verifier = new PortalTokenVerifier(
            secret: (string) ($config['secret'] ?? ''),
            allowedDomain: (string) ($config['allowed_domain'] ?? 'ritta.co.th'),
            ttlSeconds: (int) ($config['ttl'] ?? 300),
            clockSkewSeconds: (int) ($config['clock_skew'] ?? 30),
        );

        try {
            $claims = $verifier->verify($token);
        } catch (PortalTokenException $e) {
            if ($e->reason === 'secret_not_configured') {
                Log::critical('Portal login: PORTAL_LOGIN_HMAC_SECRET is not set — all portal logins are rejected');
            } else {
                Log::warning('Portal login rejected', ['reason' => $e->reason, 'ip' => $request->ip()]);
            }

            return $this->fail(self::GENERIC_ERROR);
        }

        // ---- token ใช้ได้ครั้งเดียว: จองลายเซ็นไว้ใน cache จนกว่า token จะหมดอายุ ----
        // Cache::add เป็น atomic: ถ้ามี key อยู่แล้ว (เคยใช้) จะคืน false
        $ttl = (int) ($config['ttl'] ?? 300) + (int) ($config['clock_skew'] ?? 30) + 60;
        if (!Cache::add('portal-login:used:' . hash('sha256', $claims['signature']), true, $ttl)) {
            Log::warning('Portal login rejected', ['reason' => 'replayed', 'email' => $claims['email'], 'ip' => $request->ip()]);

            return $this->fail(self::GENERIC_ERROR);
        }

        $user = User::where('email', $claims['email'])->first();

        if (!$user && ($config['auto_create_users'] ?? false)) {
            // สร้างบัญชีใหม่เป็น Read Only — Admin ค่อยให้สิทธิ์เพิ่มในหน้า User Permissions
            $user = User::create([
                'name'        => Str::before($claims['email'], '@'),
                'email'       => $claims['email'],
                'password'    => Hash::make(Str::random(64)), // ใช้ login ด้วยรหัสผ่านไม่ได้จนกว่าจะรีเซ็ตเอง
                'permission1' => '0',
                'permission2' => '0',
                'permission3' => '0',
                'permission4' => '0',
                'permission5' => '1',
            ]);
            Log::info('Portal login: created read-only user', ['user_id' => $user->id, 'email' => $user->email]);
        }

        if (!$user) {
            Log::notice('Portal login: no local account', ['email' => $claims['email'], 'ip' => $request->ip()]);

            return $this->fail("ไม่พบบัญชี {$claims['email']} ในระบบ KL-5 กรุณาติดต่อผู้ดูแลระบบเพื่อเปิดบัญชี");
        }

        // ---- login: ออก session ใหม่ทั้งหมด กัน session fixation ----
        if (Auth::check()) {
            Auth::guard('web')->logout();
        }
        $request->session()->invalidate();
        Auth::login($user);
        $request->session()->regenerate();

        Log::info('Portal login success', ['user_id' => $user->id, 'ip' => $request->ip()]);

        // ไม่ใช้ redirect()->intended() — ปลายทางต้องเป็นหน้าในระบบเท่านั้น
        return $this->noReferrer(redirect()->route('dashboard'));
    }

    private function fail(string $message): RedirectResponse
    {
        return $this->noReferrer(redirect()->route('login')->withErrors(['portal' => $message]));
    }

    /** URL callback มี token อยู่ — ห้ามส่งต่อไปใน Referer ของหน้าถัดไป */
    private function noReferrer(RedirectResponse $response): RedirectResponse
    {
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
