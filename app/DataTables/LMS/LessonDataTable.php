<?php

namespace App\DataTables\LMS;

use App\Models\Lesson;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class LessonDataTable extends DataTable
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
            ->of($query)
            ->addColumn('', '')
            ->addColumn('', '')
            ->editColumn('associated', function ($lesson) {
                $output = '';
                if (!empty($lesson->course_title)) {
                    $output =
                        'Course: <a href="' .
                        route('lms.courses.show', $lesson->course_id) .
                        '">' .
                        $lesson->course_title .
                        '</a>';
                }

                return $output;
            })
            ->editColumn('release_plan', function ($lesson) {
                $output = '';
                if ($lesson->release_key === 'XDAYS') {
                    $output = $lesson->release_value . ' days';
                } elseif ($lesson->release_key === 'DATE') {
                    $output = Carbon::parse(
                        $lesson->release_value
                    )->toDateString();
                } else {
                    $output = 'Immediately';
                }

                return $output;
            })
            ->editColumn('course.is_archived', function ($lesson) {
                return $lesson->is_archived ? 'Yes' : 'No';
            })
            ->orderColumn('course.is_archived', function ($query, $order) {
                $query->orderBy(
                    'courses.is_archived',
                    $order == 'desc' ? 'asc' : 'desc'
                );
            })
            ->addColumn('action', 'lms/lesson.action')
            ->rawColumns(['associated', 'release_plan', 'is_archived']);
    }

    /**
     * Get query source of dataTable.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Lesson $model)
    {
        //        return $model->newQuery()->with(['course'])->selectRaw('lessons.*');

        $query = DB::table('lessons')
            ->join('courses', 'lessons.course_id', '=', 'courses.id')
            ->select(
                'lessons.*',
                'courses.is_archived',
                'courses.title as course_title'
            );

        return $this->applyScopes($query);
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
                    4, // here is the column number
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
            Column::make('title'),
            Column::make('course.is_archived')
                ->title('Course Archived')
                ->orderable(true)
                ->searchable(false),
            Column::make('associated')
                ->title('Associated Content')
                ->name('associated')
                ->orderable(false)
                ->searchable(false),
            //            Column::make('next_course_after_days'),
            Column::make('release_plan')
                ->orderable(false)
                ->searchable(false),
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'LMS/Lesson_' . date('YmdHis');
    }
}
