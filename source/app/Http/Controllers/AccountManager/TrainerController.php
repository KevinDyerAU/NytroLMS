<?php

namespace App\Http\Controllers\AccountManager;

use App\DataTables\AccountManager\TrainerDataTable;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDetail;
use App\Notifications\NewAccountNotification;
use App\Services\AdminReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Models\Activity;

class TrainerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(TrainerDataTable $dataTable)
    {
        $this->authorize('view trainers');
        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'Account Manager'],
            ['link' => route('account_manager.trainers.index'), 'name' => 'Trainers'],
        ];

        $actionItems = [];
        if (auth()->user()->can('create trainers')) {
            $actionItems = [
                0 => ['link' => route('account_manager.trainers.create'), 'icon' => 'plus-square', 'title' => 'Add New Trainer'],
            ];
        }

        return $dataTable->render('content.account-manager.trainers.index', [
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
        $this->authorize('create trainers');
        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'Account Manager'],
            ['link' => route('account_manager.trainers.index'), 'name' => 'Trainers'],
            ['name' => 'Add New Trainer'],
        ];

        return view()->make('content.account-manager.trainers.add-edit')
            ->with([
                'action' => ['url' => route('account_manager.trainers.store'), 'name' => 'Create'],
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
            ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create trainers');
        $validated = $request->validate([
            'first_name' => ['required', 'max:255', 'regex:/^[A-Za-z0-9\s]+/'],
            'last_name' => ['required', 'max:255', 'regex:/^[A-Za-z0-9\s]+/'],
            'email' => 'required|unique:users',
            'phone' => ['nullable', 'regex:/^[\+0-9]+/'],
            'address' => ['nullable', 'regex:/[- ,\/0-9a-zA-Z\.]+/'],
            'country' => 'required|numeric|exists:countries,id',
            'timezone' => 'required|exists:timezones,name',
            'language' => 'required|alpha|max:2',
            'password' => 'required|confirmed|min:6',
        ], [
            'phone.regex' => 'A valid phone is required',
            'address.regex' => 'A valid address is required',
        ]);

        $trainer = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'username' => $request->first_name.$request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'email_verified_at' => Carbon::now(),
        ]);
        $trainer->detail()->save(
            new UserDetail([
            'phone' => $request->phone,
            'address' => $request->address,
            'country_id' => $request->country,
            'language' => $request->language,
            'timezone' => $request->timezone,
        ])
        );
        $trainer->assignRole('Trainer');

        $trainer->userable_type = 'App\\Models\\Trainer';
        $trainer->userable_id = $trainer->id;
        $trainer->save();

        $trainer->notify(new NewAccountNotification('Trainer', $request->password));

        return redirect()->route('account_manager.trainers.index')
            ->with('success', 'Trainer created successfully. Email with details sent at: '.$request->email);
    }

    /**
     * Display the specified resource.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function show(User $trainer)
    {
        $this->authorize('view trainers', $trainer);

        if (!$trainer->isTrainer()) {
            abort(404, 'This user is not a valid Trainer');
        }

        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'Account Manager'],
            ['link' => route('account_manager.trainers.index'), 'name' => 'Trainers'],
            ['name' => 'View Trainer'],
        ];
        $actionItems = [
            0 => ['link' => route('account_manager.trainers.edit', $trainer), 'icon' => 'edit', 'title' => 'Edit Trainer'],
            1 => ['link' => route('account_manager.trainers.create'), 'icon' => 'plus-square', 'title' => 'Add New Trainer'],
        ];
        if (!auth()->user()->can('update trainers')) {
            unset($actionItems[0]);
        }

        $activityStatus = null;

        if (intval($trainer->is_active) === 0) {
            $activityStatus = Activity::where('subject_id', $trainer->id)
                ->where('subject_type', User::class)
                ->where('event', 'DEACTIVATED')
                ->where('log_name', 'user_status')
                ->orderBy('id', 'desc')
                ->first();
        }

        return view()->make('content.account-manager.trainers.show')
            ->with([
                'trainer' => $trainer,
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
                'actionItems' => $actionItems,
                'activity' => [
                    'by' => $activityStatus?->causer->name,
                    'on' => $activityStatus ? Carbon::parse($activityStatus->created_at)->timezone(Helper::getTimeZone())->format('j F, Y') : $trainer->updated_at,
                ],
            ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(User $trainer)
    {
        $this->authorize('update trainers', $trainer);
        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'Account Manager'],
            ['link' => route('account_manager.trainers.index'), 'name' => 'Trainers'],
            ['name' => 'View Trainer'],
        ];
        $actionItems = [
            0 => ['link' => route('account_manager.trainers.show', $trainer), 'icon' => 'file-text', 'title' => 'View Trainer'],
            1 => ['link' => route('account_manager.trainers.create'), 'icon' => 'plus-square', 'title' => 'Add New Trainer'],
        ];

        return view()->make('content.account-manager.trainers.add-edit')
            ->with([
                'trainer' => $trainer,
                'action' => ['url' => route('account_manager.trainers.update', $trainer), 'name' => 'Edit'],
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
    public function update(Request $request, User $trainer)
    {
        $this->authorize('update trainers', $trainer);
        $validated = $request->validate([
            'first_name' => ['required', 'max:255', 'regex:/^[A-Za-z0-9\s]+/'],
            'last_name' => ['required', 'max:255', 'regex:/^[A-Za-z0-9\s]+/'],
            'email' => 'required|unique:users,email,'.$trainer->id.',id',
            'phone' => ['nullable', 'regex:/^[\+0-9]+/'],
            'address' => ['nullable', 'regex:/[- ,\/0-9a-zA-Z]+/'],
            'country' => 'required|numeric|exists:countries,id',
            'timezone' => 'required|exists:timezones,name',
            'language' => 'required|alpha|max:2',
            'password' => 'nullable|confirmed|min:6',
        ], [
            'phone.regex' => 'A valid phone is required',
            'address.regex' => 'A valid address is required',
        ]);

        $trainer->first_name = $request->first_name;
        $trainer->last_name = $request->last_name;
        $trainer->email = $request->email;

        if (!empty($request->password)) {
            $trainer->password = Hash::make($request->password);
        }
        $trainer->save();
        $trainer->detail()->update([
            'phone' => $request->phone,
            'address' => $request->address,
            'country_id' => $request->country,
            'language' => $request->language,
            'timezone' => $request->timezone,
        ]);

        AdminReportService::updateTrainer($trainer);

        return redirect()->route('account_manager.trainers.show', $trainer)
            ->with('success', 'Trainer updated successfully');
    }

    public function activate(Request $request, User $trainer)
    {
        $this->authorize('update trainers', $trainer);
        if ($trainer->isTrainer()) {
            $trainer->is_active = true;
            $trainer->save();

            $trainer->detail->status = 'ACTIVE';
            $trainer->detail->save();

            $causer = auth()->user();
            $causer_role = $causer->roles()->first();
            activity('user_status')
                ->event('ACTIVATED')
                ->causedBy($causer)
                ->performedOn($trainer)
                ->withProperties([
                    'role' => 'Trainer',
                    'user_id' => $trainer->id,
                    'causer' => [
                        'id' => $causer->id,
                        'role' => [
                            'id' => $causer_role->id,
                            'name' => $causer_role->name,
                        ],
                    ],
                ])
                ->log('Trainer is activated, status is ACTIVE now');

            return redirect()->route('account_manager.trainers.show', $trainer)
                ->with('success', 'Trainer status updated successfully');
        }

        return redirect()->route('account_manager.trainers.show', $trainer)
            ->with('error', 'Unable to update status');
    }

    public function deactivate(Request $request, User $trainer)
    {
        $this->authorize('update trainers', $trainer);
        if ($trainer->isTrainer()) {
            $trainer->is_active = false;
            $trainer->save();

            $trainer->detail->status = 'INACTIVE';
            $trainer->detail->save();

            $causer = auth()->user();
            $causer_role = $causer->roles()->first();
            activity('user_status')
                ->event('DEACTIVATED')
                ->causedBy($causer)
                ->performedOn($trainer)
                ->withProperties([
                    'role' => 'Trainer',
                    'user_id' => $trainer->id,
                    'causer' => [
                        'id' => $causer->id,
                        'role' => [
                            'id' => $causer_role->id,
                            'name' => $causer_role->name,
                        ],
                    ],
                ])
                ->log('Trainer is deactivated, status is INACTIVE now');

            return redirect()->route('account_manager.trainers.show', $trainer)
                ->with('success', 'Trainer status updated successfully');
        }

        return redirect()->route('account_manager.trainers.show', $trainer)
            ->with('error', 'Unable to update status');
    }
}
