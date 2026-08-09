<?php
namespace App\Http\Controllers;
use App\Models\{Order, CreditTransaction};

class DashboardController extends Controller {
    public function index() {
        $user  = auth()->user();
        $stats = [
            'total_orders'   => Order::where('user_id', $user->id)->count(),
            'success_orders' => Order::where('user_id', $user->id)->where('status','success')->count(),
            'error_orders'   => Order::where('user_id', $user->id)->where('status','error')->count(),
            'total_spent'    => Order::where('user_id', $user->id)->where('status','success')->sum('sell_price'),
            'balance'        => $user->balance,
        ];
        $recentOrders  = Order::with('service')->where('user_id', $user->id)->latest()->limit(5)->get();
        $recentCredits = CreditTransaction::where('user_id', $user->id)->latest('created_at')->limit(5)->get();
        return view('dashboard', compact('stats','recentOrders','recentCredits'));
    }
}
