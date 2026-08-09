<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{User, CreditTransaction};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller {
    public function index(Request $request) {
        $q = User::where('role','!=','admin')->withCount('orders')
            ->withSum(['orders as total_spent' => fn($q) => $q->where('status','success')],'sell_price');
        if ($request->filled('search'))
            $q->where(fn($q) => $q->where('name','like','%'.$request->search.'%')->orWhere('email','like','%'.$request->search.'%'));
        return view('admin.users.index', ['users' => $q->latest()->paginate(20)->withQueryString()]);
    }
    public function show(User $user) {
        $transactions = CreditTransaction::where('user_id',$user->id)->latest('created_at')->paginate(20);
        return view('admin.users.show', compact('user','transactions'));
    }
    public function topup(Request $request, User $user) {
        $request->validate(['amount' => 'required|numeric|min:1|max:100000']);
        $before = $user->balance;
        $user->increment('balance', $request->amount);
        $user->refresh();
        CreditTransaction::create([
            'user_id' => $user->id, 'type' => 'topup', 'amount' => $request->amount,
            'balance_before' => $before, 'balance_after' => $user->balance,
            'description' => $request->description ?? 'Admin top-up', 'admin_id' => auth()->id(),
        ]);
        return back()->with('success', "เติมเครดิต ฿".number_format($request->amount,2)." ให้ {$user->name} สำเร็จ");
    }
    public function store(Request $request) {
        $request->validate(['name'=>'required','email'=>'required|email|unique:users','password'=>'required|min:8','role'=>'required|in:user,reseller']);
        User::create(['name'=>$request->name,'email'=>$request->email,'password'=>Hash::make($request->password),'role'=>$request->role]);
        return back()->with('success','สร้างผู้ใช้งานสำเร็จ');
    }
    public function toggleActive(User $user) {
        $user->update(['is_active' => !$user->is_active]);
        return back()->with('success', $user->is_active ? 'เปิดใช้งานแล้ว' : 'ปิดใช้งานแล้ว');
    }
}
