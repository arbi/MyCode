$(function () {
    var $entityId = $('#entity_id');
    $entityId.selectize({
        valueField: 'id',
        labelField: 'text',
        searchField: ['text'],
        multiple: false,
        sortField: [
            {
                field: ['type']
            }
        ],
        render: {
            option: function(option, escape) {
                var label;
                switch (parseInt(escape(option.type))) {
                    case ENTITY_TYPE_APARTMENT:
                        label = '<span class="label label-success">Apartment</span>';
                        break;
                    case ENTITY_TYPE_APARTMENT_GROUP:
                        label = '<span class="label label-info">Building</span>';
                        break;
                }
                return '<div>'
                    + label
                    + '<span> ' + escape(option.text) + ' </span>'
                    + '</div>'
            },
            item: function(option, escape) {
                var label;
                switch (parseInt(escape(option.type))) {
                    case ENTITY_TYPE_APARTMENT:
                        label = '<span class="label label-success">A</span>';
                        break;
                    case ENTITY_TYPE_APARTMENT_GROUP:
                        label = '<span class="label label-info">B</span>';
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
                url: GET_ENTITY_LIST_URL,
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
                        $('#entity_id')[0].selectize.refreshOptions();
                    }
                }
            });
        },
        onChange: function(value) {
            if (value) {
                $('#entity_type').val($entityId[0].selectize.sifter.items[value].type);
            }
        }
    });

    $("#btn_filter_documents").click(function () {
        if (window.gTable) {
            gTable.fnDraw();
        } else {
            /** Datatable configuration */
            gTable = $('#datatable_documents').dataTable({
                bAutoWidth: false,
                bFilter: true,
                bInfo: true,
                bPaginate: true,
                bProcessing: true,
                bServerSide: false,
                bStateSave: true,
                iDisplayLength: 25,
                sPaginationType: "bootstrap",
                sAjaxSource: SEARCH_URL,
                aaSorting: [[0, "asc"], [2, "asc"]],
                aoColumns: [
                    {
                        name: "apartment",
                        width: "14%"
                    }, {
                        name: "security",
                        width: 80
                    }, {
                        name: "type",
                        width: 80
                    }, {
                        name: "supplier",
                        width: 80,
                    }, {
                        name: "description",
                        width: "24%",
                        class: "hidden-xs"
                    }, {
                        name: "created_date",
                        class: "nowrap hidden-xs",
                        width: "1%"
                    }, {
                        name: "download",
                        width: "1%",
                        sortable: false,
                        searchable: false
                    }, {
                        name: "view",
                        width: "1%",
                        sortable: false,
                        searchable: false
                    }, {
                        name: "etid",
                        width: "1%",
                        sortable: false,
                        searchable: false
                    }
                ],
                "fnServerParams": function (aoData) {
                    additionalParams = $("#search-document").serializeObject();
                    jQuery.each(additionalParams, function (index, val) {
                        var myObject = {
                            name: index,
                            value: val
                        };

                        aoData.push(myObject);
                    });
                }
            });
            
            if ($('#datatable_documents').hasClass('hidden')) {
                $('#datatable_documents').removeClass('hidden');
            }
        }
    });

    if (jQuery().daterangepicker) {

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

        $('#createdDate').daterangepicker(
                $dateRangePickeroptions
        );



        $dateRangePickeroptions2 = {
            ranges: {
                'Last Year': [moment().subtract(1, 'years'), moment().subtract(1, 'days')],
                'Last 9 Months': [moment().subtract(9, 'months'), moment()],
                'Last 6 Months': [moment().subtract(6, 'months'), moment()],
                'Last 3 Months': [moment().subtract(3, 'months'), moment()],
                'From 1 Year before To 1 Year Later': [moment().subtract(1, 'years'), moment().add(1, 'years')],
                'From 9 Months before To 9 Months Later': [moment().subtract(9, 'months'), moment().add(9, 'months')],
                'From 6 Months before To 6 Months Later': [moment().subtract(6, 'months'), moment().add(6, 'months')],
                'From 3 Months before To 3 Months Later': [moment().subtract(3, 'months'), moment().add(3, 'months')]
            },
            startDate: moment().subtract(6, 'months'),
            endDate: moment(),
            format: 'YYYY-MM-DD'
        };

        $('#validation-range').daterangepicker(
            $dateRangePickeroptions2
        );

    }

    $("#btn_filter_documents").click(function () {
        if (window.gTable) {
            gTable.fnReloadAjax();
        }
    });

    $("#search-document").keypress(function (e) {
        if (e.which == 13) {
            $("#btn_filter_documents").trigger('click');
            e.preventDefault();
        }
    });

    $("#building").keyup(function () {
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

            $("#building").catcomplete({
                source: function (request, response) {
                    $.ajax({
                        url: BUILDING_SEARCH,
                        data: {query: $("#building").val()},
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
                        $('#building_id').val(ui.item.id);
                },
                search: function (event, ui) {
                },
                focus: function (event, ui) {
                    event.preventDefault();
                }
            });
        }

        if (!$('#building').val().length) {
            $('#building_id').attr('value', 0);
        }
    });

    $('#supplier').keyup(function () {
        if ($('#supplier').val().length >= 2) {

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

            $('#supplier').catcomplete({
                source: function (request, response) {
                    $.ajax({
                        url: SUPPLIER_SEARCH,
                        data: {query: $('#supplier').val()},
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
                        $('#supplier_id').val(ui.item.id);
                },
                search: function (event, ui) {
                },
                focus: function (event, ui) {
                    event.preventDefault();
                }
            });
        }

        if (!$('#supplier').val().length) {
            $('#supplier_id').attr('value', 0);
        }
    });

    $('#author').keyup(function () {
        if ($('#author').val().length >= 2) {

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

            $('#author').catcomplete({
                source: function (request, response) {
                    $.ajax({
                        url: AUTHOR_SEARCH,
                        data: {query: $('#author').val()},
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
                        $('#author_id').val(ui.item.id);
                },
                search: function (event, ui) {
                },
                focus: function (event, ui) {
                    event.preventDefault();
                }
            });
        }

        if (!$('#author').val().length) {
            $('#author_id').attr('value', 0);
        }
    });

    $("#btn_download_filtered_csv").click(function() {
        window.location = DOWNLOAD_CSV + '?' + $("#search-document").serialize();
    });

});