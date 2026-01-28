<?php

namespace App\DataTables\AccountManager;

use App\Helpers\Helper;
use App\Models\User;
use Carbon\Carbon;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class StudentDataTable extends DataTable
{
    protected bool $fastExcel = true;

    public function fastExcelCallback(): \Closure
    {
        return function (User $user) {
            $exportCols = [
                'ID' => $user->id,
                'First Name' => $user->first_name,
                'Last Name' => $user->last_name,
                'Username' => $user->username,
                'Email' => $user->email,
                'Phone' => $user->detail->phone,
                'Status' => $user->is_active ? 'Active' : 'In Active',
                'Company' => $user->companies
                    ->map(function ($company) {
                        return $company->name;
                    })
                    ->implode(', '),
                'Leader' => $user->leaders
                    ->map(function ($leader) {
                        return $leader->name;
                    })
                    ->implode(', '),
                'Trainer' => $user->trainers
                    ->map(function ($trainer) {
                        return $trainer->name;
                    })
                    ->implode(', '),
                'Created At' => $user->created_at,
            ];

            return $exportCols;
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
            ->editColumn('companies', function (User $student) {
                return $student->companies
                    ->map(function ($company) {
                        return '<a href="' .
                            route(
                                'account_manager.companies.show',
                                $company->id
                            ) .
                            '">' .
                            $company->name .
                            '</a>';
                    })
                    ->implode(', ');
            })
            ->editColumn('trainers', function (User $student) {
                if (
                    auth()
                        ->user()
                        ->hasRole(['Leader'])
                ) {
                    return '';
                }

                return $student->trainers
                    ->map(function ($trainer) {
                        return '<a href="' .
                            route(
                                'account_manager.trainers.show',
                                $trainer->id
                            ) .
                            '">' .
                            $trainer->name .
                            '</a>';
                    })
                    ->implode(', ');
            })
            ->editColumn('leaders', function (User $student) {
                if (
                    auth()
                        ->user()
                        ->hasRole(['Leader', 'Trainer'])
                ) {
                    return $student->leaders
                        ->map(function ($leader) {
                            return '<span>' . $leader->name . '</span>';
                        })
                        ->implode(', ');
                }

                return $student->leaders
                    ->map(function ($leader) {
                        return '<a href="' .
                            route('account_manager.leaders.show', $leader->id) .
                            '">' .
                            $leader->name .
                            '</a>';
                    })
                    ->implode(', ');
            })
            ->filterColumn('displayable_active', function ($query, $keyword) {
                $query->whereRaw(
                    "IF(is_active = 1, 'Active', 'In Active') like ?",
                    ["%{$keyword}%"]
                );
            })
            ->orderColumn('displayable_active', function ($query, $order) {
                $query->orderBy('is_active', $order);
            })
            //            ->filterColumn('leaders', function($query, $keyword) {
            //                if($keyword === '1') {
            //                    return $this->filteredQuery();
            //                }
            //                return $this->defaultQuery();
            //            })
            //            ->editColumn('is_active', function (User $student) {
            //                $status = $student->is_active ? "ACTIVE" : "IN_ACTIVE";
            //                $color = config('constants.status.color.' . $status);
            //                return '<span class="text-' . $color . '">' . \Str::title(str_replace('_', ' ', $status)) . '</span>';
            //            })
            //            ->addColumn('enrolled_course', function(User $student){
            //                return '';
            //            })
            ->addColumn('action', 'accountmanager/student.action')
            ->rawColumns(['companies', 'trainers', 'leaders']);
    }

    public function defaultQuery()
    {
        $model = new User();
        $return = $model
            ->newQuery()
            ->with(['detail', 'roles', 'companies', 'trainers', 'leaders'])
            ->whereHas('roles', function ($query) {
                $query->where('roles.name', '=', 'Student');
            });
        //        dd($this->for, $this->status);
        if ($this->for) {
            $timeZoneOffset = Helper::getTimeZoneOffset();
            $start = Carbon::parse($this->for)->timezone('GMT');

            if ($timeZoneOffset > 0) {
                $start = $start->subHours($timeZoneOffset);
            } else {
                $start = $start->addHours($timeZoneOffset);
            }
            $end = $start->clone()->addDay();
            //            dd($this->for, $start->toDateTimeString(), $end->toDateTimeString());

            $return = $return
                ->where('users.created_at', '>', $start->toDateTimeString())
                ->where('users.created_at', '<', $end->toDateTimeString());
        }
        if ($this->status) {
            if ($this->status === 'non_commenced') {
                $return = $return
                    ->whereHas('detail', function ($query) {
                        $query->whereNull('user_details.onboard_at');
                    })
                    ->where('users.is_active', '=', 1);
            } else {
                $return = $return->where(
                    'users.is_active',
                    $this->status !== 'inactive'
                );
            }
        }
        if (
            auth()
                ->user()
                ->isLeader()
        ) {
            $return = $return->isRelatedLeader();
        }

        //        if(auth()->user()->isTrainer()) {
        //            $return = $return->isRelatedTrainer();
        //        }
        return $return->orderBy('id');
    }

    public function filteredQuery()
    {
        $model = new User();
        $return = $model
            ->newQuery()
            ->with(['detail', 'roles', 'companies', 'trainers', 'leaders'])
            ->whereHas('roles', function ($query) {
                $query->where('roles.name', '=', 'Student');
            });
        //        dd($this->for, $this->status);
        if ($this->for) {
            $timeZoneOffset = Helper::getTimeZoneOffset();
            $start = Carbon::parse($this->for)->timezone('GMT');

            if ($timeZoneOffset > 0) {
                $start = $start->subHours($timeZoneOffset);
            } else {
                $start = $start->addHours($timeZoneOffset);
            }
            $end = $start->clone()->addDay();
            //            dd($this->for, $start->toDateTimeString(), $end->toDateTimeString());

            $return = $return
                ->where('users.created_at', '>', $start->toDateTimeString())
                ->where('users.created_at', '<', $end->toDateTimeString());
        }
        if ($this->status) {
            if ($this->status === 'non_commenced') {
                $return = $return
                    ->whereHas('detail', function ($query) {
                        $query->whereNull('user_details.onboard_at');
                    })
                    ->where('users.is_active', '=', 1);
            } else {
                $return = $return->where(
                    'users.is_active',
                    $this->status !== 'inactive'
                );
            }
        }
        if (
            auth()
                ->user()
                ->isLeader()
        ) {
            $return = $return->isRelatedCompany();
        }

        //        if(auth()->user()->isTrainer()) {
        //            $return = $return->isRelatedTrainer();
        //        }
        return $return->orderBy('id');
    }

    /**
     * Get query source of dataTable.
     *
     *
     * @return \Illuminate\Database\Eloquent\Builder
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
        if ($this->registration_date) {
            $return = $return
                ->join(
                    'student_course_enrolments',
                    'users.id',
                    '=',
                    'student_course_enrolments.user_id'
                )
                ->select(
                    'users.*',
                    'student_course_enrolments.id as enrolment_id'
                )
                ->where(function ($query) {
                    $query
                        ->whereRaw(
                            'DATE(CONVERT_TZ(student_course_enrolments.created_at, "+00:00", "+10:00")) = ?',
                            [$this->registration_date]
                        )
                        ->orWhere(function ($subQuery) {
                            $subQuery
                                ->whereRaw(
                                    'DATE(CONVERT_TZ(student_course_enrolments.registration_date, "+00:00", "+10:00")) = ?',
                                    [$this->registration_date]
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
        }

        if ($this->for) {
            $timeZoneOffset = Helper::getTimeZoneOffset();
            $start = Carbon::parse($this->for)->timezone('GMT');

            if ($timeZoneOffset > 0) {
                $start = $start->subHours($timeZoneOffset);
            } else {
                $start = $start->addHours($timeZoneOffset);
            }
            $end = $start->clone()->addDay();

            $return = $return
                ->where('users.created_at', '>', $start->toDateTimeString())
                ->where('users.created_at', '<', $end->toDateTimeString());
        }

        if ($this->status) {
            if ($this->status === 'non_commenced') {
                $return = $return
                    ->whereHas('detail', function ($query) {
                        $query->whereNull('user_details.onboard_at');
                    })
                    ->where('users.is_active', '=', 1);
            } elseif ($this->status === 'behind_schedule') {
                $return = $return
                    ->where('users.is_active', 1)
                    ->whereIn('users.id', function ($query) {
                        $query
                            ->select('user_id')
                            ->from('student_course_stats')
                            ->where('course_status', 'BEHIND SCHEDULE');
                    });
            } else {
                $return = $return->where(
                    'users.is_active',
                    $this->status !== 'inactive'
                );
            }
        }

        if (
            auth()
                ->user()
                ->isLeader()
        ) {
            if ($this->show_all === '1') {
                $return = $return->isRelatedCompany();
            } else {
                $return = $return->isRelatedLeader();
            }
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
        $url = url()->current();
        if ($this->request()->has('status')) {
            $urlData += ['status' => $this->request()->get('status')];
        }
        if ($this->request()->has('for')) {
            $urlData += ['for' => $this->request()->get('for')];
        }
        if ($this->request()->has('show_all')) {
            $urlData += ['show_all' => $this->request()->get('show_all')];
        }
        if ($this->request()->has('registration_date')) {
            $urlData += [
                'registration_date' => $this->request()->get(
                    'registration_date'
                ),
            ];
            //            dd($this->request()->get( 'registration_date' ), $urlData);
        }

        return $this->builder()
            ->setTableId('student-table')
            ->addTableClass(['table-responsive', 'display', 'nowrap'])
            ->responsive(false)
            ->columns($this->getColumns())
            ->minifiedAjax($url, null, $urlData)
            ->parameters([
                'searchDelay' => 600,
                'order' => [
                    9, // here is the column number
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
                    ->authorized(
                        auth()
                            ->user()
                            ->can('download reports')
                    )
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
            Column::make('first_name'),
            Column::make('last_name'),
            Column::make('username')->title('User Name'),
            Column::make('email'),
            Column::make('detail.phone')
                ->title('Phone')
                ->visible(true),
            Column::make('is_active')->visible(false),
            Column::make('displayable_active')
                ->data('displayable_active')
                ->name('displayable_active')
                ->title('Status'),
            Column::make('companies')
                ->title('Company')
                ->addClass('text-wrap')
                ->searchable(false)
                ->orderable(false)
                ->visible(
                    auth()
                        ->user()
                        ->can('view companies')
                ),
            Column::make('trainers')
                ->title('Trainer')
                ->searchable(false)
                ->orderable(false)
                ->visible(
                    auth()
                        ->user()
                        ->can('view trainers')
                ),
            Column::make('leaders')
                ->title('Leader')
                ->searchable(false)
                ->orderable(false),
            Column::make('created_at')
                ->title('Created At')
                ->searchable(false)
                ->orderable(false),
            //            Column::make('enrolled_course')->title('Enrolled Course')->searchable(false)->orderable(false),
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'Student_' . date('YmdHis');
    }
}
