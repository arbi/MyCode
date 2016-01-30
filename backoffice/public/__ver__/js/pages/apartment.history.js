$(function() {
    /** Datatable configuration */
    if (jQuery().dataTable) {
        gTable = $('#history').dataTable({
            bFilter: true,
            bInfo: true,
            bServerSide: false,
            bProcessing: false,
            bPaginate: true,
            bAutoWidth: false,
            iDisplayLength: 25,
            sAjaxSource: null,
            sPaginationType: "bootstrap",
            aoColumns:[
                {
                    name: "date",
                    width: "150px"
                }, {
                    name: "user",
                    width: "200px"
                }, {
                    name: "category"
                }, {
                    name: "action",
                    sortable: false
                }
            ],
            aaSorting: [[0, 'desc']],
            aaData: aaData
        });
    }
});
