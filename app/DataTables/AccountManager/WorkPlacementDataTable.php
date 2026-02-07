<?php

namespace App\DataTables\AccountManager;

use App\Models\WorkPlacement;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class WorkPlacementDataTable extends DataTable
{
    protected bool $fastExcel = true;

    public function fastExcelCallback(): \Closure
    {
        return function (WorkPlacement $model) {
            return [
                'ID' => $model->id,
                'Student Name' => $model->user ? $model->user->name : '',
                'Course Title' => $model->course ? $model->course->title : '',
                'Course Start Date' => $model->course_start_date ?? '',
                'Course End Date' => $model->course_end_date ?? '',
                'Consultation Completed' => $model->consultation_completed
                    ? 'Yes'
                    : '',
                'Consultation Completed On' =>
                    $model->consultation_completed_on ?? '',
                'WP Commencement Date' => $model->wp_commencement_date ?? '',
                'WP End Date' => $model->wp_end_date ?? '',
                'Leader' => $model->leader ? $model->leader->name : '',
                'Company' => $model->company ? $model->company->name : '',
                'Employer Name' => $model->employer_name ?? '',
                'Employer Email' => $model->employer_email ?? '',
                'Employer Phone' => $model->employer_phone
                    ? "'" . $model->employer_phone
                    : '',
                'Employer Address' => $model->employer_address ?? '',
                'Employer Notes' => $model->employer_notes ?? '',
                'Created By' => $model->creator ? $model->creator->name : '',
            ];
        };
    }

    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return datatables()
            ->eloquent($query)
            ->addColumn('user.name', function ($model) {
                return $model->user ? $model->user->name : '';
            })
            ->addColumn('consultation_completed', function ($model) {
                return $model->consultation_completed ? 'Yes' : '';
            })
            ->addColumn(
                'action',
                'accountmanager/student/work_placement.action'
            );
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(WorkPlacement $model): QueryBuilder
    {
        return $model
            ->newQuery()
            ->with(['course', 'user'])
            ->where('user_id', $this->student)
            ->whereHas('course', function ($query) {
                $query->where('is_main_course', true);
            });
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('student-work-placements-table')
            ->addTableClass(['table-responsive', 'display', 'nowrap'])
            ->responsive(false)
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->paging(false)
            ->parameters([
                'searchDelay' => 600,
                'order' => [
                    2, // here is the column number
                    'desc',
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
                        Button::make('postCsv')
                            ->text("<i data-lucide='file-text'></i> CSV")
                            ->className('dropdown-item')
                            ->exportOptions(['columns' => ':visible']),
                    ])
            );
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('id')
                ->orderable(false)
                ->visible(false),
            Column::make('user.name')->title('Student Name'),
            Column::make('course.title')->title('Course Name'),
            Column::make('course_start_date')->title('Course Start'),
            Column::make('course_end_date')->title('Course End'),
            Column::make('consultation_completed')->title(
                'Consultation Completed'
            ),
            Column::make('consultation_completed_on')->title(
                'Consultation Completed On'
            ),
            Column::make('wp_commencement_date')->title('WP Commencement Date'),
            Column::make('wp_end_date')->title('WP End Date'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'WorkPlacement_' . date('YmdHis');
    }
}
