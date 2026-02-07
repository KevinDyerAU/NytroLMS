<?php

namespace App\DataTables;

use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class RolesDataTable extends DataTable
{
    protected bool $fastExcel = false;

    public function fastExcelCallback(): \Closure
    {
        return function ($row) {
            return [
                'ID' => $row['id'],
                'Role Title' => $row['name'],
                'Created At' => $row['created_at'],
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
            ->addColumn('action', function ($row) {
                $actions = '';

                // View action
                if (auth()->user()->can('view', $row)) {
                    $actions .= '<a href="' . route('user_management.roles.show', $row->id) . '" class="btn btn-sm btn-outline-primary me-1" title="View">
                        <i data-lucide="eye"></i>
                    </a>';
                }

                // Edit action
                if (auth()->user()->can('update', $row)) {
                    $actions .= '<a href="' . route('user_management.roles.edit', $row->id) . '" class="btn btn-sm btn-outline-warning me-1" title="Edit">
                        <i data-lucide="edit"></i>
                    </a>';
                }

                // Clone action - only for Root users
                if (auth()->user()->hasRole('Root')) {
                    $actions .= '<a href="' . route('user_management.roles.clone', $row->id) . '" class="btn btn-sm btn-outline-info me-1" title="Clone">
                        <i data-lucide="copy"></i>
                    </a>';
                }

                return $actions;
            });
    }

    /**
     * Get query source of dataTable.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Role $model)
    {
        $role_id = auth()
            ->user()
            ->roles()
            ->first()->id;

        return $model
            ->newQuery()
            ->select([
                'roles.*',
                'can_edit' => DB::raw(
                    'CASE WHEN roles.id = ' .
                    $role_id .
                    ' THEN false WHEN roles.id < ' .
                    $role_id .
                    ' THEN false ELSE true END as can_edit'
                ),
            ])
            ->where('name', '!=', 'Root');
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
            ->setTableId('roles-table')
            ->addTableClass(['table-responsive', 'display'])
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
            Column::make('name')->title('Title'),
            Column::make('created_at')->title('Created At'),
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'Roles_' . date('YmdHis');
    }
}
