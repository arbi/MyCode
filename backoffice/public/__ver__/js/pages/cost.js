$("#btn_download_filtered_csv").click(function() {
        window.location = GLOBAL_DOWNLOAD_CSV;
    }
);

$(function(){

    /** Datatable configuration */
    if (jQuery().dataTable) {
        gTable = $('#costs').dataTable({
            bFilter: true,
            bInfo: true,
            bServerSide: true,
            bProcessing: true,
            bPaginate: true,
            bAutoWidth: false,
            bStateSave: true,
            iDisplayLength: 25,
            sAjaxSource: GLOBAL_GET_COSTS_URL,
            sServerMethod: "POST",
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
                    width: "100"
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
});
