<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\UsernameRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UsernameController extends Controller
{
    public function showUsernameRequestForm()
    {
        $pageConfigs = [
            'bodyClass' => 'bg-full-screen-image',
            'blankPage' => true,
        ];

        return view('/auth/username', [
            'pageConfigs' => $pageConfigs,
        ]);
    }

    public function sendUsernameRequestForm(Request $request)
    {
        $validated = $request->validate([
            'email' => Rule::exists('users')->where(function ($query) use ($request) {
                return $query->where('email', $request->email);
            }),
        ], [
            'email.exists' => 'The email does not exists.',
        ]);
        $user = User::where('email', '=', $validated['email'])->first();
        // sena username
        $user->notify(new UsernameRequest());

        return redirect()->route('login')->with(['status' => 'Check your email for username.']);
    }
}
