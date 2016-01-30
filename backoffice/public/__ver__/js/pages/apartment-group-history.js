/** Datatable configuration */
if (jQuery().dataTable) {
    if ($('#historyDatatable_info').length) {
        $('#historyDatatable_info').dataTable({
            bAutoWidth: false,
            bFilter: true,
            bInfo: false,
            bPaginate: true,
            bProcessing: false,
            bServerSide: false,
            bStateSave: true,
            iDisplayLength: 25,
            sPaginationType: "bootstrap",
            aaSorting: [[0, "desc"]],
            aoColumns: [
                {
                    name: "date",
                    width: "150px"
                }, {
                    name: "user",
                    width: "200px"
                }, {
                    name: "action"
                }, {
                    name: "message",
                    bSortable: false
                }
            ],
            aaData: historyAaData
        });
    }
}