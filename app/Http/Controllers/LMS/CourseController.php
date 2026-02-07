<?php

namespace App\Http\Controllers\LMS;

use App\DataTables\LMS\ArchivedCourseDataTable;
use App\DataTables\LMS\CourseDataTable;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Image;
use App\Models\Lesson;
use App\Services\CourseProgressService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(CourseDataTable $dataTable)
    {
        $this->authorize('manage lms');

        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'LMS'],
            ['link' => route('lms.courses.index'), 'name' => 'Courses'],
        ];

        $actionItems = [];
        if (auth()->user()->can('manage lms')) {
            $actionItems = [
                0 => ['link' => route('lms.courses.create'), 'icon' => 'plus-square', 'title' => 'Add New Course'],
            ];
        }

        return $dataTable->render('content.lms.posts.index', [
            'pageConfigs' => $pageConfigs,
            'breadcrumbs' => $breadcrumbs,
            'actionItems' => $actionItems,
            'post' => ['title' => 'Course', 'type' => 'course', 'content' => null],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function create(): \Illuminate\View\View
    {
        $this->authorize('create lms');
        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'LMS'],
            ['link' => route('lms.courses.index'), 'name' => 'Course'],
            ['name' => 'Add New Course'],
        ];

        return view()->make('content.lms.post.add-edit')
            ->with([
                'action' => ['url' => route('lms.courses.store'), 'name' => 'Create'],
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
                'post' => ['title' => 'Course', 'type' => 'course', 'content' => null],
            ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('create lms');
        $validated = $request->validate([
            'title' => 'required',
            '_content' => 'required',
            'course_length_days' => 'required',
            'course_expiry_days' => 'nullable|numeric',
            'next_course' => 'nullable|numeric',
            'next_course_after_days' => 'required_if:next_course,!=,null|nullable|numeric',
            'auto_register_next_course' => 'required_if:next_course,!=,null|nullable|boolean',
            'featured_image' => 'sometimes|file|mimes:jpg,png,jpeg',
            'visibility' => 'required',
            'status' => 'required',
            'is_archived' => 'nullable|numeric',
            'is_main_course' => 'nullable|numeric',
            'category' => ['nullable', Rule::in(array_keys(config('lms.course_category')))],
            'restricted_roles.*' => 'sometimes|exists:roles,id',
            'version' => 'required|numeric',
        ]);

        if ((Str::contains(Str::lower($validated['title']), ['semester 2', 'SEMESTER 2', 'Semester 2']))) {
            $validated['is_main_course'] = 0;
        } else {
            $validated['is_main_course'] = 1;
        }

        if ($validated['status'] === 'PUBLISHED') {
            $validated['published_at'] = Carbon::now(Helper::getTimeZone())->toDateTimeString();
        }

        $course = new Course();

        $this->toDatabase($validated, $course);

        if (!empty($validated['featured_image'])) {
            $this->featuredImage($validated['featured_image'], $course);
        }

        return redirect()->route('lms.courses.index')
            ->with('success', 'Course created successfully.');
    }

    /**
     * Display the specified resource.
     *
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function show(Course $course)
    {
        $this->authorize('view lms');
        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'LMS'],
            ['link' => route('lms.courses.index'), 'name' => 'Course'],
            ['name' => 'View Course'],
        ];

        $actionItems = [
            0 => ['link' => route('lms.courses.edit', $course), 'icon' => 'edit', 'title' => 'Edit Course'],
            //            1 => ['link' => route('lms.courses.destroy', $course), 'icon' => 'x-circle', 'title' => 'Delete Course'],
            2 => ['link' => route('lms.courses.create'), 'icon' => 'plus-square', 'title' => 'Add New Course'],
        ];

        $lessons = $course->lessons()->orderBy('order');

        return view()->make('content.lms.post.show')
            ->with([
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
                'actionItems' => $actionItems,
                'post' => ['title' => 'Course', 'type' => 'course', 'content' => $course],
                'related' => [
                    'type' => 'lessons',
                    'lvl1' => $lessons->get(),
                ],
            ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function edit(Course $course): \Illuminate\View\View
    {
        $this->authorize('update lms');
        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'LMS'],
            ['link' => route('lms.courses.index'), 'name' => 'Course'],
            ['name' => 'Edit Course'],
        ];
        $actionItems = [
            0 => ['link' => route('lms.courses.show', $course), 'icon' => 'file-text', 'title' => 'View Course'],
            1 => ['link' => route('lms.courses.create'), 'icon' => 'plus-square', 'title' => 'Add New Course'],
        ];
        $lessons = $course->lessons()->orderBy('order');

        return view()->make('content.lms.post.add-edit')
            ->with([
                'action' => ['url' => route('lms.courses.update', $course), 'name' => 'Edit'],
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
                'actionItems' => $actionItems,
                'post' => ['title' => 'Course', 'type' => 'course', 'content' => $course],
                'related' => [
                    'type' => 'lessons',
                    'lvl1' => $lessons->get(),
                ],
            ]);
    }

    /**
     * Update the specified resource in storage.
     *
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Request $request, Course $course): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update lms');
        $validated = $request->validate([
            'title' => 'required',
            '_content' => 'required',
            'course_length_days' => 'required',
            'course_expiry_days' => 'nullable|numeric',
            'next_course' => 'nullable|numeric',
            'next_course_after_days' => 'required_unless:next_course,"none"|integer',
            'auto_register_next_course' => 'nullable|boolean',
            'featured_image' => 'sometimes|file|mimes:jpg,png,jpeg',
            'visibility' => 'required',
            'status' => 'required',
            'is_archived' => 'nullable|numeric',
            'is_main_course' => 'nullable|numeric',
            'category' => ['nullable', Rule::in(array_keys(config('lms.course_category')))],
            'restricted_roles.*' => 'sometimes|exists:roles,id',
            'version' => 'required|numeric',
        ]);

        $validated['revisions'] = $course['revisions'] ? intval($course['revisions']) + 1 : 1;

        if ((Str::contains(Str::lower($validated['title']), ['semester 2', 'SEMESTER 2', 'Semester 2']))) {
            $validated['is_main_course'] = 0;
        } else {
            $validated['is_main_course'] = 1;
        }

        if ($validated['status'] === 'PUBLISHED' && (empty($course->status) || $course->status !== 'PUBLISHED')) {
            $validated['published_at'] = Carbon::now(Helper::getTimeZone())->toDateTimeString();
        }

        $this->toDatabase($validated, $course, true);

        if (!empty($validated['featured_image'])) {
            $this->featuredImage($validated['featured_image'], $course);
        }

        return redirect()->route('lms.courses.show', $course->id)
            ->with('success', 'Course created successfully.');
    }

    public function reorder(Request $request, Course $course, $type): \Illuminate\Http\JsonResponse
    {
        if (!$request->order) {
            return response()->json([
                'code' => (404 + 300),
                'status' => 'error',
                'success' => false,
                'message' => 'Whoops, looks like something went wrong',
            ], 404);
        }
        foreach ($request->order as $pos => $lesson_id) {
            Lesson::where('id', $lesson_id)->where('course_id', $course->id)->update(['order' => $pos]);
        }

        return response()->json([
            'data' => [],
            'success' => true, 'status' => 'success',
            'message' => 'Lessons re-ordered successfully',
        ], 201);
    }

    public function getArchivedCourses(ArchivedCourseDataTable $dataTable)
    {
        $this->authorize('manage lms');

        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'LMS'],
            ['link' => route('lms.archived-courses.index'), 'name' => 'Archived Courses'],
        ];

        $actionItems = [];
        if (auth()->user()->can('manage lms')) {
            $actionItems = [
                0 => ['link' => route('lms.courses.create'), 'icon' => 'plus-square', 'title' => 'Add New Course'],
            ];
        }

        return $dataTable->render('content.lms.posts.index', [
            'pageConfigs' => $pageConfigs,
            'breadcrumbs' => $breadcrumbs,
            'actionItems' => $actionItems,
            'post' => ['title' => 'Archived Course', 'type' => 'course', 'content' => null],
        ]);
    }

    private function featuredImage($featured_image, Course $model): void
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

    protected function toDatabase(array $validated, Course $course, $update = false): Course
    {
        $published_at = null;
        $course_published = false;

        if (empty($course->getRawOriginal('published_at')) && (empty($course->status) || $course->status !== 'PUBLISHED')) {
            $published_at = Carbon::now(Helper::getTimeZone())->toDateTimeString();
            $course_published = true;
        } else {
            $published_at = $course->getRawOriginal('published_at');
        }

        if ($validated['status'] === 'DRAFT') {
            $published_at = null;
        }

        $course->title = $validated['title'];
        $course->slug = \Str::slug($validated['title']);
        $course->course_length_days = $validated['course_length_days'] ?? '90';
        $course->course_expiry_days = $validated['course_expiry_days'] ?? 0;
        $course->next_course = $validated['next_course'] ?? 0;
        $course->next_course_after_days = $validated['next_course_after_days'] ?? 0;
        $course->auto_register_next_course = isset($validated['next_course']) ? 1 : 0;
        $course->lb_content = $validated['_content'];
        $course->visibility = $validated['visibility'];
        $course->status = $validated['status'];
        $course->category = $validated['category'];
        $course->restricted_roles = $validated['restricted_roles'] ?? [];
        $course->revisions = intval($validated['revisions'] ?? 1);
        $course->is_archived = intval($validated['is_archived'] ?? 0);
        $course->is_main_course = intval($validated['is_main_course'] ?? 0);
        $course->version = intval($validated['version'] ?? 0);
        $course->published_at = $published_at;
        $course->save();

        //        if ( !empty( $validated[ 'course_expiry_days' ] ) ) {
        //            CourseProgressService::setCourseExpiry( $course, ( $update ? 0 : $validated[ 'course_expiry_days' ] ) );
        //        }

        if ($course_published) {
            activity('course_status')
                ->event('PUBLISHED')
                ->performedOn($course)
                ->causedBy(auth()->user())
                ->withProperties([
                    'attributes' => $course->getAttributes(),
                    'changes' => $course->getChanges(),
                    'published_at' => $published_at,
                    'ip' => request()->ip(),
                ])
                ->log('Course Published');
        }

        return $course;
    }
}
