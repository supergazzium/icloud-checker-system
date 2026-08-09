<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class AdminSettingController extends Controller {
    public function index() {
        return view('admin.settings.index', ['apiKey' => config('ifreeicloud.ifreeicloud.key')]);
    }
    public function update(Request $request) {
        $request->validate(['ifreeicloud_key' => 'required|string']);
        $path    = base_path('.env');
        $content = file_get_contents($path);
        if (preg_match('/^IFREEICLOUD_KEY=/m', $content))
            $content = preg_replace('/^IFREEICLOUD_KEY=.*/m', 'IFREEICLOUD_KEY='.$request->ifreeicloud_key, $content);
        else
            $content .= "\nIFREEICLOUD_KEY=".$request->ifreeicloud_key;
        file_put_contents($path, $content);
        Artisan::call('config:clear');
        return back()->with('success','บันทึกการตั้งค่าสำเร็จ');
    }
}
