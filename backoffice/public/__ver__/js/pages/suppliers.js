var gTable;
$(function(){
    /** Datatable configuration */
    if (jQuery().dataTable) {
        gTable = $('#datatable_suppliers_info').dataTable({
            bAutoWidth: false,
            bFilter: true,
            bInfo: true,
            bPaginate: true,
            bProcessing: true,
            bServerSide: true,
            bStateSave: true,
            iDisplayLength: 25,
            sPaginationType: "bootstrap",
            sAjaxSource: "/finance/suppliers/ajax-suppliers-list",
            sDom: 'l<"enabled">frti<"bottom"p><"clear">',
            aaSorting: [[ 1, "asc" ]],
            aoColumns:[
                {
                    name: "status",
                    width: "50"
                }, {
                    name: "name"
                }, {
                    name: "description",
                    class: "hidden-xs",
                    sortable: false
                }, {
                    name: "actions",
                    sortable: false,
                    searchable: false,
                    width: '1'
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

            gTable.fnDraw();
        });
    }
});
