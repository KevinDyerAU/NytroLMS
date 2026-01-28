<?php

namespace App\DataTables\Reports;

use App\Helpers\Helper;
use App\Models\User;
use Carbon\Carbon;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class DailyEnrolmentReportDataTable extends DataTable
{
    protected bool $fastExcel = true;
    protected ?string $registrationDate = null;

    public function fastExcelCallback(): \Closure
    {
        return function (User $user) {
            return [
                'ID' => $user->id ?? '',
                'First Name' => $user->first_name ?? '',
                'Last Name' => $user->last_name ?? '',
                'Status' => $user->is_active ? 'Active' : 'In Active',
                'Course Name' => $user->course_name ?? '',
                'Company' => $user->companies
                    ->map(function ($company) {
                        return $company->name;
                    })
                    ->implode(', '),
                'Trainer' => $user->trainers
                    ->map(function ($trainer) {
                        return $trainer->name;
                    })
                    ->implode(', '),
                'Leader' => $user->leaders
                    ->map(function ($leader) {
                        return $leader->name;
                    })
                    ->implode(', '),
                'Created At' => $user->enrolment_created_at ?? ''
                    ? Carbon::parse($user->enrolment_created_at)
                        ->timezone(Helper::getTimeZone())
                        ->format('d-m-Y H:i:s')
                    : '',
            ];
        };
    }

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
            ->addColumn('course_name', function (User $user) {
                // Access the aliased column from the join - check both attributes and raw original
                return $user->getRawOriginal('course_name')
                    ?? (isset($user->getAttributes()['course_name']) ? $user->getAttributes()['course_name'] : null)
                    ?? '';
            })
            ->addColumn('displayable_active', function (User $user) {
                // Show Active/In Active based on is_active, same as student table
                return $user->is_active ? 'Active' : 'Inactive';
            })
            ->addColumn('companies', function (User $user) {
                return $user->companies
                    ->map(function ($company) {
                        return '<a href="' .
                            route('account_manager.companies.show', $company->id) .
                            '">' .
                            $company->name .
                            '</a>';
                    })
                    ->implode(', ');
            })
            ->addColumn('trainers', function (User $user) {
                if (auth()->user()->hasRole(['Leader'])) {
                    return '';
                }
                return $user->trainers
                    ->map(function ($trainer) {
                        return '<a href="' .
                            route('account_manager.trainers.show', $trainer->id) .
                            '">' .
                            $trainer->name .
                            '</a>';
                    })
                    ->implode(', ');
            })
            ->addColumn('leaders', function (User $user) {
                return $user->leaders
                    ->map(function ($leader) {
                        return '<a href="' .
                            route('account_manager.leaders.show', $leader->id) .
                            '">' .
                            $leader->name .
                            '</a>';
                    })
                    ->implode(', ');
            })
            ->addColumn('created_at', function (User $user) {
                // Access the aliased column from the join
                $createdAt = $user->getRawOriginal('enrolment_created_at')
                    ?? (isset($user->getAttributes()['enrolment_created_at']) ? $user->getAttributes()['enrolment_created_at'] : null)
                    ?? '';
                return $createdAt
                    ? Carbon::parse($createdAt)
                        ->timezone(Helper::getTimeZone())
                        ->format('d-m-Y H:i:s')
                    : '';
            })
            ->rawColumns(['companies', 'trainers', 'leaders']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\User $model
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public function query(User $model)
    {
        $model = new User();
        $return = $model
            ->newQuery()
            ->with(['detail', 'roles', 'companies', 'trainers', 'leaders'])
            ->whereHas('roles', function ($query) {
                $query->where('roles.name', '=', 'Student');
            });

        // Always join with student_course_enrolments and courses for this report
        $registrationDate = $this->registrationDate ?? Carbon::today(Helper::getTimeZone())->format('Y-m-d');

        $return = $return
            ->join(
                'student_course_enrolments',
                'users.id',
                '=',
                'student_course_enrolments.user_id'
            )
            ->join(
                'courses',
                'student_course_enrolments.course_id',
                '=',
                'courses.id'
            )
            ->select(
                'users.*',
                'student_course_enrolments.id as enrolment_id',
                'student_course_enrolments.status as enrolment_status',
                'student_course_enrolments.created_at as enrolment_created_at',
                'courses.id as course_id',
                'courses.title as course_name'
            )
            ->where(function ($query) use ($registrationDate) {
                $query
                    ->whereRaw(
                        'DATE(CONVERT_TZ(student_course_enrolments.created_at, "+00:00", "+10:00")) = ?',
                        [$registrationDate]
                    )
                    ->orWhere(function ($subQuery) use ($registrationDate) {
                        $subQuery
                            ->whereRaw(
                                'DATE(CONVERT_TZ(student_course_enrolments.registration_date, "+00:00", "+10:00")) = ?',
                                [$registrationDate]
                            )
                            ->where(function ($innerQuery) {
                                $innerQuery
                                    ->where(
                                        'student_course_enrolments.is_chargeable',
                                        1
                                    )
                                    ->orWhere(
                                        'student_course_enrolments.registered_on_create',
                                        1
                                    );
                            });
                    });
            })
            ->where('student_course_enrolments.status', '!=', 'DELIST')
            ->where(function ($query) {
                $query
                    ->where(function ($subQuery) {
                        $subQuery
                            ->where(
                                'student_course_enrolments.is_main_course',
                                1
                            )
                            ->orWhere(
                                'student_course_enrolments.is_semester_2',
                                1
                            );
                    })
                    ->orWhere(function ($subQuery) {
                        $subQuery
                            ->where(
                                'student_course_enrolments.is_main_course',
                                1
                            )
                            ->where(
                                'student_course_enrolments.is_semester_2',
                                1
                            );
                    });
            })
            ->groupBy('users.id');

        // Leader filter
        if (auth()->user()->isLeader()) {
            $return = $return->isRelatedLeader();
        }

        return $return->distinct()->orderBy('users.id');
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        $urlData = [];
        if ($this->request()->has('registration_date')) {
            $urlData += [
                'registration_date' => $this->request()->get('registration_date'),
            ];
        }

        return $this->builder()
            ->setTableId('daily-enrolment-table')
            ->addTableClass(['table-responsive', 'display', 'nowrap'])
            ->responsive(false)
            ->columns($this->getColumns())
            ->minifiedAjax(url()->current(), null, $urlData)
            ->parameters([
                'searchDelay' => 600,
                'order' => [[2, 'asc']], // Order by ID ascending to match students page
            ])
            ->buttons(
                Button::make('export')
                    ->text("<i class='font-small-4 me-50' data-lucide='share'></i>Export")
                    ->className('dt-button buttons-collection btn btn-outline-secondary dropdown-toggle me-2')
                    ->buttons([
                        Button::make('postCsv')
                            ->text("<i data-lucide='file-text'></i> CSV")
                            ->className('dropdown-item')
                            ->exportOptions(['columns' => ':visible']),
                    ])
                    ->authorized(auth()->user()->can('download reports'))
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
            Column::make('first_name')->title('First Name'),
            Column::make('last_name')->title('Last Name'),
            Column::make('displayable_active')
                ->data('displayable_active')
                ->name('displayable_active')
                ->title('Status'),
            Column::make('course_name')->title('Course Name')->searchable(false)->orderable(false),

            Column::make('companies')
                ->title('Company')
                ->addClass('text-wrap')
                ->searchable(false)
                ->orderable(false)
                ->visible(auth()->user()->can('view companies')),
            Column::make('trainers')
                ->title('Trainer')
                ->searchable(false)
                ->orderable(false)
                ->visible(auth()->user()->can('view trainers')),
            Column::make('leaders')
                ->title('Leader')
                ->searchable(false)
                ->orderable(false),
            Column::make('created_at')
                ->title('Created At')
                ->searchable(false)
                ->orderable(true),
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        $date = $this->registrationDate ?? Carbon::today(Helper::getTimeZone())->format('Y-m-d');
        return 'Daily_Enrolment_Report_' . $date . '_' . date('YmdHis');
    }

    /**
     * Set registration date filter
     */
    public function setRegistrationDate(?string $date): self
    {
        $this->registrationDate = $date;
        return $this;
    }
}
