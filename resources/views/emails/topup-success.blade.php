<div style="font-family:sans-serif;max-width:480px;margin:0 auto;color:#1e293b;">
    <h2 style="color:#2563eb;">เติมเครดิตสำเร็จ</h2>
    <p>สวัสดีคุณ {{ $topup->user->name }}</p>
    <p>รายการเติมเครดิตของคุณสำเร็จแล้ว:</p>
    <table style="width:100%;border-collapse:collapse;margin:16px 0;">
        <tr><td style="padding:8px 0;color:#64748b;">จำนวนเงิน</td><td style="text-align:right;font-weight:bold;">฿{{ number_format($topup->amount,2) }}</td></tr>
        <tr><td style="padding:8px 0;color:#64748b;">ยอดคงเหลือปัจจุบัน</td><td style="text-align:right;font-weight:bold;">฿{{ number_format($topup->user->balance,2) }}</td></tr>
        <tr><td style="padding:8px 0;color:#64748b;">วันที่</td><td style="text-align:right;">{{ $topup->updated_at->format('d/m/Y H:i') }}</td></tr>
    </table>
    <p style="color:#94a3b8;font-size:12px;">อีเมลนี้ส่งอัตโนมัติจากระบบ iPart Store</p>
</div>
