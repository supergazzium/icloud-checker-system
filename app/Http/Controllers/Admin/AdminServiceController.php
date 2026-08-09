<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class AdminServiceController extends Controller {
    public function index() {
        return view('admin.services.index', ['services' => Service::orderBy('sort_order')->get()]);
    }
    public function store(Request $request) {
        $request->validate(['name_th'=>'required','name_en'=>'required','provider_service_id'=>'required|integer','device_type'=>'required','cost_price'=>'required|numeric','sell_price'=>'required|numeric']);
        Service::create($request->only(['name_th','name_en','description_th','description_en','provider_service_id','device_type','cost_price','sell_price','processing_time','supports_serial','active','sort_order']));
        return back()->with('success','เพิ่มบริการสำเร็จ');
    }
    public function update(Request $request, Service $service) {
        $service->update($request->only(['name_th','name_en','description_th','description_en','cost_price','sell_price','active','sort_order']));
        return back()->with('success','อัปเดตบริการสำเร็จ');
    }
    public function toggleActive(Service $service) {
        $service->update(['active' => !$service->active]);
        return back()->with('success', $service->active ? 'เปิดบริการแล้ว' : 'ปิดบริการแล้ว');
    }
}
