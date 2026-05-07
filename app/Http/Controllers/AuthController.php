<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserStat;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->stateless()
            ->scopes(['https://www.googleapis.com/auth/calendar.readonly'])
            ->with(['access_type' => 'offline', 'prompt' => 'consent select_account'])
            ->redirect();
    }

    public function handleGoogleCallback()
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        $updateData = [
            'name'                    => $googleUser->getName(),
            'email'                   => $googleUser->getEmail(),
            'avatar'                  => $googleUser->getAvatar(),
            'google_access_token'     => $googleUser->token,
            'google_token_expires_at' => now()->addSeconds($googleUser->expiresIn ?? 3600),
        ];

        if ($googleUser->refreshToken) {
            $updateData['google_refresh_token'] = $googleUser->refreshToken;
        }

        $user = User::updateOrCreate(
            ['google_id' => $googleUser->getId()],
            $updateData
        );

        UserStat::firstOrCreate(['user_id' => $user->id]);

        Auth::login($user, remember: true);

        return redirect()->route('dashboard');
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/');
    }
}
