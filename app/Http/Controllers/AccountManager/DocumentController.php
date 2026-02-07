<?php

namespace App\Http\Controllers\AccountManager;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Resources\DocumentCollection;
use App\Models\Document;
use App\Models\User;
use App\Services\StudentActivityService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public StudentActivityService $activityService;

    public function __construct(StudentActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    public function index(User $student)
    {
        // Anyone who can see the documents tab should be able to view the list
        $this->authorize('view documents');

        $excludedDocuments = config('onboarding.step4.document_type');
        $documents = Document::where('user_id', $student->id);
        if (
            auth()
                ->user()
                ->isLeader()
        ) {
            $documents = $documents
                ->whereNotIn('file_name', $excludedDocuments)
                ->whereNotIn('file_uuid', $excludedDocuments);
        }

        //        dd($documents->dump());
        return new DocumentCollection($documents->get());

        //        return new DocumentCollection( Document::where( 'user_id', $student->id )->get() );
    }

    public function store(User $student, Request $request)
    {
        $this->authorize('upload documents');

        // Validate before save
        $validated = $request->validate([
            'file.*' =>
                'required|file|max:10240|mimes:pdf,doc,docx,zip,jpg,jpeg,png,gif,webp,xls,xlsx,rtf,txt,ppt,pptx',
        ]);

        //        dd( $validated, $student, $request->all() );
        $documents = [];
        foreach ($request->file('file') as $file) {
            $documents[] = $this->uploadFile($student->id, $file);
        }

        $count = count($documents);

        return Helper::successResponse(
            $documents,
            $count .
                ' ' .
                \Str::plural('document', $count) .
                ' added successfully'
        );
    }

    public function show(Document $document)
    {
        // Anyone who can see the documents tab should be able to download
        $this->authorize('view documents');

        // Check if user has access to this specific document
        if (
            auth()
                ->user()
                ->isRoot()
        ) {
            // Root users have full access to all documents
        } elseif (
            auth()
                ->user()
                ->isLeader()
        ) {
            // Leaders can only access documents from students in their company
            $leaderCompany = auth()->user()->company_id;
            $studentCompany = $document->user->company_id;

            if ($leaderCompany !== $studentCompany) {
                abort(
                    403,
                    'You do not have permission to access this document.'
                );
            }
        } elseif (
            auth()
                ->user()
                ->isStudent()
        ) {
            // Students can only access their own documents
            if ($document->user_id !== auth()->id()) {
                abort(
                    403,
                    'You do not have permission to access this document.'
                );
            }
        } elseif (
            auth()
                ->user()
                ->isTrainer()
        ) {
            // Trainers can access documents from students they are assigned to
            // This would need to be implemented based on your trainer-student relationship logic
            if (
                !auth()
                    ->user()
                    ->hasRole('Admin')
            ) {
                abort(
                    403,
                    'You do not have permission to access this document.'
                );
            }
        }
        // If user has 'view documents' permission and doesn't match above roles,
        // they can access the document (same logic as tab visibility)

        if (!Storage::exists($document->file_path)) {
            abort(404, 'File not found');
        }

        return Storage::download($document->file_path, $document->file_name);
    }

    public function destroy(Document $document)
    {
        $this->authorize('delete documents');

        $oldDocument = $document;

        if ($document->delete()) {
            Storage::delete($document->file_path);
        }

        activity()
            ->performedOn($oldDocument)
            ->causedBy(auth()->user())
            ->withProperties([
                'activity_event' => 'DOCUMENT DELETED',
                'activity_details' => [
                    'deleted_document' => $oldDocument->toArray(),
                    'of' => $oldDocument->user_id,
                    'by' => auth()->user()->id,
                    'ip' => request()->ip(),
                ],
            ])
            ->log('Document Deleted');

        return Helper::successResponse(
            $oldDocument,
            'Document deleted successfully',
            201
        );
    }

    private function uploadFile($currentUser, UploadedFile $document)
    {
        $file_location = 'public/user/' . $currentUser . '/documents';
        Helper::ensureDirectoryWithPermissions($file_location);
        $path = $document->store($file_location);
        $newDocument = Document::create([
            'user_id' => $currentUser,
            'file_name' => $document->getClientOriginalName(),
            'file_size' => $document->getSize(),
            'file_path' => $path,
            'file_uuid' => \Str::uuid()->toString(),
        ]);

        $this->activityService->setActivity(
            [
                'user_id' => $currentUser,
                'activity_event' => 'DOCUMENT ADDED',
                'activity_details' => [
                    'student' => $currentUser,
                    'by' => [
                        'id' => auth()->user()->id,
                        'role' => auth()
                            ->user()
                            ->roleName(),
                    ],
                ],
            ],
            $newDocument
        );

        return $newDocument->id;
    }
}
