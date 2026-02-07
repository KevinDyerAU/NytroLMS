<?php

namespace App\Http\Controllers\LMS;

use App\DataTables\LMS\LessonDataTable;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Models\Lesson;
use App\Models\Topic;
use App\Services\CourseProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LessonController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(LessonDataTable $dataTable)
    {
        $this->authorize('manage lms');

        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'LMS'],
            ['link' => route('lms.lessons.index'), 'name' => 'Lessons'],
        ];

        $actionItems = [];
        if (auth()->user()->can('create companies')) {
            $actionItems = [
                0 => ['link' => route('lms.lessons.create'), 'icon' => 'plus-square', 'title' => 'Add New Lesson'],
            ];
        }

        return $dataTable->render('content.lms.posts.index', [
            'pageConfigs' => $pageConfigs,
            'breadcrumbs' => $breadcrumbs,
            'actionItems' => $actionItems,
            'post' => ['title' => 'Lesson', 'parent' => 'course', 'type' => 'lesson', 'content' => null],
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
            ['link' => route('lms.lessons.index'), 'name' => 'Lessons'],
            ['name' => 'Add New Lesson'],
        ];

        return view()->make('content.lms.post.add-edit')
            ->with([
                'action' => ['url' => route('lms.lessons.store'), 'name' => 'Create'],
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
                'post' => ['title' => 'Lesson', 'parent' => 'course', 'type' => 'lesson', 'content' => null],
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
        $this->authorize('create lms');
        $validated = $request->validate([
            'title' => 'required',
            '_content' => 'sometimes',
            'course_id' => 'required|numeric',
            'featured_image' => 'sometimes|file|mimes:jpg,png,jpeg',
            'release_key' => 'required',
            'release_value' => 'sometimes',
            'has_work_placement' => 'nullable|boolean',
        ]);

        $total_lessons = Lesson::where('course_id', $validated['course_id'])->count();

        $lesson = new Lesson();
        $lesson->order = $total_lessons ?? 0;
        $lesson->title = $validated['title'];
        $lesson->slug = \Str::slug($validated['title']);
        $lesson->course_id = $validated['course_id'];
        $lesson->release_key = $validated['release_key'];
        $lesson->release_value = $validated['release_value'] ?? null;
        $lesson->has_work_placement = $validated['has_work_placement'] ?? 0;
        $lesson->has_topic = false;
        $lesson->lb_content = $validated['_content'];
        $lesson->save();

        if (!empty($validated['featured_image'])) {
            $this->featuredImage($validated['featured_image'], $lesson);
        }
        $lesson = Lesson::where('id', $lesson->id)->first();
        $payload = [
            'key' => 'lesson',
            'id' => $lesson->id,
            'parent_id' => $lesson->course_id,
            'data' => $lesson->toArray(),
        ];

        (new CourseProgressService())->addToProgress($validated['course_id'], $payload);

        return redirect()->route('lms.lessons.index')
            ->with('success', 'Lesson created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Lesson $lesson)
    {
        $this->authorize('view lms');
        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'LMS'],
            ['link' => route('lms.lessons.index'), 'name' => 'Lesson'],
            ['name' => 'View Lesson'],
        ];

        $actionItems = [
            0 => ['link' => route('lms.lessons.edit', $lesson), 'icon' => 'edit', 'title' => 'Edit Lesson'],
            1 => ['link' => route('lms.lessons.destroy', $lesson), 'icon' => 'x-circle', 'title' => 'Delete Lesson'],
            2 => ['link' => route('lms.lessons.create'), 'icon' => 'plus-square', 'title' => 'Add New Lesson'],
        ];

        $topics = $lesson->topics()->orderBy('order');

        return view()->make('content.lms.post.show')
            ->with([
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
                'actionItems' => $actionItems,
                'post' => ['title' => 'Lesson', 'parent' => 'course', 'type' => 'lesson', 'content' => $lesson],
                'related' => [
                    'type' => 'topics',
                    'lvl1' => $topics->get(),
                ],
            ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Lesson $lesson)
    {
        $this->authorize('update lms');
        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'LMS'],
            ['link' => route('lms.lessons.index'), 'name' => 'Lesson'],
            ['name' => 'Edit Lesson'],
        ];
        $actionItems = [
            0 => ['link' => route('lms.lessons.show', $lesson), 'icon' => 'file-text', 'title' => 'View Lesson'],
            1 => ['link' => route('lms.lessons.create'), 'icon' => 'plus-square', 'title' => 'Add New Lesson'],
        ];
        $topics = $lesson->topics()->orderBy('order');

        return view()->make('content.lms.post.add-edit')
            ->with([
                'action' => ['url' => route('lms.lessons.update', $lesson), 'name' => 'Edit'],
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
                'actionItems' => $actionItems,
                'post' => ['title' => 'Lesson', 'parent' => 'course', 'type' => 'lesson', 'content' => $lesson],
                'related' => [
                    'type' => 'topics',
                    'lvl1' => $topics->get(),
                ],
            ]);
    }

    /**
     * Update the specified resource in storage.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Lesson $lesson)
    {
        $this->authorize('update lms');
        $validated = $request->validate([
            'title' => 'required',
            '_content' => 'sometimes',
            'course_id' => 'required|numeric',
            'featured_image' => 'sometimes|file|mimes:jpg,png,jpeg',
            'release_key' => 'required',
            'release_value' => 'sometimes',
            'has_work_placement' => 'nullable|boolean',
        ]);
        if ($lesson->title !== $validated['title']) {
            $lesson->title = $validated['title'];
            $lesson->slug = \Str::slug($validated['title']);
        }
        $lesson->release_key = $validated['release_key'];
        $lesson->release_value = $validated['release_value'] ?? null;
        $lesson->has_work_placement = $validated['has_work_placement'] ?? 0;
        $lesson->course_id = $validated['course_id'];
        $lesson->lb_content = $validated['_content'];
        $lesson->save();

        if (!empty($validated['featured_image'])) {
            $this->featuredImage($validated['featured_image'], $lesson);
        }

        return redirect()->route('lms.lessons.show', $lesson->id)
            ->with('success', 'Lesson created successfully.');
    }

    public function reorder(Request $request, Lesson $lesson, $type)
    {
        if (!$request->order) {
            return response()->json([
                'code' => (404 + 300),
                'status' => 'error',
                'success' => false,
                'message' => 'Whoops, looks like something went wrong',
            ], 404);
        }
        foreach ($request->order as $pos => $topic_id) {
            Topic::where('id', $topic_id)->where('lesson_id', $lesson->id)->update(['order' => $pos]);
        }

        return response()->json([
            'data' => [],
            'success' => true, 'status' => 'success',
            'message' => 'Topics re-ordered successfully',
        ], 201);
    }

    private function featuredImage($featured_image, Lesson $model): void
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

    /**
     * Remove the specified resource from storage.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Lesson $lesson)
    {
        $this->authorize('delete lms');

        if ($lesson->hasTopics() < 1) {
            $exLesson = $lesson;

            $lesson->delete();

            activity()
                ->performedOn($exLesson)
                ->causedBy(auth()->user())
                ->withProperties([
                    'activity_event' => 'LESSON DELETED',
                    'activity_details' => [
                        'lesson' => $exLesson->toArray(),
                        'by' => auth()->user()->id,
                        'ip' => request()->ip(),
                    ],
                ])
                ->log('Lesson Deleted');

            return response()->json([
                'data' => $exLesson->toArray(),
                'success' => true, 'status' => 'success',
                'message' => 'Lesson deleted successfully',
            ], 201);
        }

        return Helper::errorResponse('Delete associated topics first.', 403);
    }
}
