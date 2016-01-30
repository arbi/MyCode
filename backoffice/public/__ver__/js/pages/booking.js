$(function () {
    if (jQuery().daterangepicker) {
        var reportRangeSpan = $('#reportrange span'),
                $dateRangePickeroptions = {
                    ranges: {
                        'Today': [moment(), moment()],
                        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                        'This Month': [moment().startOf('month'), moment().endOf('month')],
                        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                    },
                    startDate: moment().subtract(29, 'days'),
                    endDate: moment(),
                    format: 'YYYY-MM-DD'
                };

        $('#booking_date').daterangepicker(
                $dateRangePickeroptions,
                function (start, end) {
                    reportRangeSpan.html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
                }
        );

        $('#arrival_date').daterangepicker(
                $dateRangePickeroptions,
                function (start, end) {
                    reportRangeSpan.html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
                }
        );

        $('#departure_date').daterangepicker(
                $dateRangePickeroptions,
                function (start, end) {
                    reportRangeSpan.html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
                }
        );
    }

    $("#btn_search_booking").click(function () {
        if (window.gTable) {
            gTable.fnDraw();
        } else {
            gTable = $('#datatable_reservations').dataTable({
                bAutoWidth: false,
                bFilter: false,
                bInfo: true,
                bPaginate: true,
                bProcessing: true,
                bServerSide: true,
                bStateSave: true,
                iDisplayLength: 25,
                sPaginationType: "bootstrap",
                sAjaxSource: '/booking/get-reservations-json',
                aaSorting: [[2, 'desc']],
                aoColumns: [{
                        name: 'res_number',
                        sortable: false,
                        width: '9%',
                        class: 'hidden-xs'
                    }, {
                        name: 'status',
                        width: 1,
                        sortable: false,
                        class: 'hidden-xs hidden-sm'
                    }, {
                        name: 'date',
                        width: '88',
                        class: 'nowrap hidden-sm hidden-xs'
                    }, {
                        name: 'apartment',
                        class: 'hidden-xs'
                    }, {
                        name: 'guest',
                        sortable: false
                    }, {
                        name: 'from',
                        width: '88',
                        class: 'nowrap'
                    }, {
                        name: 'to',
                        width: '88',
                        class: 'nowrap'
                    }, {
                        name: 'rate',
                        class: 'hidden-xs hidden-sm',
                        width: '8%'
                    }, {
                        name: 'charged',
                        sortable: false,
                        width: '105',
                        class: 'hidden-xs hidden-sm'
                    }, {
                        name: 'guest_balance',
                        sortable: true,
                        width: '105',
                        class: 'hidden-xs'
                    }, {
                        name: 'view',
                        sortable: false,
                        searchable: false,
                        width: '1'
                    }],
                aoColumnDefs: [{
                        aTargets: [0],
                        fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {
                            var $cell = $(nTd),
                                value = $cell.text(),
                                value_parts = value.split(" "),
                                firstFieldHtml = value_parts[0],
                                needBr = '<br>',
                                row = '';

                            if (value_parts[2] == 1) {
	                            row = firstFieldHtml + needBr + '<span class="label label-info">GIN</span>';
                            } else {
	                            row = firstFieldHtml + needBr +'<span class="label label-warning">AFF</span>';
                            }

	                        if (value_parts[1] == 1) {
		                        row += '<span class="lock-sign-custom glyphicon glyphicon-lock"></span>';
	                        }

	                        $cell.html(row);
                        }
                    }, {
                        "aTargets": [10],
                        "fnCreatedCell": function (nTd, sData, oData, iRow, iCol) {
                            var $cell = $(nTd),
                                    value = $cell.text();

                            $cell.html('<a href="/booking/edit/' + value + '" target="_blank" class="btn btn-xs btn-primary pull-left" data-html-content="View"></a>');
                        }
                    }],
                fnServerParams: function (aoData) {
                    jQuery.each($("#search-reservation").serializeObject(), function (index, val) {
                        var myObject = {
                            name: index,
                            value: val
                        };

                        aoData.push(myObject);
                    });
                }
            });

            if ($('#booking_table_container').hasClass('hidden')) {
                $('#booking_table_container').removeClass('hidden');
            }
        }
    });

    // Search on page load
    if (parseInt($('#booking_table_container').attr('data-search-onload'))) {
        $('#btn_search_booking').trigger('click');
    }

    // Search on [Enter]
    $('#search-reservation').keypress(function (e) {
        if (e.which == 13) {
            $("#btn_search_booking").trigger('click');
        }
    });

    $("#btn_download_filtered_csv").click(function () {
        var btn = $(this);
        btn.button('loading');
        $.get(
            GLOBAL_AJAX_DOWNLOAD_CSV + '?' + $("#search-reservation").serialize(),
            function (data, status) {
                if (data.status == 'error') {
                    notification(data);
                    btn.button('reset');
                } else {
                    window.location = '/booking/download-csv?' + $("#search-reservation").serialize();
                    btn.button('reset');
                }
            }
        );
    });

    $('.filter-reset').click(function (e) {
        e.preventDefault();

        $('#search-reservation').find('input').each(function (index, item) {
            $(item).val('');
        });

        $('#search-reservation').find('select').each(function (index, item) {
            $(item).val(
                    $(item).find('option:first').val()
                    );
        });

        $('#status').val(1);
    });

    $('#search-reservation').change(function (e) {
        updatePageUrl(e);
    });

    $('.daterangepicker').click(function (e) {
        updatePageUrl(e);
    });

    $('.ui-autocomplete').click(function (e) {
        updatePageUrl(e);
    });
});

