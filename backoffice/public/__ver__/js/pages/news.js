$(function(){
    /** Datatable configuration */
    if (jQuery().dataTable) {
        gTable = $('#datatable_news').dataTable({
            bFilter: true,
            bInfo: true,
            bServerSide: false,
            bProcessing: false,
            bPaginate: true,
            bAutoWidth: true,
            bStateSave: true,
            iDisplayLength: 25,
            sAjaxSource: AJAX_SOURCE_URL,
            sPaginationType: "bootstrap",
            aaSorting: [[0, "desc"]],
            aoColumns:[
                {
                    name: "date",
                    width: "10%"
                }, {
                    name: "title",
                    width: "75%"
                }, {
                    name: "web",
                    sortable: false,
                    searchable: false,
                    width: "1%"
                }, {
                    name: "buttons",
                    sortable: false,
                    searchable: false,
                    width: "1%"
                }
            ]
        });
    }
});