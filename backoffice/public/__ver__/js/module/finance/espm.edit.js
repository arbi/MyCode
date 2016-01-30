$(function() {
    $('#espm-form').validate({
        ignore: [],
        rules: {

            amount: {
                required: true,
                number: true,
                min: 0
            },
            currency: {
                required: true,
                number: true,
                min: 1
            },
            transaction_account: {
                required: true,
                number: true,
                min: 1
            },
            type: {
                required: true,
                number: true,
                min: 1
            },
            status: {
                required: true,
                number: true,
                min: 1
            },
            action_date: {
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
        errorPlacement: function(error, element) { }
    });

    $('#action_date').daterangepicker({
        'singleDatePicker': true,
        'format': globalDateFormat
    });
    var $account = $('#account');
    $account.selectize({
        create: false,
        plugins: ['remove_button'],
        valueField: 'value',
        labelField: 'text',
        searchField: ['text'],
        sortField: [
            {
                field: 'text'
            }
        ]
    });

    var $transactionAccount = $('#transaction_account');
    $transactionAccount.selectize({
        valueField: 'unique_id',
        labelField: 'name',
        searchField: ['name'],
        render: {
            option: function(item, escape) {
                // Account type definition: 3 - affiliate, 4 - supplier, 5 - people
                var label = (item.type == 3 ? 'primary' : (item.type == 4 ? 'warning' : 'success'));

                return '<div><span class="label label-' + label + '">' + escape(item.label) + '</span> ' + escape(item.name) + '</div>';
            },
            item: function(item, escape) {
                return '<div data-name="' + escape(item.name) + '" data-type="' + escape(item.type) + '" data-id="' + escape(item.account_id) + '"><span class="label label-primary">' + escape(item.label) + '</span> ' + escape(item.name) + '</div>';
            }
        },
        load: function(query, callback) {
            if (query.length < 2) {
                return callback();
            }

            $.ajax({
                url: SUPPLIER_URL,
                type: 'POST',
                data: {'q': encodeURIComponent(query)},
                error: function() {
                    callback();
                },
                success: function(res) {
                    callback(res.data);
                }
            });
        },
        onItemAdd: function (value, $item) {
            $.getJSON(GET_SUPPLIER_ACCOUNT + '/' + value, function(data) {
                if (data.length) {
                    for (var i in data) {
                        var item = data[i];
                        $account[0].selectize.addOption({
                            text: item.name,
                            value: item.id
                        });
                    }
                    var accountSelectedId = $account.attr('data-id');
                    if (accountSelectedId) {
                        $account[0].selectize.addItem(accountSelectedId, true);
                    }
                }
            });
        },
        onItemRemove: function (value) {
            $account[0].selectize.clearOptions();
        },

    persist: false,
        hideSelected: true,
        highlight: false
    });

    var $type = $('#type');
    $type.selectize({
        create: false,
        plugins: ['remove_button'],
        valueField: 'value',
        labelField: 'text',
        searchField: ['text'],
        sortField: [
            {
                field: 'text'
            }
        ]
    });

    var $status = $('#status');
    $status.selectize({
        create: false,
        plugins: ['remove_button'],
        valueField: 'value',
        labelField: 'text',
        searchField: ['text'],
        sortField: [
            {
                field: 'text'
            }
        ]
    });

    var $currency = $('#currency');
    $currency.selectize({
        create: false,
        plugins: ['remove_button'],
        valueField: 'value',
        labelField: 'text',
        searchField: ['text'],
        sortField: [
            {
                field: 'text'
            }
        ]
    });

    if (isEditMode) {
        var uniqueId = $transactionAccount.attr('data-unique-id'),
            accountId = $transactionAccount.attr('data-account-id'),
            accountName = $transactionAccount.attr('data-account-name'),
            accountType = $transactionAccount.attr('data-account-type'),
            label = accountType == 3 ? 'Partner' : (accountType == 5 ? 'People' : 'External');

        $transactionAccount[0].selectize.addOption({
            unique_id: uniqueId,
            account_id: accountId,
            type: accountType,
            name: accountName,
            label: label
        });
        $transactionAccount[0].selectize.setValue(uniqueId);



        if (DISABLE_ALL_FORM == 'yes') {
            $("#espm-form :input").attr("disabled", true);
            $transactionAccount[0].selectize.disable();
            $type[0].selectize.disable();
            $status[0].selectize.disable();
            $account[0].selectize.disable();
        }

        if (ENABLE_STATUS == 'yes') {
            $status[0].selectize.enable();
            $('.save-button').attr("disabled", false);
        }
    } else {
        $currency[0].selectize.clear();
        $type[0].selectize.clear();
        $account[0].selectize.clear();
    }

    if (HISTORY_DATA.length > 0) {
        $('#history_clean').hide();

        $('#datatable_history').DataTable({
            bFilter: true,
            bInfo: true,
            bServerSide: false,
            bProcessing: false,
            bPaginate: true,
            bAutoWidth: false,
            bStateSave: false,
            iDisplayLength: 10,
            sAjaxSource: false,
            sPaginationType: "bootstrap",
            aaSorting: [[0, 'desc']],
            aaData: HISTORY_DATA,
            sDom: 'l<"enabled">frti<"bottom"p><"clear">',
            aoColumns: [
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
    } else {
        $('#datatable_history').hide();
        $('#history_clean').show();
    }

});
