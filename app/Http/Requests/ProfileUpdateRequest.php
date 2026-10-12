<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // อีเมลแก้เองไม่ได้: Portal login ผูกบัญชีด้วยอีเมล — ถ้าเปลี่ยนได้ ผู้ใช้จะเปลี่ยนเป็นอีเมลของคนอื่น
            // แล้วได้บัญชีนั้นเมื่อเจ้าของจริง login ผ่าน portal (ให้ Admin แก้ใน DB หรือสร้างบัญชีใหม่แทน)
        ];
    }
}