function updatePageUrl(e) {
    e.preventDefault();

    var url = $("#search-reservation").serialize();

    setNewUrl(url);
}

$(".apt-filter-btn").click(function () {
    var prod = $(this).parent().prev(),
            prod_id = $(this).parent().parent().next(),
            prod_type = prod_id.next(),
            span = $(this).children("span.hide");

    prod_id.val(0);
    prod.val('');

    $(this).children("span").toggleClass("hide");

    if (span.hasClass("glyphicon-filter")) {
        prod_type.attr("value", 0);
    } else {
        prod_type.attr("value", 1);
    }
});

$("#product").keyup(function () {
    if ($(this).val().length >= 3) {
        $("#product").autocomplete({
            source: function (request, response) {
                $.ajax({
                    url: FIND_PRODUCT_BY_ADDRESS_AUTOCOMPLETE_URL,
                    data: {
                        txt: $("#product").val(),
                        mode: $("#product_type").val()
                    },
                    dataType: "json",
                    type: "POST",
                    success: function (data) {
                        var obj = [];

                        if (data && data.rc == '00') {
                            for (var row in data.result) {
                                var item = data.result[row],
                                        new_obj = {};

                                new_obj.value = item.name;
                                new_obj.id = item.id;
                                obj.push(new_obj);
                            }
                        }

                        response(obj);
                    }
                })
            },
            max: 10,
            minLength: 1,
            autoFocus: true,
            select: function (event, ui) {
                if (ui.item)
                    $('#product_id').val(ui.item.id);
            },
            search: function (event, ui) {
                $('#product_id').val('');
            },
            focus: function (event, ui) {
                event.preventDefault();
            }
        });
    }
});

$("#assigned_product").keyup(function () {
    if ($(this).val().length >= 3) {
        $("#assigned_product").autocomplete({
            source: function (request, response) {
                $.ajax({
                    url: FIND_PRODUCT_BY_ADDRESS_AUTOCOMPLETE_URL,
                    data: {
                        txt: $("#assigned_product").val(),
                        mode: $("#assigned_product_type").val()
                    },
                    dataType: "json",
                    type: "POST",
                    success: function (data) {
                        var obj = [];

                        if (data && data.rc == '00') {
                            for (var row in data.result) {
                                var item = data.result[row],
                                        new_obj = {};

                                new_obj.value = item.name;
                                new_obj.id = item.id;
                                obj.push(new_obj);
                            }
                        }

                        response(obj);
                    }
                })
            },
            max: 10,
            minLength: 1,
            autoFocus: true,
            select: function (event, ui) {
                if (ui.item) {
                    $('#assigned_product_id').val(ui.item.id);
                }
            },
            search: function (event, ui) {
                $('#assigned_product_id').val('');
            },
            focus: function (event, ui) {
                event.preventDefault();
            }
        });
    }
});

function updateTable() {
    if (!($('#datatable_reservations').hasClass('initialized'))) {
        $('#datatable_reservations').addClass('initialized');

        if ($('#booking_table_container').hasClass('hidden')) {
            $('#booking_table_container').removeClass('hidden');
        }

        initBookingTable();
    } else {
        if (window.gTable) {
            gTable.fnDraw();
        }
    }
}

$("#group").keyup(function () {
    if ($(this).val().length >= 2) {

        $.widget("custom.catcomplete", $.ui.autocomplete, {
            _renderMenu: function (ul, items) {
                var that = this,
                        currentCategory = "";
                $.each(items, function (index, item) {
                    if (item.category != currentCategory) {
                        ul.append("<li class='ui-autocomplete-category'>" + item.category + "</li>");
                        currentCategory = item.category;
                    }
                    that._renderItemData(ul, item);
                });
            }
        });

        $("#group").catcomplete({
            source: function (request, response) {
                $.ajax({
                    url: BUILDING_SEARCH,
                    data: {query: $("#group").val()},
                    dataType: "json",
                    type: "POST",
                    success: function (data) {
                        var obj = [];
                        if (data && data.status == 'success') {
                            for (var row in data.result) {
                                var item = data.result[row];
                                var new_obj = {};
                                new_obj.value = item.name;
                                new_obj.id = item.id;
                                new_obj.category = item.category;
                                obj.push(new_obj);
                            }
                        }
                        response(obj);
                    }
                })
            },
            max: 10,
            minLength: 1,
            autoFocus: true,
            select: function (event, ui) {
                if (ui.item)
                    $('#group_id').val(ui.item.id);
            },
            search: function (event, ui) {
            },
            focus: function (event, ui) {
                event.preventDefault();
            }
        });
    }
});
