import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

/**
 * แสดงข้อความจาก controller ที่ส่งมาด้วย ->with('success' | 'error', '...')
 * ขึ้นมุมขวาบน แล้วหายเองใน 4 วินาที (error ค้างไว้จนกดปิด)
 */
export default function FlashMessage() {
    const page = usePage();
    const flash = page.props.flash ?? {};
    const [message, setMessage] = useState(null);

    // page เปลี่ยนทุกครั้งที่มีการโหลดหน้า/ส่งฟอร์ม → ข้อความเดิมซ้ำก็ยังแสดงใหม่ได้
    useEffect(() => {
        if (flash.error) setMessage({ type: 'error', text: flash.error });
        else if (flash.success) setMessage({ type: 'success', text: flash.success });
        else setMessage(null);
    }, [page]);

    useEffect(() => {
        if (message?.type !== 'success') return;
        const t = setTimeout(() => setMessage(null), 4000);
        return () => clearTimeout(t);
    }, [message]);

    if (!message) return null;

    const isError = message.type === 'error';

    return (
        <div className="pointer-events-none fixed inset-x-0 top-4 z-50 flex justify-center px-4 sm:justify-end print:hidden">
            <div
                role={isError ? 'alert' : 'status'}
                className={`pointer-events-auto flex max-w-md items-start gap-3 rounded-lg px-4 py-3 text-sm shadow-lg ring-1 ${
                    isError
                        ? 'bg-rose-50 text-rose-800 ring-rose-200'
                        : 'bg-emerald-50 text-emerald-800 ring-emerald-200'
                }`}
            >
                <span className="flex-1">{message.text}</span>
                <button
                    type="button"
                    onClick={() => setMessage(null)}
                    aria-label="ปิด"
                    className="shrink-0 opacity-60 transition hover:opacity-100"
                >
                    ✕
                </button>
            </div>
        </div>
    );
}
