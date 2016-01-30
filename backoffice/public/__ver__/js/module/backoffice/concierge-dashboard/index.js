if (jQuery().dataTable) {
    $('#tbl_concierge_dashboard_index').dataTable({
        bAutoWidth: false,
        bFilter: true,
        "oLanguage": {
            "sSearch": "Filter: "
        },
        bInfo: false,
        bLengthChange: false,
        bPaginate: false,
        bProcessing: false,
        bServerSide: false,
        bStateSave: true,
        iDisplayLength: 25,
        aoColumns: [{
            bSortable: true
        }, {
            bSortable: false
        }]
    });
}