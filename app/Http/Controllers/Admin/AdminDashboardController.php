<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{User, Order};

class AdminDashboardController extends Controller {
    public function index() {
        $stats = [
            'total_users'    => User::where('role','!=','admin')->count(),
            'total_orders'   => Order::count(),
            'success_orders' => Order::where('status','success')->count(),
            'total_revenue'  => Order::where('status','success')->sum('sell_price'),
            'total_cost'     => Order::where('status','success')->sum('cost_price'),
            'total_profit'   => Order::where('status','success')->selectRaw('SUM(sell_price-cost_price) as p')->value('p') ?? 0,
        ];
        $recentOrders = Order::with(['user','service'])->latest()->limit(10)->get();
        $topUsers     = User::withCount('orders')
            ->withSum(['orders as spent' => fn($q) => $q->where('status','success')],'sell_price')
            ->where('role','!=','admin')->orderByDesc('spent')->limit(5)->get();
        return view('admin.dashboard', compact('stats','recentOrders','topUsers'));
    }
}
