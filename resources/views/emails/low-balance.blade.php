<div style="font-family:sans-serif;max-width:480px;margin:0 auto;color:#1e293b;">
    <h2 style="color:#dc2626;">แจ้งเตือนเครดิตต่ำ</h2>
    <p>สวัสดีคุณ {{ $user->name }}</p>
    <p>เครดิตคงเหลือของคุณต่ำกว่าเกณฑ์ที่กำหนดแล้ว:</p>
    <table style="width:100%;border-collapse:collapse;margin:16px 0;">
        <tr><td style="padding:8px 0;color:#64748b;">ยอดคงเหลือ</td><td style="text-align:right;font-weight:bold;color:#dc2626;">฿{{ number_format($user->balance,2) }}</td></tr>
    </table>
    <p>กรุณาเติมเครดิตเพื่อให้สามารถตรวจสอบอุปกรณ์ได้อย่างต่อเนื่อง</p>
    <p style="color:#94a3b8;font-size:12px;">อีเมลนี้ส่งอัตโนมัติจากระบบ iPart Store</p>
</div>
