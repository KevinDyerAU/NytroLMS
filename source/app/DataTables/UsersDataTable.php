<?php

namespace App\DataTables;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class UsersDataTable extends DataTable
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
            ->addColumn('action', 'users.action');
    }

    /**
     * Get query source of dataTable.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(User $model)
    {
        $roleName = auth()
            ->user()
            ->role()->name;
        $superiorRoles = auth()
            ->user()
            ->getSuperiorRoles();

        $excludedRoles = array_merge($superiorRoles, [
            'Leader',
            'Trainer',
            'Student',
        ]);

        $query = $model
            ->newQuery()
            ->select([
                'users.*',
                'can_edit' => DB::raw("(SELECT CASE WHEN roles.name = '{$roleName}' THEN false ELSE true END as can_edit
                                FROM roles
                                JOIN model_has_roles ON model_has_roles.role_id = roles.id
                                JOIN users as u ON model_has_roles.model_id = u.id
                                WHERE u.id = users.id) as 'can_edit'"),
            ])
            ->with(['detail', 'roles'])
            ->notRole($excludedRoles)
            ->where('users.id', '!=', auth()->user()->id);

        return $query;
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
            ->setTableId('users-table')
            ->addTableClass(['table-responsive', 'display', 'nowrap'])
            ->responsive(false)
            ->columns($this->getColumns())
            ->minifiedAjax()
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
            Column::make('first_name'),
            Column::make('last_name'),
            Column::make('username')->title('User Name'),
            Column::make('email'),
            Column::make('roles[0].name')
                ->name('roles.name')
                ->title('Role'),
            Column::make('detail.last_logged_in')->title('Last Signed In'),
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'Users_' . date('YmdHis');
    }
}
