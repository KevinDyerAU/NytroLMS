<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class StaterkitController extends Controller
{
    // home
    public function home() {
        //        $this->middleware('onboard');
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->hasRole('Student')) {
                if (!empty($user->detail->onboard_at)) {
                    return redirect(route('frontend.dashboard'));
                } else {
                    return redirect(route('frontend.onboard.create', ['step' => 1, 'resumed' => 1]));
                }
            }

            if ($user->hasRole('Leader')) {
                if (empty($user->detail->onboard_at)) {
                    return redirect(route('account_manager.leaders.onboard'));
                } else {
                    // Redirect to dashboard if logged in
                    return redirect(route('dashboard'));
                }
            }

            // For all other authenticated users (Admin, etc.), redirect to dashboard
            return redirect(route('dashboard'));
        }

        // If user is not logged in, redirect to login page
        return redirect(route('login'));
    }

    // dashboard
    public function dashboard() {
        $pageConfigs = ['layoutWidth' => 'full'];

        $breadcrumbs = [['link' => 'dashboard', 'name' => 'Dashboard']];

        $actionItems = [];
        if (auth()->user()->can('create students')) {
            $actionItems = [
                0 => ['link' => route('account_manager.students.create'), 'icon' => 'plus-square', 'title' => 'Add New Student'],
            ];
        }

        $settings = Setting::whereNull('user_id')->get()?->pluck('value', 'key');
        $settings = $settings->map(function ($item, $key) {
            if (is_string($item) && json_decode($item) !== null) {
                return json_decode($item, true);
            }

            return $item;
        });

        return view('content.dashboard', [
            'title' => 'Welcome',
            'pageConfigs' => $pageConfigs,
            'breadcrumbs' => $breadcrumbs,
            'actionItems' => $actionItems,
            'settings' => $settings,
        ]);
    }

    // Layout collapsed menu
    public function collapsed_menu() {
        $pageConfigs = ['sidebarCollapsed' => true];
        $breadcrumbs = [
            ['link' => 'home', 'name' => 'Home'],
            ['link' => 'javascript:void(0)', 'name' => 'Layouts'],
            ['name' => 'Collapsed menu'],
        ];

        return view('/content/layout-collapsed-menu', [
            'breadcrumbs' => $breadcrumbs,
            'pageConfigs' => $pageConfigs,
        ]);
    }

    // layout boxed
    public function layout_full() {
        $pageConfigs = ['layoutWidth' => 'full'];

        $breadcrumbs = [
            ['link' => 'home', 'name' => 'Home'],
            ['name' => 'Layouts'],
            ['name' => 'Layout Full'],
        ];

        return view('/content/layout-full', [
            'pageConfigs' => $pageConfigs,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    // without menu
    public function without_menu() {
        $pageConfigs = ['showMenu' => false];
        $breadcrumbs = [
            ['link' => 'home', 'name' => 'Home'],
            ['link' => 'javascript:void(0)', 'name' => 'Layouts'],
            ['name' => 'Layout without menu'],
        ];

        return view('/content/layout-without-menu', [
            'breadcrumbs' => $breadcrumbs,
            'pageConfigs' => $pageConfigs,
        ]);
    }

    // Empty Layout
    public function layout_empty() {
        $breadcrumbs = [
            ['link' => 'home', 'name' => 'Home'],
            ['link' => 'javascript:void(0)', 'name' => 'Layouts'],
            ['name' => 'Layout Empty'],
        ];

        return view('/content/layout-empty', ['breadcrumbs' => $breadcrumbs]);
    }

    // Blank Layout
    public function layout_blank() {
        $pageConfigs = ['blankPage' => true];

        return view('/content/layout-blank', ['pageConfigs' => $pageConfigs]);
    }

    // Blank Layout
    public function getContent($page) {
        $pageConfigs = ['showMenu' => false];
        $breadcrumbs = [
            ['link' => '/', 'name' => 'Home'],
            ['name' => 'Privacy Policy'],
        ];

        return view(
            'frontend.content.pages.privacy-policy',
            [
                'breadcrumbs' => $breadcrumbs,
                'pageConfigs' => $pageConfigs,
            ]
        );
    }
}
