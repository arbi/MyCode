$(function(){
    if (jQuery().dataTable) {
        gTable = $('#costs-datatable').dataTable({
            bFilter: true,
            bInfo: true,
            bServerSide: true,
            bProcessing: true,
            bPaginate: true,
            bAutoWidth: true,
            bStateSave: true,
            iDisplayLength: 25,
            sAjaxSource: GLOBAL_GET_OFFICE_COSTS_URL,
            sPaginationType: "bootstrap",
            aaSorting: [[2, 'desc']],
            aoColumns:[
                {
                    name: "id",
                    width: 105
                }, {
                    name: "category",
                    width: "15%"
                }, {
                    name: "date",
                    width: 90
                }, {
                    name: "currency",
                    width: 10,
                }, {
                    name: "amount",
                    width: "100",
                    class: "text-right"
                }, {
                    name: "purpose",
                    sortable: false,
                    class: "hidden-xs"
                }, {
                    name: "actions",
                    sortable: false,
                    width: "4%"
                }
            ],
        });
    }

    $("#btn_download_filtered_csv").click(function() {
            var filter = $('#costs-datatable_filter').find('input[type=search]').val();

            window.location = GLOBAL_DOWNLOAD_OFFICE_COSTS_CSV_URL + '?filter=' + filter;
        }
    );
});
