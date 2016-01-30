$(function() {
    /** Datatable configuration */
    if (jQuery().dataTable) {
        gTable = $('#datatable_office').dataTable({
            bAutoWidth: false,
            bFilter: true,
            bInfo: false,
            bPaginate: true,
            bProcessing: true,
            bServerSide: true,
            bStateSave: true,
            iDisplayLength: 25,
            sPaginationType: "bootstrap",
            sAjaxSource: DATATABLE_AJAX_SOURCE,
            sDom: 'l<"enabled">frti<"bottom"p><"clear">',
            aoColumns:[
                {
                    name: "status",
                    width : '1'
                },
                {
                    name: "name"
                }, {
                    name: "location"
                }, {
                    name: "address"
                }, {
                    name: "phone",
                    sortable: false
                }, {
                    name: "staff",
                    sortable: false,
                    class: "hidden-xs"
                }, {
                    name: "edit",
                    sortable: false,
                    searchable: false,
                    width : '1'
                }
            ]
        });

        $("div.enabled").html($('#status-switch').html());
        $('#status-switch').remove();

        $('.fn-buttons a').on('click', function(e) {
            e.preventDefault();

            $('.fn-buttons a').removeClass('active');
            $(this).addClass('active');

            switch ($(this).attr('data-status')) {
                case 'all':
                    $("#show-status").attr('value', 0); break;
                case 'active':
                    $("#show-status").attr('value', 1); break;
                case 'inactive':
                    $("#show-status").attr('value', 2); break;
            }

            gTable.fnSettings().aoServerParams.push({
                "fn": function (aoData) {
                    aoData.push({
                        "name": "all",
                        "value":  $("#show-status").attr('value')
                    });
                }
            });

            gTable.fnGetData().length;
            gTable.fnDraw();
        });
    }
});

