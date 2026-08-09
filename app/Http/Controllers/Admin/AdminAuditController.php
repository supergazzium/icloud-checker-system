<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\CreditTransaction;
use Illuminate\Http\Request;

class AdminAuditController extends Controller {
    /** Global audit trail of every credit change an admin made. */
    public function index(Request $request) {
        $q = CreditTransaction::with(['user','admin'])->whereNotNull('admin_id');
        if ($request->filled('search')) {
            $q->whereHas('user', fn($u) => $u->where('name','like','%'.$request->search.'%')->orWhere('email','like','%'.$request->search.'%'));
        }
        $logs = $q->latest('created_at')->paginate(30)->withQueryString();
        return view('admin.audit.index', compact('logs'));
    }
}
