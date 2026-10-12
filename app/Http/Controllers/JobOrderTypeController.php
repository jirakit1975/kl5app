<?php

namespace App\Http\Controllers;


use App\Models\JobOrderType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JobOrderTypeController extends Controller
{
    public function index()
    {
        return Inertia::render('Setting/JobOrderType', [
            'items' => JobOrderType::orderBy('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_if($user->permission5 == '1' || $user->permission2 != '1', 403, 'คุณไม่มีสิทธิ์เพิ่มข้อมูล');

        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|size:7|unique:code,code',
            'type_code' => 'required|integer|in:0,1,2',
        ], [
            'code.required' => 'กรุณาใส่รหัส',
            'code.size'     => 'รหัสต้องยาว 7 ตัวอักษร ตามรหัสทรัพย์สิน 7 ตัวแรก เช่น RT-HCXX',
            'code.unique'   => 'มีรหัสนี้อยู่แล้ว',
        ]);

        $validated['name'] = trim($validated['name']);
        JobOrderType::create($validated);

        return redirect()->route('job-order-types.index')->with('success', 'เพิ่มรายการสำเร็จ');
    }

    /**
     * PATCH /job-order-types/{jobOrderType}/type — สลับประเภท MT / TEMP / LGT ของรายการ
     * มีผลกับใบสั่งงานที่สร้างใหม่หลังจากนี้เท่านั้น ใบสั่งงานเดิมเก็บประเภทของตัวเองไว้แล้ว
     */
    public function updateType(Request $request, JobOrderType $jobOrderType)
    {
        $user = $request->user();
        abort_if($user->permission5 == '1' || $user->permission3 != '1', 403, 'คุณไม่มีสิทธิ์แก้ไขข้อมูล');

        $validated = $request->validate([
            'type_code' => 'required|integer|in:0,1,2',
        ]);

        $jobOrderType->update(['type_code' => (int) $validated['type_code']]);

        return back();
    }

    /**
     * PATCH /job-order-types/{jobOrderType} — แก้ชื่อรายการ (Name)
     */
    public function update(Request $request, JobOrderType $jobOrderType)
    {
        $user = $request->user();
        abort_if($user->permission5 == '1' || $user->permission3 != '1', 403, 'คุณไม่มีสิทธิ์แก้ไขข้อมูล');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ], [
            'name.required' => 'กรุณาใส่ชื่อ',
            'name.max'      => 'ชื่อยาวเกิน 255 ตัวอักษร',
        ]);

        $jobOrderType->update(['name' => trim($validated['name'])]);

        return back();
    }

    public function destroy(Request $request, JobOrderType $jobOrderType)
    {
        $user = $request->user();
        abort_if($user->permission5 == '1' || $user->permission4 != '1', 403, 'คุณไม่มีสิทธิ์ลบข้อมูล');

        $jobOrderType->delete();

        return redirect()->route('job-order-types.index')->with('success', 'ลบรายการสำเร็จ');
    }

}