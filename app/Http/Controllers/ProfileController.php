<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $sessions = collect();
        if (config('session.driver') === 'database') {
            $sessions = DB::table(config('session.table', 'sessions'))
                ->where('user_id', $request->user()->id)
                ->orderByDesc('last_activity')
                ->get()
                ->map(function ($s) use ($request) {
                    $agent = $s->user_agent ?? '';
                    $device = 'Unknown device';
                    if (str_contains($agent, 'iPhone')) $device = 'iPhone';
                    elseif (str_contains($agent, 'iPad')) $device = 'iPad';
                    elseif (str_contains($agent, 'Android')) $device = 'Android';
                    elseif (str_contains($agent, 'Macintosh')) $device = 'Mac';
                    elseif (str_contains($agent, 'Windows')) $device = 'Windows';
                    $browser = 'Browser';
                    foreach (['Edg' => 'Edge', 'Chrome' => 'Chrome', 'Firefox' => 'Firefox', 'Safari' => 'Safari'] as $needle => $name) {
                        if (str_contains($agent, $needle)) { $browser = $name; break; }
                    }
                    return (object) [
                        'id' => $s->id,
                        'ip' => $s->ip_address,
                        'device' => $device.' · '.$browser,
                        'last_activity' => \Carbon\Carbon::createFromTimestamp($s->last_activity),
                        'is_current' => $s->id === $request->session()->getId(),
                    ];
                });
        }

        return view('profile.edit', ['user' => $request->user(), 'sessions' => $sessions]);
    }

    /** Log out one specific session (device) by session id. */
    public function destroySession(Request $request, string $sessionId)
    {
        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->id)
            ->where('id', $sessionId)
            ->delete();
        return back()->with('success', 'ออกจากระบบอุปกรณ์นั้นแล้ว');
    }

    /** Log out every session except the current one. */
    public function destroyOtherSessions(Request $request)
    {
        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();
        return back()->with('success', 'ออกจากระบบอุปกรณ์อื่นทั้งหมดแล้ว');
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($request->user()->id)],
        ]);

        $user = $request->user();
        $user->name = $request->name;
        $user->email = $request->email;

        if ($user->isDirty('email') && $user instanceof MustVerifyEmail) {
            $user->email_verified_at = null;
        }

        $user->save();

        return redirect()->route('profile.edit')->with('success', 'บันทึกข้อมูลโปรไฟล์แล้ว');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
