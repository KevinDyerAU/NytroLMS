<?php

namespace App\DataTables\LMS;

use App\Models\Course;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class CourseDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addColumn('', '')
            ->addColumn('', '')
            ->editColumn('published_at', function (Course $course) {
                return $course->published_at
                    ? Carbon::parse(
                        $course->getRawOriginal('published_at')
                    )->toDateTimeString()
                    : '';
            })
            ->editColumn('course_category', function (Course $course) {
                $categories = config('lms.course_category');

                return !empty($course->category)
                    ? $categories[$course->category]
                    : '';
            })
            ->editColumn('pre_course_assessment', function (Course $course) {
                if (
                    \Str::contains($course->title, [
                        'emester 2',
                        'emester-2',
                        'emester_2',
                        'EMESTER 2',
                        'emester2',
                    ])
                ) {
                    return '';
                }

                $preCourse = DB::table('lessons')
                    ->select('lessons.id')
                    ->join('quizzes', function ($join) {
                        return $join
                            ->on('lessons.id', '=', 'quizzes.lesson_id')
                            ->whereNotNull('quizzes.lesson_id')
                            ->where('lessons.order', 0);
                    })
                    ->where('lessons.course_id', $course->id)
                    ->first();

                return !empty($preCourse) ? '' : 'Missing';
            })
            ->addColumn('action', 'lms/course.action')
            ->rawColumns([
                'published_at',
                'course_category',
                'pre_course_assessment',
            ]);
    }

    /**
     * Get query source of dataTable.
     *
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Course $model)
    {
        return $model
            ->newQuery()
            ->with('lessons')
            ->notRestricted()
            ->where('is_archived', '!=', 1);
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
            ->setTableId('lms-table')
            ->addTableClass(['table-responsive', 'display', 'nowrap'])
            ->responsive(false)
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'searchDelay' => 600,
                'order' => [
                    5, // here is the column number
                    'asc',
                ],
            ])
            ->buttons(
                Button::make('export')
                    ->text(
                        "<i class='font-small-4 me-50' data-lucide='share'></i>Export"
                    )
                    ->className(
                        'dt-button buttons-collection btn btn-outline-secondary dropdown-toggle me-2'
                    )
                    ->buttons([
                        Button::make('csv')
                            ->text("<i data-lucide='file-text'></i> CSV")
                            ->className('dropdown-item')
                            ->exportOptions(['columns' => ':visible']),
                    ])
            );
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
            Column::computed('', '')
                ->exportable(false)
                ->printable(false)
                ->responsivePriority(2)
                ->addClass('control'),
            Column::computed('', '')
                ->responsivePriority(3)
                ->addClass('dt-checkboxes-cell'),
            Column::make('id'),
            Column::make('id')
                ->orderable(false)
                ->visible(false),
            //            Column::computed('action')
            //                  ->exportable(false)
            //                  ->printable(false)
            //                  ->width(60)
            //                  ->addClass('text-center text-nowrap'),
            Column::make('version'),
            Column::make('title'),
            Column::make('pre_course_assessment')
                ->title('Pre-Course Assessment')
                ->orderable(false)
                ->searchable(false),
            Column::make('course_length_days')->title(
                'Course Length (in Days)'
            ),
            Column::make('course_expiry_days')->title(
                'Course Expiry (in Days)'
            ),
            Column::make('course_category')
                ->title('Category')
                ->orderable(false)
                ->searchable(false),
            Column::make('status'),
            Column::make('visibility'),
            Column::make('published_at')->title('Published Date'),
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'LMS/Course_' . date('YmdHis');
    }
}
