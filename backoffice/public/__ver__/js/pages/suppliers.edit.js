$(function(){
    $('#supplier-form').submit(function(event){
        event.preventDefault();
        if($(this).valid()){
            var name = $('#name').val();
            var description = $('#description').val();
            saveSupplier({name:name,description:description,id:GENERAL_SUPPLIER_ID});
        }
    });
    $('#create-new-active').click(function(event){
        event.preventDefault();
        var name = $('#name').val();
        var description = $('#description').val();
        saveSupplier({name:name,description:description,id:GENERAL_SUPPLIER_ID, ignoreDuplication:1});
    });
});

// add external account
$(document).on('click', '#add-account, .supplier-account-edit', function(e){
    var container = $('#edit-account');
    var tab       = $('#edit-account-tab');

    var data;
    if ($(e.target).hasClass('supplier-account-edit')) {
        $('#edit-account-tab').text('Edit Account');
        data = {
            'id': $(e.target).data('id'),
            'supplier_id' : GENERAL_SUPPLIER_ID
        };
    } else {
        $('#edit-account-tab').text('Add Account');
        data = {'supplier_id' : $(this).data('supplier-id')};
    }

    $.ajax({
        type: "POST",
        url: GENERAL_EXTERNAL_ACCOUNT_EDIT,
        data: data,
        dataType: "html",
        success: function (data) {
            container.html(data);
            tab.parent().css('display', 'block');
            tab.trigger('click');
            initUserAccountValidation();
        }
    });
});

// save supplier account
$(document).on('click', '#save-supplier-account', function(e) {
    $(this).button('loading');

    if($('#supplier-account-form').valid()) {
        var data = $('#supplier-account-form').serializeArray();
        data.push(
            {
                name: 'supplierId',
                value: $(this).data('supplier-id')
            }
        );
        saveSupplierAccountData(data);
    } else {
        $(this).button('reset');
    }
});

// archive supplier account
$(document).on('click', '.supplier-account-archive', function() {
    var btn = $(this);
    var id  = btn.data('id');
    var supplierId;

    if ($('#add-account').data('supplier-id') == undefined) {
        supplierId = $('#save-supplier-account').data('supplier-id');
    } else {
        supplierId = $('#add-account').data('supplier-id');
    }

    btn.button('loading');

    $.ajax({
        type: "POST",
        url: DATATABLE_SUPPLIER_ACCOUNTS_ARCHIVE,
        data: {
            'id': id,
            'supplierId': supplierId
        },
        dataType: "json",
        success: function (data) {
            if(data.url != '' && data.status == 'success'){
                window.location.href = data.url;
                location.reload();
            } else {
                notification(data);
            }
            btn.button('reset');
        }
    });
});

function saveSupplier(data)
{
    $.ajax({
        type: "POST",
        url: GENERAL_SAVE_PATH,
        data: data,
        dataType: "json",
        success: function(data) {
            if (data.status == 'success') {
                if (GENERAL_SUPPLIER_ID == 0) {
                    window.location.href = GENERAL_RETURN_PATH;
                }
                else {
                    notification(data);
                }
            } else if (data.status == 'error'){
                notification(data);
            }
            else if (data.status == 'warning') {
                $('#deactivate-old-container').html(data.activationUrl);
                $('#activate-or-create-modal').modal('show');
            }
        }
    });
}

$('#datatable_supplier_account_container').removeClass('hidden');
if (window.fTable) {
    fTable.fnReloadAjax();
} else {
    fTable = $('#datatable_supplier_account_info').dataTable({
        bAutoWidth: false,
        bFilter: true,
        bInfo: false,
        bPaginate: true,
        bProcessing: true,
        bServerSide: true,
        bStateSave: true,
        iDisplayLength: 25,
        sPaginationType: "bootstrap",
        sAjaxSource: DATATABLE_SUPPLIER_ACCOUNTS_AJAX_SOURCE,
        sDom: 'l<"enabled">frti<"bottom"p><"clear">',
        aoColumns: [
            {
                name: "isDefault",
                "sClass" : "text-center",
                "bSortable": true
            }, {
                name: "name",
                "bSortable": true
            }, {
                name: "type",
                "bSortable": true
            }, {
                name: "fullLegalName",
                "bSortable": true
            }, {
                name: "addresses",
                "bSortable": false
            }, {
                name: "countryId",
                "bSortable": false
            }, {
                name: "iban",
                "bSortable": true
            }, {
                name: "swft",
                "bSortable": true
            }, {
                "name": "edit",
                "bSortable": false,
                "bSearchable": false,
                "sClass" : "text-center",
                "sWidth" : "15%"
            }
        ],
        "aoColumnDefs": [
            {
                "aTargets": [8],
                "fnCreatedCell": function (nTd, sData, oData, iRow, iCol) {
                    var $cell = $(nTd);
                    var value = $cell.text();
                    if (value !== "0") {
                        $cell.html('<a href="javascript:void(0)" class="btn btn-xs btn-primary supplier-account-edit" data-id="' + value + '">Manage</a><a href="javascript:void(0)" class="btn btn-xs btn-danger supplier-account-archive" data-id="' + value + '">Archive</a>');
                    } else {
                        $cell.html('');
                    }
                }
            }
        ]
    });

    $("#datatable_supplier_account_info_wrapper div.enabled").html($('#status-switch-account').html());
    $('#status-switch-account').remove();

    $(document).on('click', '#datatable_supplier_account_info_wrapper .fn-buttons a', function(e) {
        e.preventDefault();

        $('.fn-buttons a').removeClass('active');
        $(this).addClass('active');

        switch ($(this).attr('data-status')) {
            case 'all':
                $("#show_status_account").attr('value', 1); break;
            case 'archived':
                $("#show_status_account").attr('value', 2); break;
        }

        fTable.fnSettings().aoServerParams.push({
            "fn": function (aoData) {
                aoData.push({
                    "name": "all",
                    "value":  $("#show_status_account").attr('value')
                });
            }
        });

        fTable.fnGetData().length;
        fTable.fnDraw();
    });
}

/**
 * Save supplier account data
 *
 * @param data
 */
function saveSupplierAccountData(data) {
    $.ajax({
        type: "POST",
        url: GLOBAL_SUPPLIER_ACCOUNT_SAVE,
        data: data,
        dataType: "json",
        success: function(data) {
            if(data.url != '' && data.status == 'success'){
                window.location.href = data.url;
                location.reload();
            } else {
                notification(data);
            }

            $("#supplier-account-form #save-supplier-account").button('reset');
        }
    });
}
