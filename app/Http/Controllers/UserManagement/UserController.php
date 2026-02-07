<?php

namespace App\Http\Controllers\UserManagement;

use App\DataTables\UsersDataTable;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDetail;
use App\Notifications\NewAccountNotification;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     *
     * @return \Illuminate\Http\Response
     *
     * @throws AuthorizationException
     */
    public function index(UsersDataTable $dataTable)
    {
        $this->authorize('viewAny', User::class);

        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'Roles & Users'],
            ['link' => route('user_management.users.index'), 'name' => 'Users'],
        ];

        $actionItems = [];
        if (auth()->user()->can('create companies')) {
            $actionItems = [
                0 => ['link' => route('user_management.users.create'), 'icon' => 'plus-square', 'title' => 'Add New User'],
            ];
        }

        return $dataTable->render('content.user-management.users.index', [
            'pageConfigs' => $pageConfigs,
            'breadcrumbs' => $breadcrumbs,
            'actionItems' => $actionItems,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', User::class);
        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'Roles & Users'],
            ['link' => route('user_management.users.index'), 'name' => 'Users'],
            ['name' => 'Add New User'],
        ];

        $superiorRoles = $this->notAllowedRoles();

        $excludedRoles = array_merge($superiorRoles, ['Leader', 'Trainer', 'Student']);

        return view()->make('content.user-management.users.add-edit')
            ->with([
                'action' => ['url' => route('user_management.users.store'), 'name' => 'Create'],
                'allowedRoles' => Role::notRole($excludedRoles)->get(),
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
            ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $this->authorize('create', User::class);
        $validated = $request->validate([
            'first_name' => ['required', 'max:255', 'regex:/^[A-Za-z0-9\s]+/'],
            'last_name' => ['required', 'max:255', 'regex:/^[A-Za-z0-9\s]+/'],
            'email' => 'required|unique:users',
            'phone' => ['nullable', 'regex:/^[\+0-9]+/'],
            'address' => ['nullable', 'regex:/[- ,\/0-9a-zA-Z\.]+/'],
            'country' => 'required|numeric|exists:countries,id',
            'timezone' => 'required|exists:timezones,name',
            'language' => 'required|alpha|max:2',
            'role' => 'required|string|exists:roles,name',
            'password' => 'required|confirmed|min:6',
        ], [
            'role.required' => 'A valid role name is required.',
            'role.exists' => 'A valid role is required.',
            'role.string' => 'A valid role is required.',
            'phone.regex' => 'A valid phone is required i.e. +61 123 456 7890',
            'address.regex' => 'A valid address is required',
        ]);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'username' => $request->first_name . $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'email_verified_at' => ($request->role === 'Student') ? null : Carbon::now(),
        ]);

        $user->detail()->save(
            new UserDetail([
            'phone' => $request->phone,
            'address' => $request->address,
            'country_id' => $request->country,
            'language' => $request->language,
            'timezone' => $request->timezone,
        ])
        );

        if (in_array($request->role, ['Student', 'Trainer', 'Leader'])) {
            $user->userable_type = 'App\\Models\\' . $request->role;
            $user->userable_id = $user->id;
            $user->save();
        }

        $user->assignRole($request->role);

        $user->notify(new NewAccountNotification($request->role, $request->password));

        return redirect()->route('user_management.users.index')
            ->with('success', 'User created successfully. Email with details sent at: ' . $request->email);
    }

    /**
     * Display the specified resource.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        $this->authorize('view', $user);
        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'Roles & Users'],
            ['link' => route('user_management.users.index'), 'name' => 'Users'],
            ['name' => 'View User'],
        ];
        $actionItems = [
            ['link' => route('user_management.users.edit', $user), 'icon' => 'edit', 'title' => 'Edit User'],
            ['link' => route('user_management.users.create'), 'icon' => 'plus-square', 'title' => 'Add New User'],
        ];
        if (!$this->canModify($user->id)) {
            unset($actionItems[0]);
        }

        return view()->make('content.user-management.users.show')
            ->with([
                'user' => $user,
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
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
        $this->authorize('update', $user);
        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'Roles & Users'],
            ['link' => route('user_management.users.index'), 'name' => 'Users'],
            ['name' => 'Edit User'],
        ];
        $actionItems = [
            ['link' => route('user_management.users.show', $user), 'icon' => 'file-text', 'title' => 'View View'],
            ['link' => route('user_management.users.create'), 'icon' => 'plus-square', 'title' => 'Add New User'],
        ];
        $superiorRoles = $this->notAllowedRoles();

        $excludedRoles = array_merge($superiorRoles, ['Leader', 'Trainer', 'Student']);

        return view()->make('content.user-management.users.add-edit')
            ->with([
                'user' => $user,
                'action' => ['url' => route('user_management.users.update', $user), 'name' => 'Edit'],
                'allowedRoles' => Role::notRole($excludedRoles)->get(),
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
                'actionItems' => $actionItems,
            ]);
    }

    /**
     * Update the specified resource in storage.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);
        $validated = $request->validate([
            'v' => ['required', function ($attribute, $value, $fail) use ($user) {
                if ($value !== md5($user->id)) {
                    abort(403);
                }
            }],
            'first_name' => ['required', 'max:255', 'regex:/^[A-Za-z0-9\s]+/'],
            'last_name' => ['required', 'max:255', 'regex:/^[A-Za-z0-9\s]+/'],
            'email' => 'required|unique:users,email,' . $user->id . ',id',
            'phone' => ['nullable', 'regex:/^[\+0-9]+/'],
            'address' => ['nullable', 'regex:/[- ,\/0-9a-zA-Z]+/'],
            'country' => 'required|numeric|exists:countries,id',
            'timezone' => 'required|exists:timezones,name',
            'language' => 'required|alpha|max:2',
            'role' => 'required|string|exists:roles,name',
            'password' => 'nullable|confirmed|min:6',
        ], [
            'role.required' => 'A valid role name is required.',
            'role.exists' => 'A valid role is required.',
            'role.string' => 'A valid role is required.',
            'phone.regex' => 'A valid phone is required i.e. +61 123 456 7890',
            'address.regex' => 'A valid address is required',
        ]);

        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;

        if (!empty($request->password)) {
            $user->password = Hash::make($request->password);
        }
        $user->save();
        $user->detail()->update([
            'phone' => $request->phone,
            'address' => $request->address,
            'country_id' => $request->country,
            'language' => $request->language,
            'timezone' => $request->timezone,
        ]);
        $user->syncRoles($request->role);

        return redirect()->route('user_management.users.show', $user)
            ->with('success', 'User updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        //
    }

    public function canModify($user_id)
    {
        return auth()->user()->isRoot() || (auth()->user()->id != $user_id && auth()->user()->isAdmin());
    }

    private function notAllowedRoles()
    {
        $user = auth()->user();
        if ($user->isRoot()) {
            return ['Root'];
        } elseif ($user->isAdmin()) {
            return ['Root', 'Admin'];
        } elseif ($user->isModerator()) {
            return ['Root', 'Moderator', 'Admin'];
        }

        return ['Root', 'Admin'];
    }
}
