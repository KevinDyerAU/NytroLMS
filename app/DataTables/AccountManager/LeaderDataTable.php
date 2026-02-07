<?php

namespace App\DataTables\AccountManager;

use App\Models\User;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class LeaderDataTable extends DataTable
{
    protected bool $fastExcel = true;

    protected int $chunkSize = 7000;

    // Process 1000 records at a time

    public function fastExcelCallback(): \Closure
    {
        return function (User $user) {
            $companies = $user->companies
                ->map(function ($company) {
                    return $company->name;
                })
                ->implode(', ');

            return [
                'ID' => $user->id,
                'First Name' => $user->first_name,
                'Last Name' => $user->last_name,
                'Username' => $user->username,
                'Status' => $user->displayable_active,
                'Email' => $user->email,
                'Phone' => $user->detail->phone ?? '',
                'Position' => $user->detail->position ?? '',
                'Role' => $user->detail->role ?? '',
                'Company' => $companies,
                'Last Signed In' => $user->detail->last_logged_in ?? '',
                'Created On' => $user->created_at,
                'First Login' => $user->detail->first_login ?? '',
                'First Enrolment' => $user->detail->first_enrollment ?? '',
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
            ->filterColumn('displayable_active', function ($query, $keyword) {
                $query->whereRaw(
                    "IF(is_active = 1, 'Active', 'In Active') like ?",
                    ["%{$keyword}%"]
                );
            })
            ->orderColumn('displayable_active', function ($query, $order) {
                $query->orderBy('is_active', $order);
            })
            ->addColumn('action', 'accountmanager/leader.action')
            ->rawColumns(['companies']);
    }

    /**
     * Get query source of dataTable.
     *
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(User $model)
    {
        return $model
            ->newQuery()
            ->select([
                'users.id',
                'users.first_name',
                'users.last_name',
                'users.username',
                'users.email',
                'users.is_active',
                'users.created_at',
            ])
            ->with([
                'detail:user_id,phone,position,role,last_logged_in,first_login,first_enrollment',
                'roles:id,name',
                'companies:id,name',
            ])
            ->whereHas('roles', function ($query) {
                $query->where('roles.name', '=', 'Leader');
            });
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

        return $this->builder()
            ->setTableId('leader-table')
            ->addTableClass(['table-responsive', 'display', 'nowrap'])
            ->responsive(false)
            ->columns($this->getColumns())
            ->minifiedAjax($url, null, $urlData)
            //                    ->paging( FALSE )
            ->parameters([
                'searchDelay' => 600,
                'order' => [
                    3, // here is the column number
                    'desc',
                ],
                //                'buttons' => ['csv'],
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
            Column::make('is_active')->visible(false),
            Column::make('displayable_active')
                ->data('displayable_active')
                ->name('displayable_active')
                ->title('Status'),
            Column::make('email'),
            Column::make('detail.phone')->title('Phone'),
            Column::make('detail.position')->title('Position'),
            Column::make('detail.role')->title('Role'),
            Column::make('companies')
                ->title('Company')
                ->searchable(false)
                ->orderable(false),
            Column::make('detail.last_logged_in')->title('Last Signed In'),
            Column::make('created_at')->title('Created On'),
            Column::make('detail.first_login')->title('First Login'),
            Column::make('detail.first_enrollment')->title('First Enrolment'),
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'Leader_' . date('YmdHis');
    }
}
