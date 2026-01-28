<?php

namespace App\Http\Controllers\UserManagement;

use App\DataTables\RolesDataTable;
use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    private $role;

    private $theModel;

    public function __construct()
    {
        $this->theModel = 'roles';
        $this->middleware(function ($request, $next) {
            $this->role = Auth::user()->roles()->first();

            return $next($request);
        });
        //        $this->middleware('role.rights:' . $this->role . ',edit');
    }

    /**
     * Display a listing of the resource.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function index(RolesDataTable $dataTable)
    {
        $this->authorize('viewAny', Role::class);
        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'Roles & Users'],
            ['link' => route('user_management.roles.index'), 'name' => 'Roles'],
        ];

        $actionItems = [];
        if (auth()->user()->can('create companies')) {
            $actionItems = [
                0 => ['link' => route('user_management.roles.create'), 'icon' => 'plus-square', 'title' => 'Add New Role'],
            ];
        }

        return $dataTable->render('content.user-management.roles.index', [
            'role' => $this->role,
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
        $this->authorize('create', Role::class);
        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'Roles & Users'],
            ['link' => route('user_management.roles.index'), 'name' => 'Roles'],
            ['name' => 'Add New Roles'],
        ];

        return view()->make('content.user-management.roles.add-edit')
            ->with([
                'user' => auth()->user(),
                'action' => ['url' => route('user_management.roles.store'), 'name' => 'Create'],
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
        $this->authorize('create', Role::class);

        $validated = $request->validate([
            'name' => 'required|unique:roles|max:255',
            'permissions' => 'required|exists:permissions,id',
        ], [
            'name.required' => 'A valid role name is required.',
            'name.unique' => 'Role name already exists.',
            'name.max' => 'Role name should not be more than 254 characters.',
            'permissions.required' => 'A valid permission is required.',
            'permissions.exists' => 'A valid permission is required.',
        ]);

        $role = new Role();
        $role->name = $request->name;
        $role->guard_name = 'web';
        $role->save();

        $role->permissions()->sync($request->permissions);

        return redirect()->route('user_management.roles.index');
    }

    /**
     * Display the specified resource.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Role $role)
    {
        $this->authorize('view', $role);
        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'Roles & Users'],
            ['link' => route('user_management.roles.index'), 'name' => 'Roles'],
            ['name' => 'View Role'],
        ];
        $actionItems = [
            ['link' => route('user_management.roles.edit', $role), 'icon' => 'edit', 'title' => 'Edit Role'],
            ['link' => route('user_management.roles.create'), 'icon' => 'plus-square', 'title' => 'Add New Role'],
        ];

        // Add Clone Role option only for Root users
        if (auth()->user()->hasRole('Root')) {
            $actionItems[] = [
                'link' => route('user_management.roles.clone', $role),
                'icon' => 'copy',
                'title' => 'Clone Role',
            ];
        }
        if (!$this->canModify($role->id)) {
            unset($actionItems[0]);
        }

        return view()->make('content.user-management.roles.show')
            ->with([
                'user' => auth()->user(),
                'role' => $role,
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
    public function edit(Role $role)
    {
        $this->authorize('update', $role);

        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'Roles & Users'],
            ['link' => route('user_management.roles.index'), 'name' => 'Roles'],
            ['name' => 'Edit Role'],
        ];
        $actionItems = [
            ['link' => route('user_management.roles.show', $role), 'icon' => 'file-text', 'title' => 'View Role'],
            ['link' => route('user_management.roles.create'), 'icon' => 'plus-square', 'title' => 'Add New Role'],
        ];

        return view()->make('content.user-management.roles.add-edit')
            ->with([
                'user' => auth()->user(),
                'role' => $role,
                'action' => ['url' => route('user_management.roles.update', $role), 'name' => 'Edit'],
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
     *
     * @throws \Exception
     */
    public function update(Request $request, Role $role)
    {
        $this->authorize('update', $role);

        $validated = $request->validate([
            'v' => ['required', function ($attribute, $value, $fail) use ($role) {
                if ($value !== md5($role->id)) {
                    abort(403);
                }
            }],
            'permissions' => 'required|exists:permissions,id',
        ], [
            'permissions.required' => 'A valid permission is required.',
            'permissions.exists' => 'A valid permission is required.',
        ]);
        $role->permissions()->sync($request->permissions);

        return redirect()->route('user_management.roles.show', $role->id);
    }

    /**
     * Clone the specified role.
     *
     * @param Role $role
     * @return \Illuminate\Http\Response
     */
    public function clone(Role $role)
    {
        // Only Root users can clone roles
        if (!auth()->user()->hasRole('Root')) {
            abort(403, 'Only Root users can clone roles.');
        }

        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'Roles & Users'],
            ['link' => route('user_management.roles.index'), 'name' => 'Roles'],
            ['name' => 'Clone Role: ' . $role->name],
        ];

        return view()->make('content.user-management.roles.add-edit')
            ->with([
                'user' => auth()->user(),
                'role' => $role,
                'action' => ['url' => route('user_management.roles.store'), 'name' => 'Clone Role: ' . $role->name],
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
                'isClone' => true,
            ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Role $role)
    {
        $this->authorize('delete', $role);
    }

    public function canModify($role_id)
    {
        return $this->role->id != $role_id && $this->role->id < $role_id;
    }
}
