$(function () {

    // $('#datatable_products_wrapper').hide();
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
                $dateRangePickeroptions,
                function (start, end) {
                    $('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
                }
        );
    }

    $("#btn_filter_products").click(function () {

        $('#datatable_apartments').show();

        if (window.gTable) {
            gTable.fnReloadAjax();
        } else {
           /** Datatable configuration */
            gTable = $('#datatable_apartments').dataTable({
                bAutoWidth: false,
                bFilter: true,
                bInfo: true,
                bPaginate: true,
                bProcessing: true,
                bServerSide: false,
                bStateSave: true,
                iDisplayLength: 25,
                sPaginationType: "bootstrap",
                sAjaxSource: '/apartments/get-apartment-search-json',
                aaSorting: [[1, "asc"], [0, "asc"]],
                aoColumns: [
                    {
                        name: "status",
                        width: "27"
                    }, {
                        name: "name",
                        width: "220"
                    }, {
                        name: "city",
                        width: "170"
                    }, {
                        name: "building",
                        width: "",
                        sClass: "hidden-xs"
                    }, {
                        name: "created_date",
                        class: "nowrap hidden-xs",
                        width: "1"
                    }, {
                        name: "navigation",
                        width: "315",
                        sortable: false,
                        searchable: false
                    }, {
                        name: "links",
                        width: "50",
                        sortable: false,
                        searchable: false
                    }
                ],
                "fnServerParams": function (aoData) {
                    additionalParams = $("#search-product").serializeObject();
                    jQuery.each(additionalParams, function (index, val) {
                        var myObject = {
                            name: index,
                            value: val
                        };

                        aoData.push(myObject);
                    });
                }
            });
        }
    });

    $("#search-product").keypress(function (e) {
        if (e.which == 13) {
            $("#btn_filter_products").trigger('click');
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

});
$(document).on('click','[name="web"][disabled]', function(event) {
    event.preventDefault();
});
