<?php

namespace App\DataTables\AccountManager\Company;

use App\Models\User;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class LeadersDataTable extends DataTable
{
    protected bool $fastExcel = true;

    public function fastExcelCallback(): \Closure
    {
        return function (User $user) {
            return [
                'Leader ID' => $user->id,
                'First Name' => $user->first_name,
                'Last Name' => $user->last_name,
                'Position' => $user->detail->position,
                'Role' => $user->detail->role,
                'Status' => $user->displayable_active,
                'Email' => $user->email,
                'Phone' => $user->detail->phone,
                'Last Signed In' => $user->detail->last_logged_in,
                'First Login' => $user->detail->first_login,
                'First Enrolment' => $user->detail->first_enrollment,
                'Created On' => $user->created_at,
            ];
        };
    }

    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addColumn('action', 'accountmanager/comapny/leader.action');
    }

    public function query(User $model)
    {
        return $model
            ->newQuery()
            ->with(['detail', 'roles', 'companies'])
            ->onlyCompanyLeaders($this->company);
        //                     ->whereHas( 'roles', function ( $query ) {
        //                         $query->where( 'roles.name', '=', 'Leader' );
        //                     } )
        //                     ->whereHas( 'companies', function ( $query ) {
        //                         $query->where( 'companies.id', '=', $this->company );
        //                     } );
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('companyleadersdatatable-table')
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
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
            Column::make('id')
                ->orderable(false)
                ->visible(false),
            Column::make('first_name'),
            Column::make('last_name'),
            Column::make('detail.position')
                ->title('Position')
                ->name('position'),
            Column::make('detail.role')
                ->title('Role')
                ->name('role'),
            Column::make('email'),
            Column::make('detail.phone')
                ->title('Phone')
                ->name('phone'),
            Column::make('detail.last_logged_in')
                ->title('Last Signed In')
                ->name('last_signed_in'),
            Column::make('detail.first_login')
                ->title('First Login')
                ->name('first_login'),
            Column::make('detail.first_enrollment')
                ->title('First Enrolment')
                ->name('first_enrolment'),
            Column::make('created_at')
                ->title('Created On')
                ->name('created_on'),
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'CompanyLeaders_' . date('YmdHis');
    }
}
