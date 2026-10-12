import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

const PERMISSION_LABELS = [
    { key: 'permission1', label: 'Admin' },
    { key: 'permission2', label: 'Can Add' },
    { key: 'permission3', label: 'Can Edit' },
    { key: 'permission4', label: 'Can Delete' },
    { key: 'permission5', label: 'Read Only' },
];
const ADMIN_VALUES = { permission2: true, permission3: true, permission4: true, permission5: false };

// ---------- ฟอร์มเพิ่มผู้ใช้ (แทนหน้า Register ที่ปิดไป) ----------
function AddUserModal({ show, onClose }) {
    const { data, setData, post, processing, errors, reset, clearErrors, transform } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        permission1: false,
        permission2: false,
        permission3: false,
        permission4: false,
        permission5: true, // ค่าเริ่มต้น: อ่านอย่างเดียว
    });
    const [showPassword, setShowPassword] = useState(false);

    const isAdmin = data.permission1;
    const shown = isAdmin ? { ...data, ...ADMIN_VALUES } : data;

    function toggle(key) {
        if (key === 'permission1') {
            setData((prev) => ({ ...prev, permission1: !prev.permission1, ...ADMIN_VALUES }));
        } else {
            setData(key, !data[key]);
        }
    }

    function close() {
        reset();
        clearErrors();
        setShowPassword(false);
        onClose();
    }

    function submit(e) {
        e.preventDefault();
        // Admin → ส่งสิทธิ์ครบตามที่เห็นบนจอ (ฝั่ง server ก็บังคับซ้ำอีกชั้น)
        transform((d) => (d.permission1 ? { ...d, ...ADMIN_VALUES } : d));
        post(route('user-permissions.store'), {
            preserveScroll: true,
            onSuccess: close,
            onFinish: () => reset('password', 'password_confirmation'),
        });
    }

    return (
        <Modal show={show} onClose={close} maxWidth="lg">
            <form onSubmit={submit} className="p-6">
                <h2 className="text-lg font-semibold text-gray-900">เพิ่มผู้ใช้</h2>
                <p className="mt-1 text-sm text-gray-500">
                    สร้างบัญชีให้พนักงาน แล้วแจ้งอีเมลและรหัสผ่านให้เจ้าของบัญชี (เปลี่ยนรหัสเองได้ที่หน้า Profile)
                </p>

                <div className="mt-5 space-y-4">
                    <div>
                        <InputLabel htmlFor="new_name" value="ชื่อ" />
                        <TextInput
                            id="new_name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            className="mt-1 block w-full"
                            isFocused
                            autoComplete="off"
                        />
                        <InputError message={errors.name} className="mt-1" />
                    </div>

                    <div>
                        <InputLabel htmlFor="new_email" value="อีเมล (ใช้ login)" />
                        <TextInput
                            id="new_email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            className="mt-1 block w-full"
                            autoComplete="off"
                        />
                        <InputError message={errors.email} className="mt-1" />
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel htmlFor="new_password" value="รหัสผ่าน" />
                            <TextInput
                                id="new_password"
                                type={showPassword ? 'text' : 'password'}
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                className="mt-1 block w-full"
                                autoComplete="new-password"
                            />
                            <InputError message={errors.password} className="mt-1" />
                        </div>
                        <div>
                            <InputLabel htmlFor="new_password_confirmation" value="ยืนยันรหัสผ่าน" />
                            <TextInput
                                id="new_password_confirmation"
                                type={showPassword ? 'text' : 'password'}
                                value={data.password_confirmation}
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                className="mt-1 block w-full"
                                autoComplete="new-password"
                            />
                        </div>
                    </div>
                    <label className="flex items-center gap-2 text-sm text-gray-600">
                        <input
                            type="checkbox"
                            checked={showPassword}
                            onChange={() => setShowPassword((v) => !v)}
                            className="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        แสดงรหัสผ่าน
                    </label>

                    <fieldset>
                        <legend className="text-sm font-medium text-gray-700">สิทธิ์</legend>
                        <div className="mt-2 flex flex-wrap gap-x-5 gap-y-2">
                            {PERMISSION_LABELS.map(({ key, label }) => (
                                <label key={key} className="flex items-center gap-2 text-sm text-gray-700">
                                    <input
                                        type="checkbox"
                                        checked={shown[key]}
                                        onChange={() => toggle(key)}
                                        disabled={isAdmin && key !== 'permission1'}
                                        className="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50"
                                    />
                                    {label}
                                </label>
                            ))}
                        </div>
                        <p className="mt-1 text-xs text-gray-400">ถ้าไม่เลือกสิทธิ์ใดเลย ระบบจะตั้งเป็น Read Only ให้</p>
                    </fieldset>
                </div>

                <div className="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" onClick={close}>ยกเลิก</SecondaryButton>
                    <PrimaryButton disabled={processing}>{processing ? 'กำลังบันทึก...' : 'เพิ่มผู้ใช้'}</PrimaryButton>
                </div>
            </form>
        </Modal>
    );
}

