$(function(){
    /** Datatable configuration */
    if (jQuery().dataTable) {
        gTable = $('#apartment-review-category-table').dataTable({
            bFilter: true,
            bInfo: true,
            bServerSide: false,
            bProcessing: false,
            bPaginate: true,
            bAutoWidth: true,
            bStateSave: true,
            iDisplayLength: 25,
            aaData: aaData,
            sPaginationType: "bootstrap",
            aoColumns:[
                {
                    name: "name"
                }, {
                    name: "type"
                }, {
                    name: "actions",
                    sortable: false,
                    searchable: false,
                    width: "1%"
                }
            ]
        });
    }
});