<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; color:#1e293b; font-size:12px; }
    .header { text-align:center; margin-bottom:20px; }
    .header h1 { font-size:16px; margin:0; }
    .header p { color:#64748b; font-size:10px; margin:2px 0 0; }
    table { width:100%; border-collapse:collapse; margin-top:16px; }
    td { padding:6px 0; border-bottom:1px solid #e2e8f0; }
    td.label { color:#64748b; width:45%; }
    td.value { text-align:right; font-weight:bold; }
    .total { font-size:16px; color:#2563eb; }
    .footer { margin-top:24px; text-align:center; color:#94a3b8; font-size:9px; }
</style>
</head>
<body>
    <div class="header">
        <h1>iPart Store</h1>
        <p>ใบเสร็จรับเงิน / Receipt</p>
    </div>
    <table>
        <tr><td class="label">เลขที่รายการ</td><td class="value">#{{ str_pad($tx->id, 6, '0', STR_PAD_LEFT) }}</td></tr>
        <tr><td class="label">วันที่</td><td class="value">{{ $tx->created_at->format('d/m/Y H:i') }}</td></tr>
        <tr><td class="label">ชื่อบัญชี</td><td class="value">{{ $user->name }}</td></tr>
        <tr><td class="label">อีเมล</td><td class="value">{{ $user->email }}</td></tr>
        <tr><td class="label">ประเภทรายการ</td><td class="value">{{ $tx->type === 'topup' ? 'เติมเครดิต' : ($tx->type === 'refund' ? 'คืนเครดิต' : 'ตัดเครดิต') }}</td></tr>
        <tr><td class="label">รายละเอียด</td><td class="value">{{ $tx->description ?? '-' }}</td></tr>
        <tr><td class="label">ยอดก่อนหน้า</td><td class="value">฿{{ number_format($tx->balance_before,2) }}</td></tr>
        <tr><td class="label total">จำนวนเงิน</td><td class="value total">{{ $tx->amount >= 0 ? '+' : '' }}฿{{ number_format($tx->amount,2) }}</td></tr>
        <tr><td class="label">ยอดคงเหลือ</td><td class="value">฿{{ number_format($tx->balance_after,2) }}</td></tr>
    </table>
    <div class="footer">
        เอกสารนี้สร้างโดยระบบอัตโนมัติ iPart Store — ไม่จำเป็นต้องมีลายเซ็น<br>
        Generated automatically — no signature required
    </div>
</body>
</html>
