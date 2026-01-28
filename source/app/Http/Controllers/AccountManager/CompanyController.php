<?php

namespace App\Http\Controllers\AccountManager;

use App\DataTables\AccountManager\Company\LeadersDataTable;
use App\DataTables\AccountManager\Company\StudentsDataTable;
use App\DataTables\AccountManager\CompanyDataTable;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Course;
use App\Models\SignupLink;
use App\Services\AdminReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(CompanyDataTable $dataTable)
    {
        $this->authorize('viewAny', Company::class);

        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'Account Manager'],
            ['link' => route('account_manager.companies.index'), 'name' => 'Companies'],
        ];

        $actionItems = [];
        if (auth()->user()->can('create companies')) {
            $actionItems = [
                0 => ['link' => route('account_manager.companies.create'), 'icon' => 'plus-square', 'title' => 'Add New Company'],
            ];
        }

        return $dataTable->render('content.account-manager.companies.index', [
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
        $this->authorize('create', Company::class);
        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'Account Manager'],
            ['link' => route('account_manager.companies.index'), 'name' => 'Companies'],
            ['name' => 'Add New Company'],
        ];

        return view()->make('content.account-manager.companies.add-edit')
            ->with([
                'action' => ['url' => route('account_manager.companies.store'), 'name' => 'Create'],
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
        $this->authorize('create', Company::class);
        $request->merge([
            'company_name' => urlencode($request->get('company_name')),
        ]);
        $validated = $request->validate([
            'company_name' => ['unique:companies,name', 'required', 'max:255', 'regex:/^[A-Za-z0-9\s]+/'],
            'company_email' => 'required',
            'address' => ['nullable', 'regex:/[- ,\/0-9a-zA-Z\.]+/'],
            'company_number' => ['required', 'regex:/^[\+0-9]+/'],
            'poc_user_id' => ['nullable', 'regex:/^[\+0-9]+/'],
            'bm_user_id' => ['nullable', 'regex:/^[\+0-9]+/'],
        ], [
            'company_name.unique' => 'The company name is already taken',
            'company_number.regex' => 'A valid contact number is required',
            'address.regex' => 'A valid company address is required',
            'poc_user_id.regex' => 'A valid user is required',
            'bm_user_id.regex' => 'A valid user is required',
        ]);

        Company::create([
            'name' => $request->company_name,
            'email' => $request->company_email,
            'address' => $request->address,
            'number' => $request->company_number,
            'poc_user_id' => $request->poc_user_id,
            'bm_user_id' => $request->bm_user_id,
            'created_by' => auth()->user()->id,
        ]);

        return redirect()->route('account_manager.companies.index')
            ->with('success', 'Company created successfully.');
    }

    /**
     * Display the specified resource.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Company $company)
    {
        $this->authorize('view', $company);
        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'Account Manager'],
            ['link' => route('account_manager.companies.index'), 'name' => 'Companies'],
            ['name' => 'View Company'],
        ];

        $actionItems = [
            0 => ['link' => route('account_manager.companies.edit', $company), 'icon' => 'edit', 'title' => 'Edit Company'],
            //            1 => ['link' => route('account_manager.companies.deactivate', $company), 'icon' => 'x-circle', 'title' => 'Deactivate Company'],
            2 => ['link' => route('account_manager.companies.create'), 'icon' => 'plus-square', 'title' => 'Add New Company'],
        ];
        //        if($company->trashed()){
        //            unset($actionItems[0]);
        //            $actionItems[1] = ['link' => route('account_manager.companies.activate', $company), 'icon' => 'refresh-ccw', 'title' => 'Activate Company'];
        //        }
        $courses = Course::notRestricted()->orderBy('category', 'asc')->get();
        $signupLinks = SignupLink::with(['leader', 'course'])->where('company_id', $company->id)->get();

        return view()->make('content.account-manager.companies.show')
            ->with([
                'courses' => $courses,
                'leaders' => $company->leaders,
                'company' => $company,
                'signupLinks' => $signupLinks,
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
    public function edit(Company $company)
    {
        $this->authorize('update', $company);
        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'Account Manager'],
            ['link' => route('account_manager.companies.index'), 'name' => 'Companies'],
            ['name' => 'Edit Company'],
        ];
        $actionItems = [
            0 => ['link' => route('account_manager.companies.show', $company), 'icon' => 'file-text', 'title' => 'View Company'],
            //            1 => ['link' => route('account_manager.companies.deactivate', $company), 'icon' => 'x-circle', 'title' => 'Deactivate Company'],
            2 => ['link' => route('account_manager.companies.create'), 'icon' => 'plus-square', 'title' => 'Add New Company'],
        ];
        //        if($company->trashed()){
        //            $actionItems[1] = ['link' => route('account_manager.companies.activate', $company), 'icon' => 'refresh-ccw', 'title' => 'Activate Company'];
        //        }

        return view()->make('content.account-manager.companies.add-edit')
            ->with([
                'company' => $company,
                'action' => ['url' => route('account_manager.companies.update', $company), 'name' => 'Edit'],
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
                'actionItems' => $actionItems,
            ]);
    }

    /**
     * Update the specified resource in storage.
     *
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Company $company)
    {
        $this->authorize('update', $company);
        //        dump($request->toArray());
        $request->merge([
            'company_name' => urlencode($request->get('company_name')),
        ]);
        //        dd($request->toArray(), $company->toArray(),urlencode($company->name));
        $validated = $request->validate([
            'v' => ['required', function ($attribute, $value, $fail) use ($company) {
                if ($value !== md5($company->id)) {
                    abort(403);
                }
            }],
            'company_name' => ['unique:companies,name,'.urlencode($company->name).',name', 'required', 'max:255', 'regex:/^[A-Za-z0-9\s]+/'],
            'company_email' => 'required',
            'address' => ['nullable', 'regex:/[- ,\/0-9a-zA-Z\.]+/'],
            'company_number' => ['required', 'regex:/^[\+0-9]+/'],
            'poc_user_id' => ['nullable', 'regex:/^[\+0-9]+/'],
            'bm_user_id' => ['nullable', 'regex:/^[\+0-9]+/'],
        ], [
            'company_name.unique' => 'The company name is already taken',
            'company_number.regex' => 'A valid contact number is required',
            'address.regex' => 'A valid company address is required',
            'poc_user_id.regex' => 'A valid user is required',
            'bm_user_id.regex' => 'A valid user is required',
        ]);
        $company->name = $request->company_name;
        $company->address = $request->address;
        $company->number = $request->company_number;
        $company->email = $request->company_email;
        $company->poc_user_id = $request->poc_user_id;
        $company->bm_user_id = $request->bm_user_id;
        $company->created_by = auth()->user()->id;
        $company->modified_by = [...($company->modified_by ?? []), ...[['time' => now()->toDateTimeString(), 'user' => auth()->user()->id]]];
        $company->save();

        AdminReportService::updateCompany($company);

        return redirect()->route('account_manager.companies.show', $company)
            ->with('success', 'Company updated successfully');
    }

    public function deactivate(Company $company)
    {
        $this->authorize('delete', $company);

        return $this->destroy($company);
    }

    public function destroy(Company $company)
    {
        $this->authorize('delete', $company);

        $company->modified_by = [...$company->modified_by, ...[['time' => now()->toDateTimeString(), 'user' => auth()->user()->id]]];
        $company->save();

        $company->delete();

        return redirect()->route('account_manager.companies.index')
            ->with('info', 'Company de-activated successfully');
    }

    public function activate(Company $company)
    {
        $this->authorize('restore', $company);

        $company->modified_by = [...$company->modified_by, ...[['time' => now()->toDateTimeString(), 'user' => auth()->user()->id]]];
        $company->save();

        $company->restore();

        return redirect()->route('account_manager.companies.index')
            ->with('success', 'Company restored successfully');
    }

    public function signupLink(Company $company, Request $request)
    {
        $this->authorize('update', $company);

        $validator = Validator::make($request->all(), [
            'course' => 'required|exists:courses,id',
            'leaders' => ['required', 'exists:users,id'],
            'is_chargeable' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        $validated = $validator->getData();

        $existingSignupLink = SignupLink::where('company_id', $company->id)
//                                        ->where('leader_id', $validated[ 'leaders' ])
            ->where('course_id', $validated['course'])->first();

        if (!empty($existingSignupLink)) {
            return redirect(route('account_manager.companies.show', $company).'#company-signup')
                ->with('error', 'Company Signup link already exists.');
        }

        SignupLink::create([
            'company_id' => $company->id,
            'leader_id' => $validated['leaders'],
            'course_id' => $validated['course'],
            'creator_id' => auth()->user()->id,
            'is_active' => true,
            'key' => \Str::uuid()->toString(),
            'is_chargeable' => isset($validated['is_chargeable']) ? 1 : 0,
        ]);

        return redirect(route('account_manager.companies.show', $company).'#company-signup')
            ->with('success', 'Company Signup link created successfully.');
    }

    public function deleteLink(SignupLink $link, Request $request)
    {
        $this->authorize('update', $link->company);

        $link->delete();

        return redirect(route('account_manager.companies.show', $link->company).'#company-signup')
            ->with('info', 'Signup Link deleted successfully');
    }

    public function getStudents(Company $company, StudentsDataTable $dataTable, Request $request)
    {
        // Render the table HTML for the tab
        return view('content.account-manager.companies.table.students', [
            'company' => $company->id,
            'dataTable' => $dataTable,
        ]);
    }

    public function getStudentsData(Company $company, StudentsDataTable $dataTable, Request $request)
    {
        return $dataTable->with([
            'company' => $company->id,
        ])->render('content.account-manager.companies.table.students');
    }

    public function getLeaders(Company $company, LeadersDataTable $dataTable, Request $request)
    {
        // Render the table HTML for the tab
        return view('content.account-manager.companies.table.leaders', [
            'company' => $company->id,
            'dataTable' => $dataTable,
        ]);
    }

    public function getLeadersData(Company $company, LeadersDataTable $dataTable, Request $request)
    {
        return $dataTable->with([
            'company' => $company->id,
        ])->render('content.account-manager.companies.table.leaders');
    }
}
