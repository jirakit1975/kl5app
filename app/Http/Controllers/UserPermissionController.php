<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class UserPermissionController extends Controller
{
    public function index()
    {
        $users = User::select(
            'id', 'name', 'email',
            'permission1', 'permission2', 'permission3', 'permission4', 'permission5',
            'permission6', 'permission7', 'permission8', 'permission9', 'permission10'
        )->orderBy('id')->get();

        return Inertia::render('Admin/UserPermission', [
            'users' => $users,
        ]);
    }

    /**
     * POST /user-permissions — Admin สร้างบัญชีผู้ใช้ใหม่ 
     */
    public function store(Request $request)
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|string|email|max:255|unique:' . User::class,
            'password'    => ['required', 'confirmed', Rules\Password::defaults()],
            'permission1' => 'nullable|boolean',
            'permission2' => 'nullable|boolean',
            'permission3' => 'nullable|boolean',
            'permission4' => 'nullable|boolean',
            'permission5' => 'nullable|boolean',
        ], [
            'name.required'      => 'กรุณาใส่ชื่อ',
            'email.required'     => 'กรุณาใส่อีเมล',
            'email.email'        => 'รูปแบบอีเมลไม่ถูกต้อง',
            'email.unique'       => 'อีเมลนี้มีผู้ใช้แล้ว',
            'password.required'  => 'กรุณาตั้งรหัสผ่าน',
            'password.confirmed' => 'ยืนยันรหัสผ่านไม่ตรงกัน',
            'password.min'       => 'รหัสผ่านต้องมีอย่างน้อย :min ตัวอักษร',
        ]);

        $flag = fn (string $key) => !empty($data[$key]) ? '1' : '0';
        $isAdmin = $flag('permission1') === '1';

        $permissions = [
            'permission1' => $flag('permission1'),
            // Admin ได้ Add / Edit / Delete ครบ และไม่เป็น Read Only (เหมือนหน้าแก้สิทธิ์)
            'permission2' => $isAdmin ? '1' : $flag('permission2'),
            'permission3' => $isAdmin ? '1' : $flag('permission3'),
            'permission4' => $isAdmin ? '1' : $flag('permission4'),
            'permission5' => $isAdmin ? '0' : $flag('permission5'),
        ];

        // ไม่ได้ให้สิทธิ์อะไรเลย → ตั้งเป็น Read Only ไว้ก่อน (เหมือนการสมัครเองแบบเดิม)
        if (!in_array('1', $permissions, true)) {
            $permissions['permission5'] = '1';
        }

        $user = User::create([
            'name'     => trim($data['name']),
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ] + $permissions);

        return back()->with('success', "เพิ่มผู้ใช้ {$user->name} ({$user->email}) แล้ว");
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'permission1' => 'nullable|boolean',
            'permission2' => 'nullable|boolean',
            'permission3' => 'nullable|boolean',
            'permission4' => 'nullable|boolean',
            'permission5' => 'nullable|boolean',
            'permission6' => 'nullable|boolean',
            'permission7' => 'nullable|boolean',
            'permission8' => 'nullable|boolean',
            'permission9' => 'nullable|boolean',
            'permission10' => 'nullable|boolean',
        ]);

        foreach ($validated as $key => $value) {
            $validated[$key] = $value ? '1' : '0';
        }

        $user->update($validated);

        return back();
    }
}