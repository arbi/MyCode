// Extend datatables filtering to consider active/voided/all toggle
$.fn.dataTable.ext.search.push(
    function( settings, data, dataIndex ) {
        var activeStatus = $('.status-switch .active').attr('data-status');
        var dataStatus = data[0];
        return (
            (activeStatus == undefined && dataStatus == 'active')
            || activeStatus == 'all'
            || dataStatus == activeStatus
        );
    }
);

var tTable;
$(function () {
    var $datatableTransactions = $('#datatable-transactions');
    tTable = $datatableTransactions.dataTable({
        bAutoWidth: false,
        bFilter: true,
        bInfo: true,
        bPaginate: true,
        bProcessing: false,
        bServerSide: false,
        bStateSave: false,
        iDisplayLength: 25,
        sPaginationType: "bootstrap",
        sDom: 'l<"status-switch">frti<"bottom"p><"clear">',
        aaData: transactionsTableData,
        aoColumns: [
            {
                name: "void_status",
                visible: false
            },
            {
                name: "checkbox",
                visible: hasPoAndTransferManagerGlobalRole,
                class: "text-center",
                sortable: false,
                width: "30"
            }, {
                name: "id",
                width: "50"
            }, {
                name: "date",
                width: "100"
            }, {
                name: "description"
            }, {
                name: "credits",
                class: "text-right",
                width: "140"
            }, {
                name: "debits",
                class: "text-right",
                width: "150"
            }, {
                name: "verify",
                visible: managePermission,
                sortable: false,
                searchable: false,
                width: '1'
            }, {
                name: "void",
                visible: managePermission,
                sortable: false,
                searchable: false,
                width: '1'
            }, {
                name: "view",
                visible: managePermission,
                sortable: false,
                searchable: false,
                width: '1'
            }
        ],
        aaSorting: [[2, 'desc']]
    });

    $("div.status-switch").html($('#status-switch-template').html());
    $('#status-switch-template').remove();

    $('.fn-buttons a').on('click', function(e) {
        e.preventDefault();

        $('.fn-buttons a').toggleClass('active', false);
        $(this).toggleClass('active', true);

        tTable.fnDraw();
    });

    var $combineTransactionsButton = $('#combine-transactions');
    if ($combineTransactionsButton.length) {
        $('.combine-money-transactions').change(function () {
            var checkedCheckboxesCount = $('input.combine-money-transactions:checked').length;
            if (checkedCheckboxesCount > 1) {
                $combineTransactionsButton.removeClass('disabled');
            } else {
                $combineTransactionsButton.addClass('disabled');
            }
        });

        $combineTransactionsButton.click(function (event) {
            event.preventDefault();

            if ($(this).hasClass('disabled')) {
                return false;
            }

            var moneyTransactionIds = [];
            $('input.combine-money-transactions:checked').each(function () {
                var moneyTransactionId = $(this).attr('data-id');
                moneyTransactionIds.push(moneyTransactionId);
            });

            $.ajax({
                url: '/finance/money-account/combine-transactions',
                type: 'POST',
                data: {money_transaction_ids: moneyTransactionIds},
                error: function () {
                    notification({
                        status: 'error',
                        msg: 'ERROR! Something went wrong'
                    });
                },
                success: function (data) {
                    if (data.status == 'success') {
                        location.reload();
                    } else {
                        notification(data);
                    }
                }
            });

        });
    }

    $datatableTransactions.on('click', '.btn-toggle-verify', function (e) {
        e.preventDefault();

        var $btn = $(this);
        var $id = $btn.attr('data-id');
        var $status = $btn.attr('data-status');
        $btn.button('loading');
        $.ajax({
            type: "POST",
            url: GLOBAL_CHANGE_VERIFY_STATUS,
            data: {
                id: $id,
                status: $status
            },
            dataType: "json",
            success: function (data) {
                $btn.button('reset');
                if (data.status == 'success') {
                    if (data.verify_status == GLOBAL_IS_VERIFIED) {
                        // Wait just a little for button('reset') to finish
                        setTimeout(function() {
                            $btn
                                .toggleClass('btn-success', false)
                                .toggleClass('btn-danger', true)
                                .text('Unverify')
                                .attr('data-status', GLOBAL_IS_NOT_VERIFIED)
                                .closest('tr')
                                .toggleClass('success', true);
                        }, 1);

                    } else {
                        // Wait just a little for button('reset') to finish
                        setTimeout(function() {
                            $btn
                                .toggleClass('btn-success', true)
                                .toggleClass('btn-danger', false)
                                .text('Verify')
                                .attr('data-status', GLOBAL_IS_VERIFIED)
                                .closest('tr')
                                    .toggleClass('success', false);
                        }, 1);
                    }
                }

                notification(data);
            }
        });
    });

    $datatableTransactions.on('click', '.btn-void', function (e) {
        e.preventDefault();
        $('#void-transaction-id').text($(this).attr('data-id'));
        $('#transaction-void-modal').modal('show');
    });

    $('#btn-void-confirm').click(function() {
        var $btn = $(this);
        var $id = $('#void-transaction-id').text();
        $btn.button('loading');
        $.ajax({
            type: "POST",
            url: GLOBAL_VOID_URL,
            data: {
                id: $id
            },
            dataType: "json",
            success: function (data) {
                $btn.button('reset');
                if (data.status == 'success') {
                    var $originBtn = $('#btn-void-' + $id);
                    $originBtn
                        .closest('tr')
                            .toggleClass('row-faded', true);
                    $originBtn.remove();
                }

                $('#transaction-void-modal').modal('hide');
                notification(data);
            },
            error: function(data) {
                $btn.button('reset');
                $('#transaction-void-modal').modal('hide');
                notification({
                    status: 'error',
                    msg: 'Something went wrong. Please try again later or submit a bug with lampuchka.'
                });
            }
        });
    });

});