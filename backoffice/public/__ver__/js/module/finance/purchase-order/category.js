$(function() {
    if (jQuery().dataTable) {
        gTable = $('#subcategory-datatable').dataTable({
            bAutoWidth: false,
            bFilter: true,
            bInfo: false,
            bPaginate: true,
            bProcessing: true,
            bServerSide: true,
            bStateSave: true,
            iDisplayLength: 25,
            sPaginationType: "bootstrap",
            sAjaxSource: GLOBAL_GET_SUBCATEGORIES_URI,
            sDom: '',
            aoColumns:[
                {
                    name: "title"
                }, {
                    name: "edit",
                    sortable: false,
                    searchable: false,
                    width : '1'
                }
            ],
            fnServerParams: function (aoData) {
                aoData.push({
                    name:  'category_id',
                    value: $('#category_id').val()
                });
            }
        });

        $('.fn-buttons a').on('click', function(e) {
            e.preventDefault();

            $('.fn-buttons a').removeClass('active');
            $(this).addClass('active');

            $("#switch-subcat-status").attr('value', $(this).attr('data-status-id'));

            gTable.fnSettings().aoServerParams.push({
                "fn": function (aoData) {
                    aoData.push({
                        "name": "status",
                        "value":  $("#switch-subcat-status").attr('value')
                    });
                }
            });

            gTable.fnGetData().length;
            gTable.fnDraw();
        });

        $('#subcategory-datatable').delegate('.subcategory-action', 'click', function (e) {
            e.preventDefault();

            var action = false;

            if ($(this).hasClass('disable-subcategory')) {
                action = 0;
            }

            if ($(this).hasClass('enable-subcategory')) {
                action = 1;
            }

            $.ajax({
                type: "POST",
                url: GLOBAL_ACTION_SUBCATEGORY_URI,
                data: {
                    subcategory_id: this.id,
                    action: action
                },
                dataType: "json",
                cache: false,
                success: function (data) {
                    if (data.status == 'success') {
                        gTable.fnDraw();
                    }

                    notification(data);
                }
            });
        });

        $('#new-subcategory-title').on('keypress' , function (e) {
            if (e.which === 13) {
                e.preventDefault();

                $('#add-new-subcategory').click();
            }
        });

        $('#add-new-subcategory').on('click', function (e) {
            e.preventDefault();

            var btn = $('#add-new-subcategory');
            btn.button('loading');

            if ($('#new-subcategory-title').val() == '') {
                notification({
                    status: "error",
                    msg: "Fill new subcategory title"
                });
            } else {
                $.ajax({
                    type: "POST",
                    url: GLOBAL_ADD_SUBCATEGORY_URI,
                    data: {
                        category_id: $('#category_id').val(),
                        subcategory_title: $('#new-subcategory-title').val()
                    },
                    dataType: "json",
                    cache: false,
                    success: function (data) {
                        if (data.status == 'success') {
                            $('#new-subcategory-title').val('');

                            gTable.fnDraw();
                        }

                        notification(data);
                    }
                });
            }

            btn.button('reset');
        });
    }
});
