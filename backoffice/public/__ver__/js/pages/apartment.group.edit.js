$(function() {
    /** Datatable configuration */
    if (jQuery().dataTable) {
        $('#table_apartment_groups').dataTable({
            "bAutoWidth": false,
            "bFilter": true,
            "oLanguage": {
                "sSearch": "Filter: "
            },
            "bInfo": false,
            "bLengthChange": false,
            "bPaginate": false,
            "bProcessing": false,
            "bServerSide": false,
            "bStateSave": true,
            "iDisplayLength": 20,
            "sPaginationType": "bootstrap",
            "aoColumns":[{
                "bSortable": true
            }, {
                "bSortable": true,
                'sWidth' : '54'
            }, {
                "bSortable": true
            }, {
                "bSortable": true,
                'sWidth' : '45'
            }, {
                "bSortable": true,
                'sWidth' : '82'
            }, {
                "bSortable": true,
                'sWidth' : '32'
            }, {
                "bSortable": true,
                'sWidth' : '70'
            }, {
                "bSortable": true,
                'sWidth' : '62'
            }, {
                "bSortable": true,
                'sWidth' : '105'
            },{
                "bSortable": false,
                'sWidth' : '1'
            }]
        });
    }
});