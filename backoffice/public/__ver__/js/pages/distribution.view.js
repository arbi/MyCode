$(function() {

    var $apartmentGroupId = $('#apartment_group_id');

    $apartmentGroupId.selectize({
        create: false,
        plugins: ['remove_button'],
        valueField: 'id',
        labelField: 'name',
        searchField: ['name'],
        sortField: [
            {
                field: 'name'
            }
        ]
    });
    $apartmentGroupId[0].selectize.clear();

    $( "#show-group-distribution-view" ).click(function() {
        datatableChange();
    });

    function datatableChange() {
        if (!gTable){
            if ( $('#datatable_apartment_container').hasClass('hidden') ){
                $('#datatable_apartment_container').removeClass('hidden');
            }
            gTable = $('#datatable_apartment').dataTable({
                "bFilter": true,
                "bInfo": true,
                "bServerSide": false,
                "bProcessing": true,
                "bPaginate": true,
                "bAutoWidth": false,
                "bStateSave": true,
                "iDisplayLength": 25,
                "ajax": AJAX_SOURCE_URL,
                "sPaginationType": "bootstrap",
                "aoColumns":GLOBAL_PARTNER_LIST,
                "fnServerParams": function ( aoData ) {
                    var apartelValue = $apartmentGroupId.val();
                    var myObject = {
                        name:  "apartelId",
                        value: apartelValue

                    }
                    aoData.push( myObject );
                }
            });
        } else {
            gTable.api().ajax.reload();
        }
    }
});
