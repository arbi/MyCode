/************TAB*******************/
var hash = window.location.hash;
hash && $('ul.nav a[href="' + hash + '"]').tab('show');

$('.nav-tabs a').click(function (e) {
    window.location.hash = this.hash;
});

$(function() {
    /** Datatable configuration */
    if (jQuery().dataTable) {
        $('#history-datatable').dataTable({
            bFilter: true,
            bInfo: true,
            bServerSide: false,
            bProcessing: false,
            bPaginate: true,
            bStateSave: true,
            bAutoWidth: false,
            iDisplayLength: 25,
            sPaginationType: "bootstrap",
            aaData: dataTableData,
            aoColumns: [
                {
                    name: "date",
                    bSortable: true,
                    width: "150px"
                }, {
                    name: "user",
                    bSortable: true,
                    width: "200px"
                }, {
                    name: "message",
                    bSortable: false
                }
            ],
            aaSorting: [[0, 'desc']]
        });
    }
});