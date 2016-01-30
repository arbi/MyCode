$(function () {
    $("#btn_filter_locks").click(function () {
        if (typeof gTable != 'undefined') {
             gTable.fnReloadAjax();
        } else {
            /** Datatable configuration */
            gTable = $('#datatable_locks').dataTable({
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
                        name: "name"
                    }, {
                        name: "description",
                        sortable: false
                    }, {
                        name: "type"
                    }, {
                        name: "apartment",
                        class: "hidden-xs center"
                    }, {
                        name: "building",
                        class: "hidden-xs center"
                    }, {
                        name: "parking",
                        class: "hidden-xs center"
                    }, {
                        name: "edit",
                        class: 'center',
                        sortable: false,
                        searchable: false
                    }
                ],
                "fnServerParams": function (aoData) {
                    additionalParams = $("#search-lock").serializeObject();
                    jQuery.each(additionalParams, function (index, val) {
                        var myObject = {
                            name: index,
                            value: val
                        };

                        aoData.push(myObject);
                    });
                }
            });
            
            if ($('#datatable_locks').hasClass('hidden')) {
                $('#datatable_locks').removeClass('hidden');
            }

            gTable.fnDraw();
        }
    });

    $("#search-lock").keypress(function (e) {
        if (e.which == 13) {
            $("#btn_filter_locks").trigger('click');
            e.preventDefault();
        }
    });

});