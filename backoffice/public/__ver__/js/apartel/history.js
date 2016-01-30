$(function() {
    $('#history').DataTable({
        bFilter: true,
        bInfo: true,
        bServerSide: false,
        bProcessing: false,
        bPaginate: true,
        bAutoWidth: false,
        bStateSave: false,
        iDisplayLength: 25,
        sPaginationType: "bootstrap",
        aaSorting: [[0, 'desc']],
        aaData: historyAaData,
        aoColumns:[
            {
                "name": "date",
                "width": "150px"
            }, {
                "name": "user",
                "width": "200px"
            }, {
                "name": "message",
                "sortable": false
            }
        ]
    });
});