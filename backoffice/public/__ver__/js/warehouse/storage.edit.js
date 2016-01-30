$(function() {
    $('#storage-form').validate({
        rules: {
            name: {
                required: true
            },
            city: {
                required: true,
                number: true,
                min: 1
            },
            address: {
                required: true
            }
        },
        highlight: function (element, errorClass, validClass) {
            $(element).closest('.form-group').removeClass('has-success').addClass('has-error');
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).closest('.form-group').removeClass('has-error').addClass('has-success');
        },
        success: function (label) {
            $(label).closest('form').find('.valid').removeClass("invalid");
        },
        errorPlacement: function(error, element) {
        }
    });

    /** Datatable configuration */
    if (jQuery().dataTable) {
        gTable = $('#threshold-table').dataTable({
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
                    name: "category",
                    width: "40%"
                }, {
                    name: "threshold",
                    width: "40%"
                }, {
                    name: "actions",
                    sortable: false,
                    searchable: false,
                    width: "10%"
                }
            ]
        });
    }

    $('#add_threshold').click(function(e) {
        var categoryId = $('#category_list').val();
        var threshold = $('#threshold').val();
        var storageId = $('#storageId').val();
        if (categoryId > 0 && threshold > 0 && storageId > 0) {
            $(this).closest('tr').removeClass('has-error').addClass('has-success');
            var btn = $(this);
            btn.button('loading');
            $.ajax({
                type: "POST",
                url: GLOBAL_ADD_THREASHOLD,
                data: {
                    category_id:categoryId,
                    threshold:threshold,
                    storage_id:storageId
                },
                dataType: "json",
                success: function(data) {
                    if(data.status == 'success'){
                        location.reload();
                    } else {
                        notification(data);
                        btn.button('reset');
                    }
                }
            });
        } else {
            $(this).closest('tr').removeClass('has-success').addClass('has-error');
        }
    });

    $(".deleteThreshold").click(function() {
        $('#removeThresholdModal').modal();
        var url = $(this).attr('data-url');
        $('#deleteThresholdProcess').prop('href', url);
    });

    var $categoryList = $('#category_list');

    if($categoryList.length) {
        $categoryList.selectize({
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
        $categoryList[0].selectize.clear();
    }

    // Enter
    $('#storage-form').keypress(function (e) {
        if (e.which == 13) {
            $('#add_threshold').trigger('click');
        }
    });
});