<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class ProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    public function show(User $user)
    {
        $pageConfigs = ['layoutWidth' => 'full'];
        $actionItems = [
            ['link' => route('profile.edit', $user), 'icon' => 'edit', 'title' => 'Edit'],
            ['link' => route('profile.deactivate', $user), 'icon' => 'x-square', 'title' => 'Deactivate'],
        ];
        $breadcrumbs = [['link' => route('profile.show', $user), 'name' => 'Profile']];

        return view('/content/profile/show', [
            'user' => auth()->user(),
            'title' => $user->name,
            'pageConfigs' => $pageConfigs,
            //            'breadcrumbs' => $breadcrumbs,
            'actionItems' => $actionItems,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     * deactivate the specified resource from storage.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function deactivate(User $user)
    {
        //
    }

    public function password(User $user)
    {
        $pageConfigs = ['showMenu' => false, 'layoutWidth' => 'full', 'footerType' => 'sticky'];
        //        if ( auth()->user()->hasRole( 'Student' ) ) {
        //            $pageConfigs = [
        //                'showMenu' => FALSE,
        //                'layoutWidth' => 'full',
        //                'mainLayoutType' => 'horizontal',
        //            ];
        //        }
        $breadcrumbs = [];
        if (!empty($user->password_change_at)
            || !empty($user->detail->last_logged_in)) {
            $pageConfigs = ['layoutWidth' => 'full'];
            $breadcrumbs = [['link' => route('profile.show', auth()->user()), 'name' => 'Profile'], ['name' => 'Reset Password']];
        }

        return view('content.profile.password', [
            'user' => auth()->user(),
            'title' => 'Account Security',
            'pageConfigs' => $pageConfigs,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    public function passwordReset(User $user, Request $request)
    {
        if (!\Auth::check()) {
            return back()->withErrors(['email' => ['Invalid User']]);
        }
        $validated = $request->validate([
            'old_password' => 'required',
            'password' => 'required|confirmed|min:6',
        ]);
        // Match The Old Password
        if (!Hash::check($validated['old_password'], auth()->user()->password)) {
            return back()->with('error', "Old Password Doesn't match!");
        }
        if (Hash::check($validated['password'], auth()->user()->password)) {
            return back()->with('error', 'Your new password must be different from previously used passwords.');
        }

        //        $request->request->add( [ 'email' => auth()->user()->email ] );
        //
        //        $user = $this->getUser( $request->toArray() );

        //        if ( empty($user)) {
        //            return back()->withErrors( [ 'email' => [ "Invalid User" ] ] );
        //        }

        $authUser = Auth::user();

        $authUser->forceFill([
            'password' => Hash::make($validated['password']),
            'password_change_at' => Carbon::now(),
        ])->save();

        //        event( new PasswordReset( $user ) );
        \Auth::logout();
        $request->session()->invalidate();

        return redirect()->route('login')->with('status', 'Password Reset Successfully');
    }

    /**
     * @return \Illuminate\Contracts\Auth\Authenticatable|CanResetPasswordContract|User|string
     */
    protected function validateReset(array $credentials)
    {
        if (is_null($user = $this->getUser($credentials))) {
            return 'Invalid User';
        }

        return $user;
    }

    public function getUser(array $credentials)
    {
        return User::where('email', $credentials['email'])->first();
    }
}
