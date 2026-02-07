<?php

namespace App\Http\Controllers\AccountManager;

use App\DataTables\AccountManager\LeaderDataTable;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\StudentCourseEnrolment;
use App\Models\User;
use App\Models\UserDetail;
use App\Notifications\NewLeaderNotification;
use App\Services\AdminReportService;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;

class LeaderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(LeaderDataTable $dataTable)
    {
        $this->authorize('view leaders');
        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'Account Manager'],
            ['link' => route('account_manager.leaders.index'), 'name' => 'Leaders'],
        ];

        $actionItems = [];
        if (auth()->user()->can('create leaders')) {
            $actionItems = [
                0 => ['link' => route('account_manager.leaders.create'), 'icon' => 'plus-square', 'title' => 'Add New Leader'],
            ];
        }

        return $dataTable->render('content.account-manager.leaders.index', [
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
        $this->authorize('create leaders');
        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'Account Manager'],
            ['link' => route('account_manager.leaders.index'), 'name' => 'Leaders'],
            ['name' => 'Add New Leader'],
        ];

        return view()->make('content.account-manager.leaders.add-edit')
            ->with([
                'action' => ['url' => route('account_manager.leaders.store'), 'name' => 'Create'],
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
        $this->authorize('create leaders');
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
            'company' => 'required|exists:companies,id',
            'position' => ['nullable', 'max:255'],
            'role' => ['required', 'in:' . implode(',', config('constants.leader_roles'))],
        ], [
            'phone.regex' => 'A valid phone is required',
            'address.regex' => 'A valid address is required',
        ]);

        try {
            DB::beginTransaction();

            $leader = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'username' => $request->first_name . $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
            $leader->detail()->save(
                new UserDetail([
                'phone' => $request->phone,
                'position' => $request->position,
                'role' => $request->role,
                'address' => $request->address,
                'country_id' => $request->country,
                'language' => $request->language,
                'timezone' => $request->timezone,
            ])
            );

            $leader->companies()->sync($request->company);
            $leader->assignRole('Leader');

            $leader->userable_type = 'App\\Models\\Leader';
            $leader->userable_id = $leader->id;
            $leader->save();

            DB::commit();

            // Send notifications and fire events outside transaction to avoid blocking
            // Wrap in try-catch so they don't cause 500 errors
            try {
                $leader->notify(new NewLeaderNotification('Leader', $request->password));
            } catch (\Exception $e) {
                Log::error('Failed to send leader notification', [
                    'leader_id' => $leader->id,
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                event(new Registered($leader));
            } catch (\Exception $e) {
                Log::error('Failed to fire Registered event', [
                    'leader_id' => $leader->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return redirect()->route('account_manager.leaders.index')
                ->with('success', 'Leader created successfully. Email with details sent at: ' . $request->email);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create leader', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create leader. Please try again.']);
        }
    }

    /**
     * Display the specified resource.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function show(User $leader)
    {
        $this->authorize('view leaders', $leader);

        if (!$leader->isLeader()) {
            abort(404, 'This user is not a valid Leader');
        }
        $leader->load(['companies', 'detail']);

        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'Account Manager'],
            ['link' => route('account_manager.leaders.index'), 'name' => 'Leaders'],
            ['name' => 'View Leader'],
        ];
        $actionItems = [
            0 => ['link' => route('account_manager.leaders.edit', $leader), 'icon' => 'edit', 'title' => 'Edit Leader'],
            1 => ['link' => route('account_manager.leaders.create'), 'icon' => 'plus-square', 'title' => 'Add New Leader'],
        ];
        if (!auth()->user()->can('update leaders')) {
            unset($actionItems[0]);
        }

        if (!empty($leader->detail->last_logged_in)) {
            $first_login = $leader->detail->first_login ?? '';
            $first_logged_in = '';
            if (empty($first_login)
                || $first_login === '0000-00-00 00:00:00'
                || Carbon::parse($first_login)->greaterThanOrEqualTo(Carbon::parse($leader->created_at))) {
                $activity = Activity::where('causer_id', $leader->id)
                    ->where('event', 'AUTH')
                    ->where('log_name', 'audit')
                    ->where('description', 'SIGN IN')->first();
                $first_logged_in = !empty($activity) ? $activity->getRawOriginal('created_at') : '';
            }

            $leaderId = $leader->id;

            $firstEnrolment = StudentCourseEnrolment::whereHas('student', function ($query) use ($leaderId) {
                $query->whereHas('leaders', function ($query) use ($leaderId) {
                    $query->where('attachable_type', 'App\Models\Leader')
                        ->where('attachable_id', $leaderId);
                })
                    ->where('userable_type', null);
            })->orderBy('student_course_enrolments.created_at')->first();

            $userDetails = $leader->detail;
            $userDetails->first_login = $first_logged_in;
            $userDetails->first_enrollment = !empty($firstEnrolment) ? $firstEnrolment->getRawOriginal('created_at') : '';
            $userDetails->save();
        }

        $activityStatus = null;

        if (intval($leader->is_active) === 0) {
            $activityStatus = Activity::where('subject_id', $leader->id)
                ->where('subject_type', User::class)
                ->where('event', 'DEACTIVATED')
                ->where('log_name', 'user_status')
                ->orderBy('id', 'desc')
                ->first();
        }

        return view()->make('content.account-manager.leaders.show')
            ->with([
                'leader' => $leader,
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
                'actionItems' => $actionItems,
                'activity' => [
                    'by' => $activityStatus?->causer->name,
                    'on' => $activityStatus ? Carbon::parse($activityStatus->created_at)->timezone(Helper::getTimeZone())->format('j F, Y') : $leader->updated_at,
                ],
            ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(User $leader)
    {
        $this->authorize('update leaders', $leader);

        $leader->load(['companies', 'detail']);

        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'Account Manager'],
            ['link' => route('account_manager.leaders.index'), 'name' => 'Leaders'],
            ['name' => 'View Leader'],
        ];
        $actionItems = [
            0 => ['link' => route('account_manager.leaders.show', $leader), 'icon' => 'file-text', 'title' => 'View Leader'],
            1 => ['link' => route('account_manager.leaders.create'), 'icon' => 'plus-square', 'title' => 'Add New Leader'],
        ];
        $leaders_companies = Arr::pluck($leader->companies->toArray(), 'id');

        return view()->make('content.account-manager.leaders.add-edit')
            ->with([
                'leader' => $leader,
                'leader_companies' => $leaders_companies,
                'action' => ['url' => route('account_manager.leaders.update', $leader), 'name' => 'Edit'],
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
                'actionItems' => $actionItems,
            ]);
    }

    /**
     * Update the specified resource in storage.
     *
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function update(Request $request, User $leader)
    {
        $this->authorize('update leaders', $leader);
        $validated = $request->validate([
            'first_name' => ['required', 'max:255', 'regex:/^[A-Za-z0-9\s]+/'],
            'last_name' => ['required', 'max:255', 'regex:/^[A-Za-z0-9\s]+/'],
            'email' => 'required|unique:users,email,' . $leader->id . ',id',
            'phone' => ['nullable', 'regex:/^[\+0-9]+/'],
            'address' => ['nullable', 'regex:/[- ,\/0-9a-zA-Z]+/'],
            'country' => 'required|numeric|exists:countries,id',
            'timezone' => 'required|exists:timezones,name',
            'language' => 'required|alpha|max:2',
            'password' => 'nullable|confirmed|min:6',
            'company' => 'required|exists:companies,id',
            'position' => ['nullable', 'max:255'],
            'role' => ['required', 'in:' . implode(',', config('constants.leader_roles'))],
        ], [
            'phone.regex' => 'A valid phone is required',
            'address.regex' => 'A valid address is required',
        ]);

        $leader->first_name = $request->first_name;
        $leader->last_name = $request->last_name;
        $leader->email = $request->email;

        if (!empty($request->password)) {
            $leader->password = Hash::make($request->password);
        }
        $leader->save();
        $leader->detail()->update([
            'phone' => $request->phone,
            'position' => $request->position,
            'role' => $request->role,
            'address' => $request->address,
            'country_id' => $request->country,
            'language' => $request->language,
            'timezone' => $request->timezone,
        ]);
        $leader->companies()->sync($request->company);

        AdminReportService::updateLeader($leader);

        return redirect()->route('account_manager.leaders.show', $leader)
            ->with('success', 'Leader updated successfully');
    }

    public function onboard()
    {
        $pageConfigs = [
            'showMenu' => false,
            'layoutWidth' => 'full',
            'mainLayoutType' => 'horizontal',
        ];
        $breadcrumbs = [];

        return view()->make('content.account-manager.leaders.onboard')
            ->with([
                'leader' => auth()->user()->toArray(),
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
            ]);
    }

    public function onboardAgreement(Request $request)
    {
        $validated = $request->validate([
            'agreement' => 'required|alpha',
        ], [
            'required' => 'You need to agree the Terms and Conditions first.',
        ]);
        $user_detail = auth()->user()->detail;
        $user_detail->status = 'ONBOARDED';
        $user_detail->onboard_at = Carbon::now();
        $user_detail->save();

        return redirect(route('dashboard'))->with('success', 'Welcome to ' . env('APP_NAME', 'Key Institute'));
    }

    public function activate(Request $request, User $leader)
    {
        $this->authorize('update leaders', $leader);
        if ($leader->isLeader()) {
            $leader->is_active = true;
            $leader->save();

            $leader->detail->status = 'ACTIVE';
            $leader->detail->save();

            $causer = auth()->user();
            $causer_role = $causer->roles()->first();
            activity('user_status')
                ->event('ACTIVATED')
                ->causedBy($causer)
                ->performedOn($leader)
                ->withProperties([
                    'role' => 'Leader',
                    'user_id' => $leader->id,
                    'causer' => [
                        'id' => $causer->id,
                        'role' => [
                            'id' => $causer_role->id,
                            'name' => $causer_role->name,
                        ],
                    ],
                ])
                ->log('Leader is activated, status is ACTIVE now');

            return redirect()->route('account_manager.leaders.show', $leader)
                ->with('success', 'Leader status updated successfully');
        }

        return redirect()->route('account_manager.leaders.show', $leader)
            ->with('error', 'Unable to update status');
    }

    public function deactivate(Request $request, User $leader)
    {
        $this->authorize('update leaders', $leader);
        if ($leader->isLeader()) {
            $leader->is_active = false;
            $leader->save();

            $leader->detail->status = 'INACTIVE';
            $leader->detail->save();

            $causer = auth()->user();
            $causer_role = $causer->roles()->first();
            activity('user_status')
                ->event('DEACTIVATED')
                ->causedBy($causer)
                ->performedOn($leader)
                ->withProperties([
                    'role' => 'Leader',
                    'user_id' => $leader->id,
                    'causer' => [
                        'id' => $causer->id,
                        'role' => [
                            'id' => $causer_role->id,
                            'name' => $causer_role->name,
                        ],
                    ],
                ])
                ->log('Leader is deactivated, status is INACTIVE now');

            return redirect()->route('account_manager.leaders.show', $leader)
                ->with('success', 'Leader status updated successfully');
        }

        return redirect()->route('account_manager.leaders.show', $leader)
            ->with('error', 'Unable to update status');
    }
}
