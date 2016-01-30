$(document).on('click', "#btn_search_venue", function() {
    $('#datatable_venue_container').removeClass('hidden');
    if (window.gTable) {
        gTable.fnReloadAjax();
    } else {
        gTable = $('#datatable_venue_info').dataTable({
            bAutoWidth: false,
            bFilter: true,
            bInfo: false,
            bPaginate: true,
            bProcessing: true,
            bServerSide: false,
            bStateSave: true,
            iDisplayLength: 25,
            sPaginationType: "bootstrap",
            sAjaxSource: DATATABLE_AJAX_SOURCE,
            sDom: 'l<"enabled">frti<"bottom"p><"clear">',
            aoColumns: [
                {
                    name: "acceptOrders",
                    "sWidth" : "12%",
                    "sClass" : "text-center"
                }, {
                    name: "name"
                }, {
                    name: "city"
                }, {
                    name: "manager"
                }, {
                    name: "cashier"
                }, {
                    "name": "edit",
                    "bSortable": false,
                    "bSearchable": false,
                    "sWidth" : "1%",
                    "sClass" : "text-center"
                }
            ],
            "aoColumnDefs":
                [
                    {
                        "aTargets": [5],
                        "fnCreatedCell": function (nTd, sData, oData, iRow, iCol) {
                            var $cell = $(nTd);
                            var value = $cell.text();
                            if (value !== "0") {
                                if (GLOBAL_IS_VENUE_MANAGER) {
                                    buttonTitle = 'Manage';
                                } else {
                                    buttonTitle = 'View';
                                }
                                $cell.html('<a href="/venue/edit/' + value + '" class="btn btn-xs btn-primary">' + buttonTitle + '</a>');
                            } else {
                                $cell.html('');
                            }
                        }
                    }
                ],
            "fnServerParams": function (aoData) {
                additionalParams = $("#search_venue").serializeObject();
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