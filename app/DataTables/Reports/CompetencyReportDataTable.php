<?php

namespace App\DataTables\Reports;

use App\Models\StudentCourseEnrolment;
use Carbon\Carbon;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class CompetencyReportDataTable extends DataTable
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
            ->addColumn('action', 'reports/competencyreportdatatable.action')
//            ->filterColumn( 'course', function ( $query, $keyword ) {
//                if ( !isset( $keyword ) ) {
//                    return $query;
//                }
//
//                //'ATTEMPTING','SUBMITTED','REVIEWING','RETURNED','SATISFACTORY','FAIL','OVERDUE'
//                return $query->where( 'course_id', $keyword );
//            } )
            ->filterColumn('course_start', function ($query, $keyword) {
                $searchVal = isset($keyword) ? json_decode($keyword) : '';
                if (!empty($searchVal) && isset($searchVal->start)) {
                    $startDate = Carbon::parse($searchVal->start)->toDateString();
                    $endDate = Carbon::parse($searchVal->end)->toDateString();

                    return $query->whereDate('course_start_at', '>=', $startDate)
                        ->whereDate('course_start_at', '<=', $endDate);
                }

                return '';
            });
    }

    public function query(StudentCourseEnrolment $model)
    {
        $return = $model->newQuery()
            ->with(['course']);

        //        if($this->request()->has('course')){
        //            $return = $return->where('course_id', $this->request()->get( 'course' ));
        //        }
        $return = $return->where('status', '!=', 'DELIST')
            ->whereHas('course')->whereHas('student');
        if ($this->request()->has('start_date') && $this->request()->has('end_date')) {
            $date_start = Carbon::parse($this->request()->get('start_date'))->toDateString();
            $date_end = Carbon::parse($this->request()->get('end_date'))->toDateString();
            $return = $return->whereDate('course_start_at', '>=', $date_start)
                ->whereDate('course_start_at', '<=', $date_end);
        }

        return $return->groupBy('course_id')->orderBy('course_id', 'ASC');
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        $urlData = [];
        $url = url()->current();
        //        if ( $this->request()->has( "course" ) ) {
        //            $urlData[ 'course' ] = $this->request()->get( 'course' );
        //        }
        if ($this->request()->has('start_date')) {
            $urlData['start_date'] = $this->request()->get('start_date');
        }
        if ($this->request()->has('end_date')) {
            $urlData['end_date'] = $this->request()->get('end_date');
        }

        return $this->builder()
            ->setTableId('competencyreportdatatable-table')
            ->addTableClass(['table-responsive', 'display'])
            ->responsive(false)
            ->columns($this->getColumns())
            ->minifiedAjax($url, null, $urlData)
            ->parameters(
                [
                'searchDelay' => 600,
                'order' => [
                    3, // here is the column number
                    'desc',
                ],
                //                'buttons' => ['csv'],
            ]
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
            Column::computed('', '')->responsivePriority(3)->addClass('dt-checkboxes-cell'),
            Column::make('id'),
            Column::make('id')->orderable(false)->visible(false),
            Column::make('course.title'),
            Column::make('course.category'),
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'CompetencyReport_'.date('YmdHis');
    }
}
