<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TwoFactorController extends Controller
{
    /** Setup page: show existing status, or a fresh QR to scan. */
    public function show(Request $request)
    {
        $user = $request->user();
        $google2fa = new Google2FA();

        if (!$user->two_factor_enabled) {
            if (!session('2fa_setup_secret')) {
                session(['2fa_setup_secret' => $google2fa->generateSecretKey()]);
            }
            $secret = session('2fa_setup_secret');
            $qrUrl = $google2fa->getQRCodeUrl(config('app.name'), $user->email, $secret);
            $renderer = new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd());
            $qrSvg = (new Writer($renderer))->writeString($qrUrl);
            return view('admin.two-factor.setup', ['secret' => $secret, 'qrSvg' => $qrSvg]);
        }

        return view('admin.two-factor.status');
    }

    /** Verify the code typed against the pending secret, then persist + enable. */
    public function enable(Request $request)
    {
        $request->validate(['code' => 'required|digits:6']);
        $secret = session('2fa_setup_secret');
        abort_unless($secret, 400, 'No pending 2FA setup.');

        $google2fa = new Google2FA();
        if (!$google2fa->verifyKey($secret, $request->code)) {
            return back()->withErrors(['code' => 'รหัสไม่ถูกต้อง กรุณาลองใหม่']);
        }

        $codes = collect(range(1, 8))->map(fn () => strtoupper(bin2hex(random_bytes(4))))->all();

        $request->user()->update([
            'two_factor_secret' => encrypt($secret),
            'two_factor_enabled' => true,
            'two_factor_recovery_codes' => encrypt(json_encode($codes)),
        ]);
        session()->forget('2fa_setup_secret');
        session(['2fa_passed' => true]);

        return redirect()->route('admin.2fa.show')->with('recoveryCodes', $codes)->with('success', 'เปิดใช้งาน 2FA สำเร็จ — บันทึกรหัสสำรองไว้ให้ปลอดภัย');
    }

    public function disable(Request $request)
    {
        $request->validate(['password' => 'required|current_password']);
        $request->user()->update(['two_factor_secret' => null, 'two_factor_enabled' => false, 'two_factor_recovery_codes' => null]);
        return redirect()->route('admin.2fa.show')->with('success', 'ปิดใช้งาน 2FA แล้ว');
    }

    /** Challenge page shown right after password login for admins with 2FA enabled. */
    public function challenge()
    {
        if (!auth()->user()->two_factor_enabled || session('2fa_passed')) {
            return redirect()->route('dashboard');
        }
        return view('admin.two-factor.challenge');
    }

    public function verify(Request $request)
    {
        $request->validate(['code' => 'required|string']);
        $user = $request->user();
        $google2fa = new Google2FA();

        if (strlen($request->code) === 6 && ctype_digit($request->code)
            && $google2fa->verifyKey(decrypt($user->two_factor_secret), $request->code)) {
            session(['2fa_passed' => true]);
            return redirect()->intended(route('dashboard'));
        }

        $codes = $user->two_factor_recovery_codes ? json_decode(decrypt($user->two_factor_recovery_codes), true) : [];
        if (in_array(strtoupper($request->code), $codes)) {
            $codes = array_values(array_diff($codes, [strtoupper($request->code)]));
            $user->update(['two_factor_recovery_codes' => encrypt(json_encode($codes))]);
            session(['2fa_passed' => true]);
            return redirect()->intended(route('dashboard'))->with('success', 'เข้าสู่ระบบด้วยรหัสสำรอง — เหลือ '.count($codes).' รหัส');
        }

        return back()->withErrors(['code' => 'รหัสไม่ถูกต้อง']);
    }
}
