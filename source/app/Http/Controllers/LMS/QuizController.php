<?php

namespace App\Http\Controllers\LMS;

use App\DataTables\LMS\QuizDataTable;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use App\Notifications\AssessmentReturned;
use App\Services\CourseProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class QuizController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(QuizDataTable $dataTable)
    {
        $this->authorize('manage lms');

        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'LMS'],
            ['link' => route('lms.quizzes.index'), 'name' => 'Quizzes'],
        ];

        $actionItems = [];
        if (auth()->user()->can('create companies')) {
            $actionItems = [
                0 => ['link' => route('lms.quizzes.create'), 'icon' => 'plus-square', 'title' => 'Add New Quiz'],
            ];
        }

        return $dataTable->render('content.lms.posts.index', [
            'pageConfigs' => $pageConfigs,
            'breadcrumbs' => $breadcrumbs,
            'actionItems' => $actionItems,
            'post' => ['title' => 'Quiz', 'parent' => 'topic', 'type' => 'quiz', 'content' => null],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create lms');
        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'LMS'],
            ['link' => route('lms.quizzes.index'), 'name' => 'Quizzes'],
            ['name' => 'Add New Quiz'],
        ];

        return view()->make('content.lms.post.add-edit')
            ->with([
                'questions' => false,
                'gotoTab' => true,
                'action' => ['url' => route('lms.quizzes.store'), 'name' => 'Create'],
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
                'post' => ['title' => 'Quiz', 'parent' => 'lesson', 'type' => 'quiz', 'content' => null],
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
        $this->authorize('create lms');
        $validated = $request->validate([
            'title' => 'required',
            '_content' => 'required',
            'course_id' => 'required|numeric',
            'lesson_id' => 'required|numeric',
            'topic_id' => 'required|numeric',
            'estimated_time' => 'required|numeric',
            'passing_percentage' => 'required|numeric',
            'allowed_attempts' => 'required|numeric|gt:0',
            'featured_image' => 'sometimes|file|mimes:jpg,png,jpeg',
            'has_checklist' => 'nullable|boolean',
        ]);

        $total_quizzes = Quiz::where('topic_id', $validated['topic_id'])->count();

        $quiz = new Quiz();
        $quiz->order = $total_quizzes ?? 0;
        $quiz->title = $validated['title'];
        $quiz->slug = \Str::slug($validated['title']);
        $quiz->course_id = $validated['course_id'];
        $quiz->lesson_id = $validated['lesson_id'];
        $quiz->topic_id = $validated['topic_id'];
        $quiz->has_checklist = $validated['has_checklist'] ?? 0;
        $quiz->estimated_time = $validated['estimated_time'];
        $quiz->passing_percentage = $validated['passing_percentage'];
        $quiz->allowed_attempts = $validated['allowed_attempts'] ?? 999;
        $quiz->lb_content = $validated['_content'];
        $quiz->save();

        if (!empty($validated['featured_image'])) {
            $this->featuredImage($validated['featured_image'], $quiz);
        }
        $topic = $quiz->topic;
        $topic->has_quiz = true;
        $topic->save();

        $quiz = Quiz::where('id', $quiz->id)->first();
        $payload = [
            'key' => 'quiz',
            'id' => $quiz->id,
            'parent_id' => $quiz->topic_id,
            'data' => $quiz->toArray(),
        ];

        (new CourseProgressService())->addToProgress($quiz->lesson->course->id, $payload);

        return redirect()->route('lms.questions.edit', $quiz)
            ->with('success', 'Quiz created successfully. Start adding questions now');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Quiz $quiz)
    {
        $this->authorize('view lms');
        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'LMS'],
            ['link' => route('lms.quizzes.index'), 'name' => 'Quizzes'],
            ['name' => 'View Quiz'],
        ];

        $actionItems = [
            0 => ['link' => route('lms.quizzes.edit', $quiz), 'icon' => 'edit', 'title' => 'Edit Quiz'],
            1 => ['link' => route('lms.quizzes.destroy', $quiz), 'icon' => 'x-circle', 'title' => 'Delete Quiz'],
            2 => ['link' => route('lms.quizzes.create'), 'icon' => 'plus-square', 'title' => 'Add New Quiz'],
        ];

        $questions = $quiz->questions()->orderBy('order');

        return view()->make('content.lms.post.show')
            ->with([
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
                'actionItems' => $actionItems,
                'post' => [
                    'title' => 'Quiz',
                    'parent' => 'topic',
                    'type' => 'quiz',
                    'content' => $quiz,
                ],
                'related' => [
                    'type' => 'questions',
                    'lvl1' => $questions->get(),
                ],
            ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Quiz $quiz)
    {
        $this->authorize('update lms');
        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'LMS'],
            ['link' => route('lms.quizzes.index'), 'name' => 'Quiz'],
            ['name' => 'Edit Quiz'],
        ];
        $actionItems = [
            0 => ['link' => route('lms.quizzes.show', $quiz), 'icon' => 'file-text', 'title' => 'View Quiz'],
            1 => ['link' => route('lms.quizzes.create'), 'icon' => 'plus-square', 'title' => 'Add New Quiz'],
        ];
        $questions = $quiz->questions()->orderBy('order');

        return view()->make('content.lms.post.add-edit')
            ->with([
                'questions' => true,
                'gotoTab' => true,
                'action' => ['url' => route('lms.quizzes.update', $quiz), 'name' => 'Edit'],
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
                'actionItems' => $actionItems,
                'post' => ['title' => 'Quiz', 'parent' => 'lesson', 'type' => 'quiz', 'content' => $quiz],
                'related' => [
                    'type' => 'questions',
                    'lvl1' => $questions->get(),
                ],
            ]);
    }

    /**
     * Update the specified resource in storage.
     *
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Quiz $quiz)
    {
        $this->authorize('create lms');
        $validated = $request->validate([
            'title' => 'required',
            '_content' => 'required',
            'course_id' => 'required|numeric',
            'lesson_id' => 'required|numeric',
            'topic_id' => 'required|numeric',
            'estimated_time' => 'required|numeric',
            'passing_percentage' => 'required|numeric',
            'allowed_attempts' => 'required|numeric|gt:0',
            'featured_image' => 'sometimes|file|mimes:jpg,png,jpeg',
            'has_checklist' => 'nullable|boolean',
        ]);
        //        Helper::debug([$validated, $validated[ 'has_checklist' ] ?? 0], 'dd');
        $total_quizzes = Quiz::where('topic_id', $validated['topic_id'])->count();

        if ($quiz->title !== $validated['title']) {
            $quiz->title = $validated['title'];
            $quiz->slug = \Str::slug($validated['title']);
        }
        $quiz->course_id = $validated['course_id'];
        $quiz->lesson_id = $validated['lesson_id'];
        $quiz->topic_id = $validated['topic_id'];
        $quiz->estimated_time = $validated['estimated_time'];
        $quiz->has_checklist = $validated['has_checklist'] ?? 0;
        $quiz->passing_percentage = $validated['passing_percentage'];
        $quiz->allowed_attempts = $validated['allowed_attempts'] ?? 999;
        $quiz->lb_content = $validated['_content'];
        $quiz->save();

        if (!empty($validated['featured_image'])) {
            $this->featuredImage($validated['featured_image'], $quiz);
        }

        return redirect()->route('lms.quizzes.edit', $quiz)
            ->with('success', 'Quiz updated successfully. Start adding questions now');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Quiz $quiz)
    {
        $this->authorize('delete lms');
        $exQuiz = $quiz;

        $attempts = $quiz->attempts();

        foreach ($attempts as $attempt) {
            $query = User::find($attempt->user_id)
                ->notifications()
                ->where('type', AssessmentReturned::class)
                ->whereRaw("notifications.data LIKE '%assessment__{$attempt->id}%'");
            if ($query->count() > 0) {
                $query->delete();
            }
            $attempt->delete();
        }

        $quiz->delete();

        //

        activity()
            ->performedOn($exQuiz)
            ->causedBy(auth()->user())
            ->withProperties([
                'activity_event' => 'QUIZ DELETED',
                'activity_details' => [
                    'quiz' => $exQuiz->toArray(),
                    'by' => auth()->user()->id,
                    'ip' => request()->ip(),
                ],
            ])
            ->log('Quiz Deleted');

        return response()->json([
            'data' => $exQuiz,
            'success' => true, 'status' => 'success',
            'message' => 'Quiz deleted successfully',
        ], 201);
    }

    public function reorder(Request $request, Quiz $quiz, $type)
    {
        if (!$request->order) {
            return response()->json([
                'code' => (404 + 300),
                'status' => 'error',
                'success' => false,
                'message' => 'Whoops, looks like something went wrong',
            ], 404);
        }
        foreach ($request->order as $pos => $question_id) {
            Question::where('id', $question_id)->where('quiz_id', $quiz->id)->update(['order' => $pos]);
        }

        return response()->json([
            'data' => [],
            'success' => true, 'status' => 'success',
            'message' => 'Quiz re-ordered successfully',
        ], 201);
    }

    private function featuredImage($featured_image, Quiz $model): void
    {
        $getFeaturedImage = $model->images()->featured()->first();
        if (!empty($getFeaturedImage)) {
            $this->deleteImage($getFeaturedImage);
        }
        $filepath = 'public/featured/'.$model->id;
        Helper::ensureDirectoryWithPermissions($filepath);
        $uploadedImage = new Image(['type' => 'FEATURED', 'file_path' => $featured_image->store($filepath)]);
        $model->images()->save($uploadedImage);
    }

    private function deleteImage($image): void
    {
        Storage::delete($image->file_path);
        $image->delete();
    }
}
