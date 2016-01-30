$(function(){
    /** Datatable configuration */
    if (jQuery().dataTable) {
        gTable = $('#storage-table').dataTable({
            bAutoWidth: false,
            bFilter: true,
            bInfo: true,
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
                    sortable: false,
                    searchable: false,
                    width: "1%",
                    class: "text-center"
                },{
                    name: "name"
                }, {
                    name: "city",
                    width: "40%"
                }, {
                    name: "address",
                    width: "25%"
                }, {
                    name: "actions",
                    sortable: false,
                    searchable: false,
                    width: "1%"
                }
            ]
        });

        $("div.enabled").html($('#status-switch').html());
        $('#status-switch').remove();

        $('.fn-buttons a').on('click', function(e) {
            e.preventDefault();

            var sentValue = null;

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