var gTable;
$(function(){
    /** Datatable configuration */
    if (jQuery().dataTable) {
        gTable = $('#datatable_legal_entities_info').dataTable({
            bAutoWidth: false,
            bFilter: true,
            bInfo: true,
            bPaginate: true,
            bProcessing: true,
            bServerSide: true,
            bStateSave: true,
            iDisplayLength: 25,
            sPaginationType: "bootstrap",
            sAjaxSource: "/finance/legal-entities/ajax-legal-entities-list",
            sDom: 'l<"enabled">frti<"bottom"p><"clear">',
            aaSorting: [[ 1, "asc" ]],
            aoColumns:[
                {
                    name: "status",
                    width: "50"
                }, {
                    name: "name"
                }, {
                    name: "country"
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
            ],
            "fnDrawCallback": function () {
               if($('#datatable_legal_entities_info tbody > tr > td').text() == ' '){
                   $('#datatable_legal_entities_info tbody tr').remove();
                   $('#datatable_legal_entities_info tbody').append('<tr role="row" class="odd" ><td colspan="4">There are no Legal Entities to display</td></tr>')
               }
            }
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
