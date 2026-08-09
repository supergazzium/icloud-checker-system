<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirect(string $provider)
    {
        abort_unless(in_array($provider, ['google','facebook']), 404);
        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider)
    {
        abort_unless(in_array($provider, ['google','facebook']), 404);

        $social = Socialite::driver($provider)->stateless()->user();

        $user = User::where('provider', $provider)->where('provider_id', $social->getId())->first();

        if (!$user) {
            $user = User::where('email', $social->getEmail())->first();
            if ($user) {
                $user->update(['provider' => $provider, 'provider_id' => $social->getId()]);
            } else {
                $user = User::create([
                    'name'              => $social->getName() ?: $social->getNickname() ?: 'User',
                    'email'             => $social->getEmail(),
                    'password'          => bcrypt(Str::random(32)),
                    'email_verified_at' => now(),
                    'provider'          => $provider,
                    'provider_id'       => $social->getId(),
                ]);
            }
        }

        if (!$user->is_active) {
            return redirect()->route('login')->with('error', __('auth.account_disabled') ?: 'บัญชีนี้ถูกระงับการใช้งาน');
        }

        Auth::login($user, true);
        return redirect()->route('dashboard');
    }
}
