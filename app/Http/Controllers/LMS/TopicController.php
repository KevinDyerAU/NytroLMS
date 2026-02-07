<?php

namespace App\Http\Controllers\LMS;

use App\DataTables\LMS\TopicDataTable;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Jobs\UpdateAdminReport;
use App\Jobs\UpdateCourseProgress;
use App\Models\Image;
use App\Models\Quiz;
use App\Models\Topic;
use App\Services\CourseProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TopicController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(TopicDataTable $dataTable)
    {
        $this->authorize('manage lms');

        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'LMS'],
            ['link' => route('lms.topics.index'), 'name' => 'Topics'],
        ];

        $actionItems = [];
        if (auth()->user()->can('create companies')) {
            $actionItems = [
                0 => ['link' => route('lms.topics.create'), 'icon' => 'plus-square', 'title' => 'Add New Topic'],
            ];
        }

        return $dataTable->render('content.lms.posts.index', [
            'pageConfigs' => $pageConfigs,
            'breadcrumbs' => $breadcrumbs,
            'actionItems' => $actionItems,
            'post' => ['title' => 'Topic', 'parent' => 'lesson', 'type' => 'topic', 'content' => null],
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
            ['link' => route('lms.topics.index'), 'name' => 'Topics'],
            ['name' => 'Add New Topic'],
        ];

        return view()->make('content.lms.post.add-edit')
            ->with([
                'action' => ['url' => route('lms.topics.store'), 'name' => 'Create'],
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
                'post' => ['title' => 'Topic', 'parent' => 'lesson', 'type' => 'topic', 'content' => null],
            ]);
    }

    /**
     * Store a newly created resource in storage.
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
            'lesson_id' => 'required|numeric',
            'estimated_time' => 'required|numeric',
            'featured_image' => 'sometimes|file|mimes:jpg,png,jpeg',
        ]);

        $total_topics = Topic::where('lesson_id', $validated['lesson_id'])->count();

        $topic = new Topic();
        $topic->order = $total_topics ?? 0;
        $topic->title = $validated['title'];
        $topic->slug = \Str::slug($validated['title']);
        $topic->course_id = $validated['course_id'];
        $topic->lesson_id = $validated['lesson_id'];
        $topic->estimated_time = $validated['estimated_time'];
        $topic->has_quiz = false;
        $topic->lb_content = $validated['_content'];
        $topic->save();

        $lesson = $topic->lesson;
        $lesson->has_topic = true;
        $lesson->save();

        if (!empty($validated['featured_image'])) {
            $this->featuredImage($validated['featured_image'], $topic);
        }

        $topic = Topic::where('id', $topic->id)->first();
        $payload = [
            'key' => 'topic',
            'id' => $topic->id,
            'parent_id' => $topic->lesson_id,
            'data' => $topic->toArray(),
        ];

        (new CourseProgressService())->addToProgress($topic->course->id, $payload);

        return redirect()->route('lms.topics.index')
            ->with('success', 'Topic created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Topic $topic)
    {
        $this->authorize('view lms');
        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'LMS'],
            ['link' => route('lms.topics.index'), 'name' => 'Topic'],
            ['name' => 'View Topic'],
        ];

        $actionItems = [
            0 => ['link' => route('lms.topics.edit', $topic), 'icon' => 'edit', 'title' => 'Edit Topic'],
            1 => ['link' => route('lms.topics.destroy', $topic), 'icon' => 'x-circle', 'title' => 'Delete Topic'],
            2 => ['link' => route('lms.topics.create'), 'icon' => 'plus-square', 'title' => 'Add New Topic'],
        ];

        $quizzes = $topic->quizzes()->orderBy('order');

        return view()->make('content.lms.post.show')
            ->with([
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
                'actionItems' => $actionItems,
                'post' => [
                    'title' => 'Topic',
                    'parent' => 'lesson',
                    'type' => 'topic',
                    'content' => $topic,
                ],
                'related' => [
                    'type' => 'quizzes',
                    'lvl1' => $quizzes->get(),
                ],
            ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Topic $topic)
    {
        $this->authorize('update lms');
        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'LMS'],
            ['link' => route('lms.topics.index'), 'name' => 'Topic'],
            ['name' => 'Edit Topic'],
        ];
        $actionItems = [
            0 => ['link' => route('lms.topics.show', $topic), 'icon' => 'file-text', 'title' => 'View Topic'],
            1 => ['link' => route('lms.topics.create'), 'icon' => 'plus-square', 'title' => 'Add New Topic'],
        ];
        $quizzes = $topic->quizzes()->orderBy('order');

        return view()->make('content.lms.post.add-edit')
            ->with([
                'action' => ['url' => route('lms.topics.update', $topic), 'name' => 'Edit'],
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
                'actionItems' => $actionItems,
                'post' => ['title' => 'Topic', 'parent' => 'lesson', 'type' => 'topic', 'content' => $topic],
                'related' => [
                    'type' => 'quizzes',
                    'lvl1' => $quizzes->get(),
                ],
            ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Topic $topic)
    {
        $this->authorize('update lms');
        $validated = $request->validate([
            'title' => 'required',
            '_content' => 'sometimes',
            'course_id' => 'required|numeric',
            'lesson_id' => 'required|numeric',
            'estimated_time' => 'required|numeric',
            'featured_image' => 'sometimes|file|mimes:jpg,png,jpeg',
        ]);
        if ($topic->title !== $validated['title']) {
            $topic->title = $validated['title'];
            $topic->slug = \Str::slug($validated['title']);
        }
        $topicTimeUpdated = false;

        //        logger([
        //            floatval($topic->estimated_time),
        //            floatval($validated['estimated_time']),
        //            floatval($topic->estimated_time) !== floatval($validated['estimated_time'])
        //        ]);

        if (floatval($topic->estimated_time) !== floatval($validated['estimated_time'])) {
            $topic->estimated_time = floatval($validated['estimated_time']);
            $topicTimeUpdated = true;
        }
        $topic->course_id = $validated['course_id'];
        $topic->lesson_id = $validated['lesson_id'];
        $topic->lb_content = $validated['_content'];
        $topic->save();

        if (!empty($validated['featured_image'])) {
            $this->featuredImage($validated['featured_image'], $topic);
        }

        if ($topicTimeUpdated) {
            $topic = $topic->first();

            info('Triggering UpdateCourseProgress');
            UpdateCourseProgress::dispatch($topic);

            activity('Scheduled Job UpdateCourseProgress')
                ->event('UpdateCourseProgress')
                ->causedBy(auth()->user())
                ->performedOn($topic)
                ->log('Scheduled Job Topic Time Update');

            info('Scheduled Job UpdateCourseProgress');
            UpdateAdminReport::dispatch($topic);

            activity('Scheduled Job UpdateAdminReport')
                ->event('UpdateAdminReport')
                ->causedBy(auth()->user())
                ->performedOn($topic)
                ->log('Scheduled Job Topic Time Update');
            info('Scheduled Job UpdateAdminReport');
        }

        return redirect()->route('lms.topics.show', $topic->id)
            ->with('success', 'Topic created successfully.');
    }

    public function reorder(Request $request, Topic $topic, $type)
    {
        if (!$request->order) {
            return response()->json([
                'code' => (404 + 300),
                'status' => 'error',
                'success' => false,
                'message' => 'Whoops, looks like something went wrong',
            ], 404);
        }
        foreach ($request->order as $pos => $quiz_id) {
            Quiz::where('id', $quiz_id)->where('topic_id', $topic->id)->update(['order' => $pos]);
        }

        return response()->json([
            'data' => [],
            'success' => true, 'status' => 'success',
            'message' => 'Topics re-ordered successfully',
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Topic $topic)
    {
        $this->authorize('delete lms');
        if ($topic->hasQuizzes() < 1) {
            $exTopic = $topic;
            $topic->delete();

            activity()
                ->performedOn($exTopic)
                ->causedBy(auth()->user())
                ->withProperties([
                    'activity_event' => 'TOPIC DELETED',
                    'activity_details' => [
                        'topic' => $exTopic->toArray(),
                        'by' => auth()->user()->id,
                        'ip' => request()->ip(),
                    ],
                ])
                ->log('Topic Deleted');

            return response()->json([
                'data' => $exTopic->toArray(),
                'success' => true, 'status' => 'success',
                'message' => 'Topic deleted successfully',
            ], 201);
        }

        return Helper::errorResponse('Delete associated quizzes first.', 403);
    }

    private function featuredImage($featured_image, Topic $model): void
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
