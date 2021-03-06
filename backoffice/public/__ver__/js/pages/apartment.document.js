$(function() {
    /** Datatable configuration */
    if (jQuery().dataTable) {
        gTable = $('#documents').dataTable({
            bFilter: true,
            bInfo: true,
            bServerSide: false,
            bProcessing: false,
            bPaginate: true,
            bAutoWidth: false,
            bStateSave: true,
            iDisplayLength: 25,
            sAjaxSource: null,
            sPaginationType: "bootstrap",
            aoColumns:[
                {
                    name: "type"
                }, {
                    name: "date",
                    width: "90",
                    class: "hidden-xs"
                }, {
                    name: "security_level",
                    width: 105,
                    class: "hidden-xs"
                }, {
                    name: "description",
                    sortable: false,
                    width: '50%',
                    class: "hidden-xs"
                }, {
                    name: "download",
                    sortable: false,
                    width: 1
                }, {
                    name: "attachment",
                    sortable: false,
                    width: 1
                }, {
                    name: "edit",
                    sortable: false,
                    width: 1
                }
            ],
            aaData: aaData
        });
    }

    if (jQuery().dataTable) {
        gTable = $('#datatable-contacts').dataTable({
            bFilter: true,
            bInfo: true,
            bServerSide: false,
            bProcessing: false,
            bPaginate: true,
            bAutoWidth: false,
            bStateSave: true,
            iDisplayLength: 25,
            sAjaxSource: null,
            sPaginationType: "bootstrap",
            aoColumns:[
                {
                    name: "name"
                }, {
                    name: "company",
                    class: "hidden-xs"
                }, {
                    name: "mobile",
                    sortable: false,
                    class: "hidden-xs"
                }, {
                    name: "email",
                    class: "hidden-xs"
                },  {
                    name: "view",
                    sortable: false,
                    width: 1
                }
            ],
            aaData: contactsData
        });
    }
});
