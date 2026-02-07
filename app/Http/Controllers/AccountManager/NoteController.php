<?php

namespace App\Http\Controllers\AccountManager;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Note;
use App\Models\User;
use App\Services\StudentActivityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class NoteController extends Controller
{
    public StudentActivityService $activityService;

    public function __construct(StudentActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index($subject, $id)
    {
        $this->authorize('view notes');

        $model = null;
        switch ($subject) {
            case 'company':
                $model = Company::find($id);

                break;
            case 'student':
                $model = User::find($id);

                break;
            default:
                Log::error('Invalid subject type', ['subject' => $subject, 'id' => $id]);

                break;
        }

        if (empty($model)) {
            Log::error('Model not found', ['subject' => $subject, 'id' => $id]);
            abort(404);
        }

        $response = [];
        $authors = [];
        if (auth()->user()->can('pin notes')) {
            // Fetch pinned notes (max 3) and unpinned notes separately
            $pinnedNotes = Note::where('subject_type', $model::class)
                ->where('subject_id', $model->id)
                ->where('is_pinned', true)
                ->orderBy('updated_at', 'DESC')
                ->take(3)
                ->get();
            $unpinnedNotes = Note::where('subject_type', $model::class)
                ->where('subject_id', $model->id)
                ->where('is_pinned', false)
                ->orderBy('id', 'DESC')
                ->get();

            // Combine notes: pinned first, then unpinned
            $resource = $pinnedNotes->merge($unpinnedNotes);
        } else {
            // Fetch all notes if user doesn't have permission to pin
            $resource = Note::where('subject_type', $model::class)
                ->where('subject_id', $model->id)
                ->orderBy('id', 'DESC')
                ->get();
        }

        //        $notes = Note::where( 'subject_type', $model::class )->where( 'subject_id', $model->id );
        // //        if (auth()->user()->hasRole(['Leader', 'Trainer'])) {
        // //            $notes = $notes->where('user_id', auth()->user()->id);
        // //        }
        //        $resource = $notes->orderBy( 'id', 'DESC' )->get();

        if ($resource->isNotEmpty()) {
            $output = "<ul class='list-group'>";
            foreach ($resource as $item) {
                if (!isset($authors[$item['user_id']])) {
                    $authors[$item['user_id']] = User::find($item['user_id']);
                }
                $author_name = !empty($authors[$item['user_id']]) ? $authors[$item['user_id']]['name'] : ($item['user_id'] == 0 ? 'System' : '');

                $pinStatus = $item['is_pinned'] ? 'UnPin' : 'Pin';
                $pinClass = $item['is_pinned'] ? 'btn-flat-warning' : 'btn-flat-secondary';
                $pinItemClass = $item['is_pinned'] ? 'bg-light-secondary' : '';
                // Get last pinner's name for tooltip
                $lastPinnerName = 'None';
                // Use getAttribute to safely access pin_log
                $pinLog = $item->getAttribute('pin_log') ?? [];
                if (is_array($pinLog) && !empty($pinLog)) {
                    $lastAction = end($pinLog);
                    if (isset($lastAction['user_id'])) {
                        $lastPinner = User::find($lastAction['user_id']);
                        $lastPinnerName = $lastPinner ? $lastPinner->name : 'Unknown';
                    }
                }
                $tooltip = $item['is_pinned'] ? "Last pinned by: {$lastPinnerName}" : 'Click to pin';

                $output .= "<li class='list-group-item {$pinItemClass}' data-user='{$item['user_id']}' data-item='{$item['id']}'>
                                <div class='row'>
                                    <span class='fw-bolder me-25 col-lg-3 text-start text-active-dark'>
                                        {$author_name}
                                        <br />
                                        <span class='text-start text-muted'>{$item['created_at']}</span>";
                if (!auth()->user()->isLeader()) {
                    $output .= "<br/>
                                        <a class='item-delete me-1 btn btn-flat-danger btn-sm d-print-none' title='Remove' onclick='Tabs.deleteNote(event, {$item['id']})'>
                                            Remove
                                        </a>";
                    $output .= "<a class='item-edit text-primary btn btn-flat-primary btn-sm d-print-none' title='Edit' onclick='Tabs.editNote(event, {$item['id']})'>
                                            Edit
                                        </a>";
                }

                // Pin/Unpin button, visible only to users with 'pin notes' permission
                if (auth()->user()->can('pin notes')) {
                    $output .= "<a class='item-pin me-1 btn {$pinClass} btn-sm d-print-none' title='{$tooltip}' data-bs-toggle='tooltip' onclick='Tabs.togglePinNote(event, {$item['id']}, \"{$subject}\")'>";
                    $output .= $pinStatus;
                    $output .= '</a>';
                }
                $output .= '</span>';
                $output .= "<span class='col-lg-7 note-content'>";
                $output .= $item['note_body'];
                $output .= '</span>';
                if (auth()->user()->can('pin notes')) {
                    $output .= "<span class='col-lg-1 text-end text-warning fw-bolder'>";
                    $output .= $item['is_pinned'] ? '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg> Pinned' : '';
                    $output .= '</span>';
                }
                $output .= '</div>';
                $output .= '</li>';
            }
            $output .= '</ul>';

            $response['html'] = $output;
        }

        return Helper::successResponse($response, 'Notes loaded successfully');
    }

    public function store(Request $request)
    {
        $this->authorize('create notes');
        $validated = $request->validate(
            [
            'id' => 'nullable|numeric',
            'note_body' => 'required',
            'subject_type' => ['required', 'string', Rule::in(['company', 'student'])],
            'subject_id' => 'required|integer|exists:users,id',
        ],
            [
            'note_body.required' => 'A valid note/message is required',
        ]
        );

        if (!empty($request->id) && !is_null($request->id)) {
            $note = Note::where('id', $request->id)->first();
            if (empty($note)) {
                return Helper::errorResponse('Note not found.', 404);
            }
            $note->note_body = $validated['note_body'];
            $note->save();

            return Helper::successResponse($note, 'Note updated successfully');
        }

        $subject = null;
        switch ($validated['subject_type']) {
            case 'company':
                $subject = Company::find($validated['subject_id']);

                break;
            case 'student':
                $subject = User::find($validated['subject_id']);

                break;
            default:
                break;
        }

        if (empty($subject)) {
            return Helper::errorResponse('Something wrong happened.', 403);
        }

        $note = Note::create(
            [
            'user_id' => auth()->user()->id,
            'subject_type' => $subject::class,
            'subject_id' => $subject->id,
            'note_body' => $validated['note_body'],
            'is_pinned' => false, // Default to false, pinning handled via pin endpoint
        ]
        );
        if ($validated['subject_type'] === 'student') {
            $this->activityService->setActivity(
                [
                'user_id' => $validated['subject_id'],
                'activity_event' => 'NOTE ADDED',
                'activity_details' => [
                    'student' => $validated['subject_id'],
                    'by' => [
                        'id' => auth()->user()->id,
                        'role' => auth()->user()->roleName(),
                    ],
                    'is_pinned' => $note->is_pinned,
                ],
            ],
                $note
            );
        }

        return Helper::successResponse($note, 'Note added successfully');
    }

    public function pin(Request $request, Note $note)
    {
        $this->authorize('pin notes');

        $validated = $request->validate([
            'is_pinned' => 'required|boolean',
        ]);

        if ($validated['is_pinned']) {
            $pinnedCount = Note::where('subject_type', $note->subject_type)
                ->where('subject_id', $note->subject_id)
                ->where('is_pinned', true)
                ->count();
            if ($pinnedCount >= 3) {
                return Helper::errorResponse('Cannot pin more than 3 notes.', 403);
            }
        }

        // Update pin_log
        $pinLog = $note->pin_log ?? [];
        $pinLog[] = [
            'user_id' => auth()->user()->id,
            'action' => $validated['is_pinned'] ? 'pin' : 'unpin',
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
        $note->pin_log = $pinLog;

        $note->is_pinned = $validated['is_pinned'];
        $note->save();

        // Calculate current pinned notes count
        $pinnedCount = Note::where('subject_type', $note->subject_type)
            ->where('subject_id', $note->subject_id)
            ->where('is_pinned', true)
            ->count();

        // Invalidate cache for the student if subject_type is student
        if ($note->subject_type === User::class) {
            Cache::forget("student_{$note->subject_id}_pinned_notes_count");
        }

        $message = $note->is_pinned ? 'Note pinned successfully' : 'Note unpinned successfully';

        return Helper::successResponse([
            'note' => $note,
            'subject_id' => $note->subject_id,
            'pinned_count' => $pinnedCount,
        ], $message);
    }

    /**
     * Show the form for editing the specified resource.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Note $note)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Note $note)
    {
        $this->authorize('update notes');
    }

    public function destroy(Note $note)
    {
        $this->authorize('delete notes');

        $oldNote = $note;

        $note->delete();

        // Invalidate cache for the student if subject_type is student
        if ($oldNote->subject_type === User::class) {
            Cache::forget("student_{$oldNote->subject_id}_pinned_notes_count");
        }

        activity()
            ->performedOn($oldNote)
            ->causedBy(auth()->user())
            ->withProperties(
                [
                'activity_event' => 'NOTE DELETED',
                'activity_details' => [
                    'deleted_note' => $oldNote->toArray(),
                    'of' => $oldNote->user_id,
                    'by' => auth()->user()->id,
                    'ip' => request()->ip(),
                ],
            ]
            )
            ->log('Note Deleted');

        return response()->json(
            [
            'data' => $oldNote,
            'success' => true, 'status' => 'success',
            'message' => 'Note deleted successfully',
        ],
            201
        );
    }

    /**
     * Store multiple notes for different students/companies.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkStore(Request $request)
    {
        $this->authorize('create notes');

        $validated = $request->validate([
            'notes' => 'required|array|min:1',
            'notes.*.note_body' => 'required|string',
            'notes.*.subject_type' => ['required', 'string', Rule::in(['company', 'student'])],
            'notes.*.subject_id' => 'required|integer',
        ], [
            'notes.required' => 'At least one note is required',
            'notes.min' => 'At least one note is required',
            'notes.*.note_body.required' => 'Note body is required for all notes',
            'notes.*.subject_type.required' => 'Subject type is required for all notes',
            'notes.*.subject_id.required' => 'Subject ID is required for all notes',
        ]);

        $createdNotes = [];
        $errors = [];

        foreach ($validated['notes'] as $index => $noteData) {
            try {
                // Validate subject exists
                $subject = null;
                switch ($noteData['subject_type']) {
                    case 'company':
                        $subject = Company::find($noteData['subject_id']);

                        break;
                    case 'student':
                        $subject = User::find($noteData['subject_id']);

                        break;
                }

                if (empty($subject)) {
                    $errors[] = "Note {$index}: Invalid subject (ID: {$noteData['subject_id']}, Type: {$noteData['subject_type']})";

                    continue;
                }

                // Create the note
                $note = Note::create([
                    'user_id' => auth()->user()->id,
                    'subject_type' => $subject::class,
                    'subject_id' => $subject->id,
                    'note_body' => $noteData['note_body'],
                    'is_pinned' => false,
                ]);

                $createdNotes[] = $note;

                // Log activity for student notes
                if ($noteData['subject_type'] === 'student') {
                    $this->activityService->setActivity([
                        'user_id' => $noteData['subject_id'],
                        'activity_event' => 'NOTE ADDED',
                        'activity_details' => [
                            'student' => $noteData['subject_id'],
                            'by' => [
                                'id' => auth()->user()->id,
                                'role' => auth()->user()->roleName(),
                            ],
                            'is_pinned' => false,
                        ],
                    ], $note);
                }
            } catch (\Exception $e) {
                $errors[] = "Note {$index}: ".$e->getMessage();
            }
        }

        if (empty($createdNotes)) {
            return Helper::errorResponse('Failed to create any notes. '.implode('; ', $errors), 400);
        }

        $message = count($createdNotes) === 1
            ? 'Note added successfully'
            : count($createdNotes).' notes added successfully';

        if (!empty($errors)) {
            $message .= ' (with some errors: '.implode('; ', $errors).')';
        }

        return Helper::successResponse([
            'notes' => $createdNotes,
            'total_created' => count($createdNotes),
            'errors' => $errors,
        ], $message);
    }
}
