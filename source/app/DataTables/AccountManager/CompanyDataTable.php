<?php

namespace App\DataTables\AccountManager;

use App\Models\Company;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class CompanyDataTable extends DataTable
{
    protected bool $fastExcel = true;

    public function fastExcelCallback(): \Closure
    {
        return function (Company $company) {
            return [
                'ID' => $company->id,
                'Name' => $company->name,
                'Email' => $company->email,
                'Address' => $company->address,
                'Number' => $company->number,
                'POC ID' => $company->pocUser?->id ?? '',
                'POC Name' => $company->pocUser?->name ?? '',
                'BM' => $company->bmUser?->name ?? '', // Add BM to the export data
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
            ->editColumn('poc_user', function ($company) {
                if (
                    !empty($company->poc_user_id) &&
                    !empty($company->pocUser)
                ) {
                    return '<a href="' .
                        route(
                            'user_management.users.show',
                            $company->pocUser->id
                        ) .
                        '">' .
                        $company->pocUser->name .
                        '</a>';
                }

                return '';
            })
            ->editColumn('bm', function ($company) {
                if (!empty($company->bm_user_id) && !empty($company->pocUser)) {
                    return '<a href="' .
                        route(
                            'account_manager.leaders.show',
                            $company->bmUser->id
                        ) .
                        '">' .
                        $company->bmUser->name .
                        '</a>';
                }

                return '';
            })
            ->addColumn('action', 'accountmanager/company.action')
            ->rawColumns(['poc_user', 'bm']);
    }

    /**
     * Get query source of dataTable.
     *
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Company $model)
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
            ->setTableId('company-table')
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
            Column::make('name'),
            Column::make('email')->visible(false),
            Column::make('address'),
            Column::make('number')->title('Contact#'),
            Column::make('poc_user')
                ->title('BRM')
                ->searchable(false)
                ->orderable(false),
            Column::make('bm')
                ->title('BM')
                ->searchable(false)
                ->orderable(false), // Add BM column
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'Company_' . date('YmdHis');
    }
}
