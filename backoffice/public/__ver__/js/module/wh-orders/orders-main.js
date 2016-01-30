$(function () {
    var $status = $('#status').selectize({
        create: false,
        plugins: ['remove_button'],
        valueField: 'id',
        labelField: 'name',
        searchField: ['name'],
        sortField: [
            {
                field: 'name'
            }
        ]
    });
    $status[0].selectize.clear();

    var isOneUser = $('#users option').size() == 1 ? true : false;

    var $users = $('#users').selectize({
        create: false,
        plugins: ['remove_button'],
        valueField: 'id',
        labelField: 'name',
        searchField: ['name'],
        sortField: [
            {
                field: 'name'
            }
        ]
    });

    if (!isOneUser) {
        $users[0].selectize.clear();
    }

    statusesShippingSelectize = $('#status_shipping').selectize({
        plugins: ['remove_button'],
        preload: true,
        create: false,
        valueField: 'id',
        labelField: 'title',
        searchField: 'title',
        options: GLOBAL_STATUS_SHIPPING_ORDER,
        render: {
            option: function(option, escape) {
                result = '<div><span>';
                result += escape(option.title);

                result += '</span></div>';

                return result;
            },
            item: function(option, escape) {
                result = '<div><span>' + escape(option.title) + '</span></div>';

                return result;
            }
        },
        onChange: function(value) {
            if (value) {
                // same process
            } else {
                statusesShippingSelectize[0].selectize.clear();
            }
        }
    });
    // set default values
    statusesShippingSelectize[0].selectize.clear();
    $.each(byDefaultStatuses, function(index, value){
        statusesShippingSelectize[0].selectize.addItem(value);
    });

    categoriesSelectize = $('#category').selectize({
        preload: true,
        maxItems: 1,
        create: false,
        valueField: 'id',
        labelField: 'title',
        searchField: 'title',
        options: [],
        sortField: [
            {field: 'type', direction: 'asc'},
            {field: 'title', direction: 'asc'}
        ],
        render: {
            option: function(option, escape) {
                var label;
                switch (escape(option.type)) {
                    case 'Consumable':
                        label = '<span class="label label-success">' + escape(option.type) + '</span>';
                        break;
                    case 'Valuable':
                        label = '<span class="label label-primary">' + escape(option.type) + '</span>';
                        break;

                }

                return '<div>'
                    + label
                    + '<span> ' + escape(option.title) + ' </span>'
                    + '</div>';
            },
            item: function(option, escape) {
                var label;
                switch (escape(option.type)) {
                    case 'Consumable':
                        label = '<span class="label label-success">' + escape(option.type) + '</span>';
                        break;
                    case 'Valuable':
                        label = '<span class="label label-primary">' + escape(option.type) + '</span>';
                        break;

                }

                return '<div>'
                    + label
                    + '<span> ' + escape(option.title) + ' </span>'
                    + '</div>';
            }
        },
        load: function(query, callback) {
            $.ajax({
                url: GLOBAL_GET_CATEGORIES_URL,
                type: 'POST',
                dataType: 'json',
                error: function() {
                    callback();
                },
                success: function(res) {
                    if (res.status == 'error') {
                        notification(res);
                    } else {
                        callback(res);
                    }
                }
            });
        },
        onChange: function(value) {
            if (value) {
                // same process
            } else {
                categoriesSelectize[0].selectize.clear();
            }
        }
    });

    suppluerSelectize = $('#supplier').selectize({
        preload: true,
        maxItems: 1,
        create: false,
        valueField: 'id',
        labelField: 'name',
        searchField: 'name',
        sortField: [
            {field: 'name', direction: 'asc'}
        ],
        render: {
            option: function(option, escape) {
                return '<div>'
                    + '<span> ' + escape(option.name) + ' </span>'
                    + '</div>'
            },
            item: function(option, escape) {
                return '<div>'
                    + '<span> ' + escape(option.name) + ' </span>'
                    + '</div>'
            }
        },
        load: function(query, callback) {
            if (!query.length || query.length < 2) return callback();
            $.ajax({
                url: GLOBAL_GET_SUPPLIERS_URL,
                type: 'POST',
                dataType: 'json',
                data: {
                    query: query
                },
                error: function() {
                    callback();
                },
                success: function(res) {
                    if (res.status == 'error') {
                        notification(res);
                    } else {
                        callback(res);
                    }
                }
            });
        },
        onChange: function(value) {
            if (value) {
                // same process
            } else {
                suppluerSelectize[0].selectize.clear();
            }
        }
    });

    locationTargetSelectize = $('#location').selectize({
        preload: true,
        maxItems: 1,
        create: false,
        valueField: 'id',
        labelField: 'text',
        searchField: 'text',
        sortField: [
            {field: 'label', direction: 'asc'},
            {field: 'text', direction: 'asc'}
        ],
        render: {
            option: function(option, escape) {
                var label;
                switch (escape(option.label)) {
                    case 'apartment':
                        label = '<span class="label label-success">Apartment</span>';
                        break;
                    case 'storage':
                        label = '<span class="label label-primary">Storage</span>';
                        break;
                    case 'office':
                        label = '<span class="label label-info">Office</span>';
                        break;
                }
                return '<div>'
                    + label
                    + '<span> ' + escape(option.text) + ' </span>'
                    + '</div>'
            },
            item: function(option, escape) {
                var label;
                switch (escape(option.label)) {
                    case 'apartment':
                        label = '<span class="label label-success">Apartment</span>';
                        break;
                    case 'storage':
                        label = '<span class="label label-primary">Storage</span>';
                        break;
                    case 'office':
                        label = '<span class="label label-info">Office</span>';
                        break;
                }
                return '<div>'
                    + label
                    + '<span> ' + escape(option.text) + ' </span>'
                    + '</div>'
            }
        },
        load: function(query, callback) {
            if (!query.length || query.length < 2) return callback();
            $.ajax({
                url: GLOBAL_GET_LOCATIONS_URL,
                type: 'POST',
                dataType: 'json',
                data: {
                    query: query
                },
                error: function() {
                    callback();
                },
                success: function(res) {
                    if (res.status == 'error') {
                        notification(res);
                    } else {
                        callback(res);
                    }
                }
            });
        },
        onChange: function(value) {
            if (value) {
                // same process
            } else {
                locationTargetSelectize[0].selectize.clear();
            }
        }
    });

    var reportRangeSpan = $('#reportrange span'), $dateRangePickeroptions = {
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        },
        //startDate: moment().subtract(29, 'days'),
        //endDate: moment(),
        //format: 'YYYY-MM-DD'
        startDate: moment().subtract(29, 'days'),
        endDate: moment(),
        format: 'LL'
    };

    $('#estimated_date_start, #estimated_date_end, #received-date, #order_date').daterangepicker(
        $dateRangePickeroptions,
        function (start, end) {
            reportRangeSpan.html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
        }
    );

    $("#btn_search").click(function () {
        if (window.gTable) {
            gTable.fnDraw();
        } else {
            gTable = $('#datatable_orders').dataTable({
                bAutoWidth: false,
                bFilter: false,
                bInfo: true,
                bPaginate: true,
                bProcessing: true,
                bServerSide: true,
                bStateSave: true,
                iDisplayLength: 25,
                sPaginationType: "bootstrap",
                sAjaxSource: GLOBAL_ORDERS_SEARCH_URL,
                sServerMethod: "POST",
                aaSorting: [[0, 'asc']],
                aoColumns: [
                {
                    name: 'status'
                }, {
                    name: 'shipping'
                }, {
                    name: 'description',
                    sortable: false
                }, {
                    name: 'category',
                    class: 'hidden-sm hidden-xs nowrap'
                }, {
                    name: 'location',
                    class: 'hidden-xs nowrap'
                }, {
                    name: 'date_start',
                    class: 'hidden-sm hidden-xs'
                }, {
                    name: 'date_end',
                    class: 'hidden-sm hidden-xs'
                }, {
                    name: 'url',
                    sortable: false,
                    searchable: false,
                    width: 1
                }, {
                    name: 'edit',
                    sortable: false,
                    searchable: false,
                    width: 1
                }],
                fnServerParams: function (aoData) {
                    jQuery.each($("#order_search_filter").serializeObject(), function (index, val) {
                        var myObject = {
                            name: index,
                            value: val
                        };

                        aoData.push(myObject);
                    });
                },
                drawCallback: function( settings ) {
                    $('[data-toggle="popover"]').popover();
                }
            });

            if ($('#datatable_orders').hasClass('hidden')) {
                $('#datatable_orders').removeClass('hidden');
            }
        }
    });

    $('#order_search_filter').keypress(function (e) {
        if (e.which == 13) {
            $("#btn_search").trigger('click');
        }
    });

    $('#btn_clear_form').click(function (e) {
        e.preventDefault();

        $('#order_search_filter').find('input').each(function (index, item) {
            $(item).val('');
        });

        $('#order_search_filter').find('select').each(function (index, item) {
            $(item).val(
                $(item).find('option:first').val()
            );
        });

        statusesShippingSelectize[0].selectize.clear();
        categoriesSelectize[0].selectize.clear();
        locationTargetSelectize[0].selectize.clear();
        suppluerSelectize[0].selectize.clear();
        $status[0].selectize.clear();
        $users[0].selectize.clear();
    });
})
