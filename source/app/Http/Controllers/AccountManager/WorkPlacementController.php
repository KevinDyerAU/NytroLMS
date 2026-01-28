<?php

namespace App\Http\Controllers\AccountManager;

use App\DataTables\AccountManager\WorkPlacementDataTable;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WorkPlacement;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class WorkPlacementController extends Controller
{
    /**
     * Retrieve all work placements for a given student, rendering as HTML.
     */
    public function index(User $student)
    {
        $this->authorize('view work placements');
        $workPlacements = WorkPlacement::with(['course', 'company', 'leader'])
            ->where('user_id', $student->id)
            ->whereHas('course', function ($query) {
                $query->where('is_main_course', true);
            })
            ->get();

        $html = view(
            'content.account-manager.students.work-placements-table',
            compact('student', 'workPlacements')
        )->render();

        return Helper::successResponse([
            'html' => $html,
            'work_placements' => $workPlacements,
        ], 'Work Placements loaded successfully');
    }

    /**
     * Provide data for DataTable AJAX requests.
     */
    public function data(User $student, WorkPlacementDataTable $dataTable)
    {
        $this->authorize('view work placements');

        return $dataTable->with([
            'student' => $student->id,
        ])->render('content.account-manager.students.work-placements-table');

        //        $workPlacements = WorkPlacement::with( [ 'course', 'user' ] )
        //                                       ->where( 'user_id', $student->id )
        //                                       ->whereHas( 'course', function ( $query ) {
        //                                           $query->where( 'is_main_course', TRUE );
        //                                       } );

        //        return DataTables::of( $workPlacements )
        //                         ->addColumn( 'user.name', function ( $wp ) {
        //                             return $wp->user ? $wp->user->name : '-';
        //                         } )
        //                         ->addColumn( 'course.title', function ( $wp ) {
        //                             return $wp->course ? $wp->course->title : '-';
        //                         } )
        //                         ->addColumn( 'consultation_completed', function ( $wp ) {
        //                             return is_null( $wp->consultation_completed ) ? '-' : ( $wp->consultation_completed ? 'Yes' : 'No' );
        //                         } )
        //                         ->make( TRUE );
    }

    /**
     * Retrieve a single work placement.
     */
    public function show(WorkPlacement $workPlacement)
    {
        $this->authorize('view work placements');

        return response()->json([
            'status' => 'success',
            'data' => $workPlacement->load(['course', 'company', 'leader']),
        ]);
    }

    /**
     * Store a new work placement.
     */
    public function store(Request $request)
    {
        $this->authorize('create work placements');

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id|gt:0',
            'course_id' => 'required|exists:courses,id',
            'course_start_date' => 'nullable|date',
            'course_end_date' => 'nullable|date|after_or_equal:course_start_date',
            'consultation_completed' => 'nullable|boolean',
            'consultation_completed_on' => 'nullable|date',
            'wp_commencement_date' => 'nullable|date',
            'wp_end_date' => 'nullable|date|after_or_equal:wp_commencement_date',
            'employer_name' => 'nullable|string|max:255',
            'employer_email' => 'nullable|email|max:255',
            'employer_phone' => 'nullable|string|max:20',
            'employer_address' => 'nullable|string|max:500',
            'employer_notes' => 'nullable|string',
        ]);
        $student = User::find($validated['user_id']);
        if (!$student) {
            return Helper::errorResponse('Student not found.', 404);
        }
        $creatorId = auth()->user()->id;
        if ($creatorId < 1) {
            return Helper::errorResponse('User not found.', 404);
        }
        $validated['company_id'] = optional($student->companies()->first())->id;
        $validated['leader_id'] = optional($student->leaders()->first())->id;
        $validated['created_by'] = $creatorId;

        $validated = array_filter($validated);
        if (WorkPlacement::where($validated)->exists()) {
            return Helper::errorResponse('Work placement already exists.', 409);
        }

        $workPlacement = WorkPlacement::create($validated);

        return Helper::SuccessResponse($workPlacement, 'Work placement created successfully', 201);
    }

    /**
     * Update an existing work placement.
     */
    public function update(Request $request, WorkPlacement $workPlacement)
    {
        $this->authorize('create work placements');

        $validated = $request->validate([
            'consultation_completed' => 'nullable|boolean',
            'consultation_completed_on' => 'nullable|date',
            'wp_commencement_date' => 'nullable|date',
            'wp_end_date' => 'nullable|date|after_or_equal:wp_commencement_date',
            'employer_name' => 'nullable|string|max:255',
            'employer_email' => 'nullable|email|max:255',
            'employer_phone' => 'nullable|string|max:20',
            'employer_address' => 'nullable|string|max:500',
            'employer_notes' => 'nullable|string',
        ]);

        $validated = array_filter($validated);
        $fieldChanges = $workPlacement->field_changes ?? [];
        $now = now()->toDateTimeString();

        foreach ($validated as $key => $value) {
            if ($key !== 'user_id' && $value !== $workPlacement->$key) {
                $fieldChanges[] = [
                    'type' => 'update',
                    'field' => $key,
                    'value' => $value,
                    'changed_at' => $now,
                    'changed_by' => auth()->user()->id,
                ];
            }
        }

        $workPlacement->update(array_merge($validated, [
            'field_changes' => $fieldChanges,
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Work placement updated successfully.',
            'data' => $workPlacement,
        ]);
    }

    /**
     * Delete a work placement.
     */
    public function destroy(WorkPlacement $workPlacement)
    {
        $this->authorize('delete work placements');

        $workPlacement->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Work placement deleted successfully.',
        ]);
    }
}
