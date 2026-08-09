<?php
namespace App\Http\Controllers;
use App\Models\Order;
use App\Services\IFreeICloudService;
use Illuminate\Http\Request;

class OrderController extends Controller {
    public function index(Request $request) {
        $q = Order::with('service')->where('user_id', auth()->id())->latest();
        if ($request->filled('status')) $q->where('status', $request->status);
        if ($request->filled('search')) $q->where('imei_serial','like','%'.$request->search.'%');
        return view('orders.index', ['orders' => $q->paginate(20)->withQueryString()]);
    }
    public function show(Order $order) {
        abort_if($order->user_id !== auth()->id() && !auth()->user()->isAdmin(), 403);
        $overallStatus = app(IFreeICloudService::class)->getOverallStatus([
            'fmi_status'        => $order->result_fmi,
            'activation_status' => $order->result_activation,
            'blacklist_status'  => $order->result_blacklist,
        ]);
        return view('orders.show', compact('order','overallStatus'));
    }
}
