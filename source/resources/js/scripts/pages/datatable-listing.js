var myDataTable = (function (myDataTable) {
    myDataTable.readyMyDataTable = false;
    let options = {
        tableName: '',
    };
    myDataTable.init = function (overrides = {}) {
        myDataTable.readyMyDataTable = true;
        options.tableName = overrides.tableName || options.tableName;
    };
    myDataTable.setupDefaults = function () {
        return {
            // scrollX: true,
            searchDelay: 600,
            columnDefs: [
                {
                    // For Responsive
                    className: 'control',
                    orderable: false,
                    responsivePriority: 2,
                    targets: 0,
                },
                {
                    // For Checkboxes
                    targets: 1,
                    orderable: false,
                    responsivePriority: 3,
                    render: function (data, type, full, meta) {
                        return (
                            '<div class="form-check"> <input class="form-check-input dt-checkboxes" type="checkbox" value="" id="checkbox' +
                            data +
                            '" /><label class="form-check-label" for="checkbox' +
                            data +
                            '"></label></div>'
                        );
                    },
                    checkboxes: {
                        selectAllRender:
                            '<div class="form-check"> <input class="form-check-input" type="checkbox" value="" id="checkboxSelectAll" /><label class="form-check-label" for="checkboxSelectAll"></label></div>',
                    },
                    visible: false,
                },
                {
                    targets: 2,
                    visible: false,
                },
            ],
            order: [[3, 'asc']],
            dom: '<"card-header border-bottom p-1"<"head-label"><"dt-action-buttons text-end"B>><"d-flex justify-content-between align-items-center mx-0 row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>r><"table-responsive"t><"d-flex justify-content-between mx-0 row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            displayLength: 50,
            lengthMenu: [10, 15, 30, 50, 100],
            buttons: [
                {
                    extend: 'collection',
                    className: 'btn btn-outline-secondary dropdown-toggle me-2',
                    text:
                        feather.icons['share'].toSvg({
                            class: 'font-small-4 me-50',
                        }) + 'Export',
                    buttons: [
                        {
                            extend: 'print',
                            text:
                                feather.icons['printer'].toSvg({
                                    class: 'font-small-4 me-50',
                                }) + 'Print',
                            className: 'dropdown-item',
                            exportOptions: {
                                modifier: { selected: null },
                                columns: ':visible',
                            },
                            customize: function (win) {
                                $(win.document.body).css('font-size', '10pt');

                                $(win.document.body)
                                    .find('table')
                                    .addClass('compact')
                                    .css('font-size', 'inherit');
                            },
                        },
                        {
                            extend: 'csv',
                            text:
                                feather.icons['file-text'].toSvg({
                                    class: 'font-small-4 me-50',
                                }) + 'Csv',
                            className: 'dropdown-item',
                            exportOptions: { columns: ':visible' },
                        },
                        {
                            extend: 'excel',
                            text:
                                feather.icons['file'].toSvg({
                                    class: 'font-small-4 me-50',
                                }) + 'Excel',
                            className: 'dropdown-item',
                            exportOptions: { columns: ':visible' },
                        },
                        {
                            extend: 'pdfHtml5',
                            orientation: 'landscape',
                            text:
                                feather.icons['download'].toSvg({
                                    class: 'font-small-4 me-50',
                                }) + 'Pdf',
                            className: 'dropdown-item',
                            exportOptions: { columns: ':visible' },
                        },
                        // ,{
                        //     extend: 'copy',
                        //     text: feather.icons['copy'].toSvg({ class: 'font-small-4 me-50' }) + 'Copy',
                        //     className: 'dropdown-item',
                        //     exportOptions: { columns: ":visible" }
                        // }
                    ],
                    init: function (api, node, config) {
                        $(node).removeClass('btn-secondary');
                        $(node).parent().removeClass('btn-group');
                        setTimeout(function () {
                            $(node)
                                .closest('.dt-buttons')
                                .removeClass('btn-group')
                                .addClass('d-inline-flex');
                        }, 50);
                    },
                },
            ],
            responsive: {
                details: {
                    display: $.fn.dataTable.Responsive.display.modal({
                        header: function (row) {
                            var data = row.data();
                            return 'Details of ' + data['full_name'];
                        },
                    }),
                    type: 'column',
                    renderer: function (api, rowIdx, columns) {
                        var data = $.map(columns, function (col, i) {
                            return col.title !== '' // ? Do not show row in modal popup if title is blank (for check box)
                                ? '<tr data-dt-row="' +
                                      col.rowIdx +
                                      '" data-dt-column="' +
                                      col.columnIndex +
                                      '">' +
                                      '<td>' +
                                      col.title +
                                      ':' +
                                      '</td> ' +
                                      '<td>' +
                                      col.data +
                                      '</td>' +
                                      '</tr>'
                                : '';
                        }).join('');

                        return data
                            ? $('<table class="table"/>').append(
                                  '<tbody>' + data + '</tbody>'
                              )
                            : false;
                    },
                },
            },
            language: {
                paginate: {
                    // remove previous & next text from pagination
                    previous: '&nbsp;',
                    next: '&nbsp;',
                },
            },
        };
    };
    myDataTable.setupExtras = function (defaults) {
        defaults.buttons = myDataTable.setupAddRecordButton(defaults);
        defaults.columnDefs = myDataTable.setupActionColumnDef(defaults);
        return defaults;
    };
    myDataTable.showPopup = function (target, route) {
        axios
            .get(route)
            .then(response => {
                const res = response.data;
                if (res.status === 'success') {
                    $('#' + target)
                        .find('.modal-body')
                        .html(res.data.rendered);

                    let myModalEl = document.getElementById(target);
                    let myModal = new bootstrap.Modal(myModalEl);
                    myModal.show();

                    //date-picker
                    $('#' + target)
                        .find('.date-picker')
                        .each(function () {
                            let min_date = $(this).data('mindate');
                            let end_date = $(this).data('enddate');

                            // Ensure min_date is not less than end_date
                            if (
                                end_date &&
                                min_date &&
                                new Date(min_date) < new Date(end_date)
                            ) {
                                min_date = end_date;
                            }
                            $(this).flatpickr({
                                dateFormat: 'Y-m-d',
                                minDate: min_date
                                    ? min_date
                                    : new Date().fp_incr(-730),
                            });
                        });
                }
            })
            .catch(error => {
                console.log(error);
            });
    };
    myDataTable.setupActionColumnDef = function (
        defaults,
        routes,
        canEdit = false,
        actionColumn = 0
    ) {
        let dtColumnDefs = $.extend(true, [], defaults.columnDefs);
        const noRoute = 'javascript:void(0);';
        let addActionColumn = {
            // Actions
            targets: actionColumn,
            title: 'Actions',
            orderable: false,
            searchable: false,
            render: function (data, type, full, meta) {
                let route = {
                    popup: '',
                    view: '',
                    edit: '',
                    delete_restore: '',
                    destroy: '',
                };
                delete_restore = { icon: '', text_color: '', title: '' };
                // console.log(full);
                // console.log(routes);
                canEdit = full.can_edit ?? canEdit;
                route.popup = routes.popup
                    ? routes.popup._route
                          .replace(':id', full.id)
                          .replace(':user_id', full.user_id)
                    : noRoute;
                route.view = routes.view
                    ? routes.view
                          .replace(':id', full.id)
                          .replace(':slug', full.slug)
                          .replace(':user_id', full.user_id)
                    : noRoute;
                route.edit = routes.edit
                    ? routes.edit
                          .replace(':id', full.id)
                          .replace(':slug', full.slug)
                          .replace(':user_id', full.user_id)
                    : noRoute;
                route.destroy = routes.destroy
                    ? routes.destroy
                          .replace(':id', full.id)
                          .replace(':user_id', full.user_id)
                    : noRoute;
                if (full.deleted_at === null) {
                    route.delete_restore = routes.delete
                        ? routes.delete
                              .replace(':id', full.id)
                              .replace(':slug', full.slug)
                              .replace(':user_id', full.user_id)
                        : noRoute;
                    delete_restore.icon = 'x-square';
                    delete_restore.text_color = 'text-danger';
                    delete_restore.title = 'Delete';
                } else {
                    route.delete_restore = routes.restore
                        ? routes.delete_restore
                              .replace(':id', full.id)
                              .replace(':slug', full.slug)
                              .replace(':user_id', full.user_id)
                        : noRoute;
                    delete_restore.icon = 'refresh-ccw';
                    delete_restore.text_color = 'text-success';
                    delete_restore.title = 'Restore';
                }
                var hideDeleteRestore = false;
                var hideDestroy = false;
                if (
                    typeof routes.restore === 'undefined' &&
                    typeof routes.delete === 'undefined'
                ) {
                    hideDeleteRestore = true;
                }
                if (typeof routes.destroy === 'undefined') {
                    hideDestroy = true;
                }
                // console.log(canEdit);
                return (
                    (canEdit
                        ? (!hideDeleteRestore
                              ? '<a href="' +
                                route.delete_restore +
                                '" class="item-edit me-1 ' +
                                delete_restore.text_color +
                                '" title="' +
                                delete_restore.title +
                                '">' +
                                feather.icons[delete_restore.icon].toSvg({
                                    class: 'font-small-4',
                                }) +
                                '</a>'
                              : '') +
                          (hideDestroy
                              ? ''
                              : '<a href="' +
                                noRoute +
                                '" class="item-edit me-1 text-danger" title="Remove" onclick="' +
                                route.destroy +
                                '">' +
                                feather.icons['x-square'].toSvg({
                                    class: 'font-small-4',
                                }) +
                                '</a>') +
                          '<a href="' +
                          route.edit +
                          '" class="item-edit me-1 text-secondary" title="Edit">' +
                          feather.icons['edit'].toSvg({
                              class: 'font-small-4',
                          }) +
                          '</a>'
                        : '') +
                    (routes.view
                        ? '<a href="' +
                          route.view +
                          '" class="item-view me-1 text-primary" title="View Details">' +
                          feather.icons['file-text'].toSvg({
                              class: 'font-small-4',
                          }) +
                          '</a>'
                        : '') +
                    (routes.popup
                        ? '<a onclick="myDataTable.showPopup(\'' +
                          routes.popup.target +
                          "','" +
                          route.popup +
                          '\')" ' +
                          'href="' +
                          noRoute +
                          '" class="item-view me-1 text-primary" title="Show Details ' +
                          full.id +
                          '">' +
                          feather.icons['eye'].toSvg({
                              class: 'font-small-4',
                          }) +
                          '</a>'
                        : '')
                );
            },
        };
        dtColumnDefs.push(addActionColumn);
        return dtColumnDefs;
    };
    myDataTable.setupAddRecordButton = function (defaults) {
        let dtDefaults = $.extend(true, [], defaults.buttons);
        // console.log('default buttons', dtDefaults);
        let addRecordButton = {
            text:
                feather.icons['plus'].toSvg({ class: 'me-50 font-small-4' }) +
                'Add New Record',
            className: 'create-new btn btn-primary',
            action: function (e, dt, node, config) {
                window.location = window.location.href + '/create';
                // myDataTable.showCreateRecordModal();
            },
            init: function (api, node, config) {
                // $(node).attr('href', 'put/your/href/here')
                $(node).removeClass('btn-secondary');
            },
        };
        dtDefaults.push(addRecordButton);
        return dtDefaults;
    };
    myDataTable.showCreateRecordModal = function (modal = 'modals-slide-in') {
        let bModal = new bootstrap.Modal(document.getElementById(modal));
        bModal.show();
    };
    myDataTable.initDefaults = function (options) {
        // console.log('final defaults', options);
        $.extend(true, $.fn.dataTable.defaults, options);
        $('.head-label').html(
            '<h6 class="mb-0">' + options.tableName + '</h6>'
        );
    };
    myDataTable.refresh = function (dtTable) {
        dtTable.draw();
    };
    myDataTable.filterColumn = function (dtTable, i, val) {
        // console.log(dtTable, i, val);
        $('#' + dtTable)
            .DataTable()
            .column(i)
            .search(val, false, true)
            .draw();
    };
    myDataTable.search = function (dtTable, input) {
        console.log(dtTable, input);
        $('#' + dtTable)
            .DataTable()
            .search(input, false, true)
            .draw();
    };
    myDataTable.newExportAction = function (e, dt, button, config) {
        var self = this;
        var oldStart = dt.settings()[0]._iDisplayStart;
        dt.one('preXhr', function (e, s, data) {
            // Just this once, load all data from the server...
            data.start = 0;
            data.length = 2147483647;
            dt.one('preDraw', function (e, settings) {
                // Call the original action function
                if (button[0].className.indexOf('buttons-copy') >= 0) {
                    $.fn.dataTable.ext.buttons.copyHtml5.action.call(
                        self,
                        e,
                        dt,
                        button,
                        config
                    );
                } else if (button[0].className.indexOf('buttons-excel') >= 0) {
                    $.fn.dataTable.ext.buttons.excelHtml5.available(dt, config)
                        ? $.fn.dataTable.ext.buttons.excelHtml5.action.call(
                              self,
                              e,
                              dt,
                              button,
                              config
                          )
                        : $.fn.dataTable.ext.buttons.excelFlash.action.call(
                              self,
                              e,
                              dt,
                              button,
                              config
                          );
                } else if (button[0].className.indexOf('buttons-csv') >= 0) {
                    $.fn.dataTable.ext.buttons.csvHtml5.available(dt, config)
                        ? $.fn.dataTable.ext.buttons.csvHtml5.action.call(
                              self,
                              e,
                              dt,
                              button,
                              config
                          )
                        : $.fn.dataTable.ext.buttons.csvFlash.action.call(
                              self,
                              e,
                              dt,
                              button,
                              config
                          );
                } else if (button[0].className.indexOf('buttons-pdf') >= 0) {
                    $.fn.dataTable.ext.buttons.pdfHtml5.available(dt, config)
                        ? $.fn.dataTable.ext.buttons.pdfHtml5.action.call(
                              self,
                              e,
                              dt,
                              button,
                              config
                          )
                        : $.fn.dataTable.ext.buttons.pdfFlash.action.call(
                              self,
                              e,
                              dt,
                              button,
                              config
                          );
                } else if (button[0].className.indexOf('buttons-print') >= 0) {
                    $.fn.dataTable.ext.buttons.print.action(
                        e,
                        dt,
                        button,
                        config
                    );
                }
                dt.one('preXhr', function (e, s, data) {
                    // DataTables thinks the first item displayed is index 0, but we're not drawing that.
                    // Set the property to what it was before exporting.
                    settings._iDisplayStart = oldStart;
                    data.start = oldStart;
                });
                // Reload the grid with the original page. Otherwise, API functions like table.cell(this) don't work properly.
                setTimeout(dt.ajax.reload, 0);
                // Prevent rendering of the full data to the DOM
                return false;
            });
        });
        // Requery the server with the new one-time export settings
        dt.ajax.reload();
    };
    return myDataTable;
})(myDataTable || {});

$(document).ready(function () {
    myDataTable.init();
});
// At the end of the file or after defining myDataTable
window.myDataTable = myDataTable;
