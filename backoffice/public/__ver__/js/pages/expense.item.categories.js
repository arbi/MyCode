$(function(){
    if (jQuery().dataTable) {
        gTable = $('#datatable_category').dataTable({
            bAutoWidth: false,
            bFilter: true,
            bInfo: true,
            bPaginate: true,
            bProcessing: true,
            bServerSide: true,
            bStateSave: true,
            iDisplayLength: 25,
            sPaginationType: "bootstrap",
            sAjaxSource: "/finance/expense-item-categories/ajax-category-list",
            sDom: 'l<"enabled">frti<"bottom"p><"clear">',
            aaSorting: [[ 1, "asc" ]],
            aoColumns:[{
                name: "status",
                width: "1%"
            }, {
                name: "name",
                width: "20%"
            }, {
                name: "description",
                width: "78%",
                class: "hidden-xs"
            }, {
                name: "actions",
                sortable: false,
                searchable: false,
                width : "1%"
            }]
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