function UserRow({ user }) {
    const permissionKeys = Array.from({ length: 5 }, (_, i) => `permission${i + 1}`);

    const [permissions, setPermissions] = useState(
        permissionKeys.reduce((acc, key) => {
            acc[key] = user[key] === '1' || user[key] === 1;
            return acc;
        }, {})
    );
    const [saving, setSaving] = useState(false);
    const [saved, setSaved] = useState(false);

    // Admin ได้ Add / Edit / Delete ทั้งหมด และไม่เป็น Read Only (ช่องอื่นล็อกไว้ตอนติ๊ก Admin)
    const isAdmin = permissions.permission1;
    const ADMIN_VALUES = { permission2: true, permission3: true, permission4: true, permission5: false };
    const shown = isAdmin ? { ...permissions, ...ADMIN_VALUES } : permissions;

    function toggle(key) {
        setPermissions((prev) => {
            const next = { ...prev, [key]: !prev[key] };
            // ติ๊ก Admin = ให้สิทธิ์ครบทันที, เอา Admin ออก = ยังคงสิทธิ์ที่เห็นอยู่ไว้ให้ปรับต่อเอง
            return key === 'permission1' ? { ...next, ...ADMIN_VALUES } : next;
        });
        setSaved(false);
    }

    function handleSave() {
        setSaving(true);
        router.patch(route('user-permissions.update', user.id), shown, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                setSaved(true);
                setSaving(false);
            },
            onError: () => setSaving(false),
        });
    }

    return (
        <tr className="transition hover:bg-gray-50">
            <td className="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900">
                {user.name}
                <div className="text-xs text-gray-400">{user.email}</div>
            </td>
            {permissionKeys.map((key) => (
                <td key={key} className="px-2 py-3 text-center">
                    <input
                        type="checkbox"
                        checked={shown[key]}
                        onChange={() => toggle(key)}
                        disabled={isAdmin && key !== 'permission1'}
                        title={isAdmin && key !== 'permission1' ? 'Admin มีสิทธิ์นี้อัตโนมัติ' : undefined}
                        className="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50"
                    />
                </td>
            ))}
            <td className="whitespace-nowrap px-4 py-3 text-right">
                <button
                    onClick={handleSave}
                    disabled={saving}
                    className="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50"
                >
                    {saving ? 'กำลังบันทึก...' : saved ? 'บันทึกแล้ว ✓' : 'Save'}
                </button>
            </td>
        </tr>
    );
}

export default function Index({ users }) {
    const [showAdd, setShowAdd] = useState(false);

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    User Permissions
                </h2>
            }
        >
            <Head title="User Permissions" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p className="text-sm text-gray-500">
                            ผู้ใช้ที่เป็น <span className="font-medium text-gray-700">Admin</span> ได้สิทธิ์ Add, Edit และ Delete ทุกหน้าโดยอัตโนมัติ และไม่ถูกจำกัดเป็น Read Only
                        </p>
                        <button
                            type="button"
                            onClick={() => setShowAdd(true)}
                            className="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700"
                        >
                            <span className="text-base leading-none">+</span>
                            เพิ่มผู้ใช้
                        </button>
                    </div>
                    <AddUserModal show={showAdd} onClose={() => setShowAdd(false)} />
                    <div className="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200 text-sm">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left font-semibold text-gray-600">User</th>
                                            <th> Admin </th>
                                            <th> Can Add</th>
                                            <th> Can Edit </th>
                                            <th> Can Delete </th>
                                            <th> Read Only </th>
                                        <th className="px-4 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {users.map((user) => (
                                        <UserRow key={user.id} user={user} />
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}