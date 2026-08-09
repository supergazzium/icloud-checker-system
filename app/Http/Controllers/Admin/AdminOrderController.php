<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class AdminOrderController extends Controller {
    public function index(Request $request) {
        $q = Order::with(['user','service'])->latest();
        if ($request->filled('status'))  $q->where('status',$request->status);
        if ($request->filled('search'))  $q->where('imei_serial','like','%'.$request->search.'%');
        if ($request->filled('user_id')) $q->where('user_id',$request->user_id);
        return view('admin.orders.index', ['orders' => $q->paginate(30)->withQueryString()]);
    }
    public function show(Order $order) {
        return view('admin.orders.show', compact('order'));
    }

    /** Manual admin refund for an order — guards against double refund via existing credit_transactions row. */
    public function refund(Order $order) {
        if (\App\Models\CreditTransaction::where('order_id', $order->id)->where('type', 'refund')->exists()) {
            return back()->with('error', 'คำสั่งนี้ถูกคืนเครดิตไปแล้ว');
        }
        if ($order->status === 'success') {
            return back()->with('error', 'ไม่สามารถคืนเครดิตคำสั่งที่สำเร็จแล้วได้');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($order) {
            $user = $order->user;
            $before = $user->balance;
            $user->increment('balance', $order->sell_price);
            $user->refresh();
            $user->creditTransactions()->create([
                'type' => 'refund', 'amount' => $order->sell_price,
                'balance_before' => $before, 'balance_after' => $user->balance,
                'description' => 'คืนเครดิตโดย Admin: Order #'.$order->id,
                'order_id' => $order->id, 'admin_id' => auth()->id(), 'admin_ip' => request()->ip(),
            ]);
        });

        return back()->with('success', 'คืนเครดิต ฿'.number_format($order->sell_price,2).' ให้ลูกค้าสำเร็จ');
    }

    /** CSV export honoring the same filters as the index list — opens fine in Excel. */
    public function export(Request $request) {
        $q = Order::with(['user','service']);
        if ($request->filled('status'))  $q->where('status',$request->status);
        if ($request->filled('search'))  $q->where('imei_serial','like','%'.$request->search.'%');
        if ($request->filled('user_id')) $q->where('user_id',$request->user_id);
        if ($request->filled('date_from')) $q->whereDate('created_at','>=',$request->date_from);
        if ($request->filled('date_to'))   $q->whereDate('created_at','<=',$request->date_to);

        $filename = 'orders-export-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($q) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel renders Thai correctly
            fputcsv($out, ['Order ID','วันที่','ผู้ใช้','อีเมล','บริการ','IMEI/Serial','สถานะ','ราคาทุน','ราคาขาย','กำไร','รุ่นเครื่อง']);
            $q->orderBy('id')->chunk(500, function ($orders) use ($out) {
                foreach ($orders as $o) {
                    fputcsv($out, [
                        $o->id, $o->created_at->format('Y-m-d H:i:s'),
                        $o->user->name ?? '-', $o->user->email ?? '-',
                        $o->service->name_th ?? '-', $o->imei_serial, $o->status,
                        $o->cost_price, $o->sell_price, $o->profit, $o->result_model ?? '-',
                    ]);
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
