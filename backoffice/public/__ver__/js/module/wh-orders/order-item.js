$(function() {
    var $locationTarget = $('#location_target');
    var isEditMode = parseInt($('#order_id').val()) > 0;
    var form = $('#order_form');
    var isRequiredTeam = isEditMode;
    var numberValueTeam = isRequiredTeam ? 1 : 0;

    if (form.length > 0) {
        form.validate({
            ignore: '',
            rules: {
                'title': {
                    required: true
                },
                'price': {
                    required: true,
                    number: true,
                    min: 1
                },
                'currency': {
                    required: true,
                    number: true,
                    min: 1
                },
                'asset_category_id': {
                    required: true
                },
                'location_target': {
                    required: true
                },
                'status_shipping': {
                    required: isEditMode
                },
                'quantity': {
                    required: true,
                    number: true
                },
                'quantity_type': {
                    required: true,
                    number: true
                },
                'team_id': {
                    required: isRequiredTeam,
                    number: true,
                    min: numberValueTeam
                },
                tracking_url: {
                    required: false,
                    url: true
                },
                url: {
                    required: false,
                    url: true
                },
                'manual_po_id': {
                    required: false,
                    number: true,
                    min: 1
                },
                supplier_id: {
                    required: function() {
                        // if the status was or is changed to Ordered make the supplier required
                        return ($('#status_shipping').val() === '2');
                    }
                }
            },
            highlight: function (element, errorClass, validClass) {
                $(element).closest('.controls').removeClass('has-success').addClass('has-error');
            },
            unhighlight: function (element, errorClass, validClass) {
                $(element).closest('.controls').removeClass('has-error').addClass('has-success');
            },
            success: function (label) {
                $(label).closest('form').find('.valid').removeClass("invalid");
            },
            errorPlacement: function (error, element) {}
        });
    }

    if (IS_REJECTED > 0) {
        $('input').attr('disabled',true);
        $('select').attr('disabled',true);
        $('textarea').attr('disabled',true);
    }

    $('#received-date').datetimepicker({
        format: 'M j, Y H:i',
        step: 15
    });

    if (isEditMode) {
        $('.status-shipping').toggleClass('soft-hide', false);
    } else {
        $('.status-shipping').toggleClass('soft-hide', true);
    }

    var statusesSelectize = $('#status_shipping').selectize({
        preload: true,
        maxItems: 1,
        create: false,
        valueField: 'id',
        labelField: 'title',
        searchField: 'title',
        options: GLOBAL_STATUS_SHIPPING_ORDER,
        render: {
            option: function(option, escape) {
                var result = '<div><span>';

                if (escape(option.irreversibility) == 'true') {
                    result += '<strong>' + escape(option.title) + '</strong>';
                } else {
                    result += escape(option.title);
                }

                result += '</span></div>';

                return result;
            },
            item: function(option, escape) {
                return '<div><span>' + escape(option.title) + '</span></div>';
            }
        },
        onChange: function(value) {
            if (value) {
                var statusCurrentValue      = $('#status_shipping').attr('data-id'),
                    statusNewValue          = statusesSelectize[0].selectize.getValue(),
                    irreversiblyStatuses    = [STATUS_CANCELED, STATUS_RECEIVED];

                if (statusNewValue == STATUS_RECEIVED ||
                    statusNewValue == STATUS_PARTIALLY_RECEIVED ||
                    statusNewValue == STATUS_ISSUE ||
                    statusNewValue == STATUS_RETURNED ||
                    statusNewValue == STATUS_REFUNDED
                ) {
                    $('.received-info').toggleClass('hide', false);
                } else {
                    $('.received-info').toggleClass('hide', true);
                }

                if (statusCurrentValue > 0 && statusCurrentValue != statusNewValue) {
                    if ($.inArray(parseInt(statusNewValue), irreversiblyStatuses) != -1) {
                        $('#change-status-to').text(GLOBAL_ORDER_STATUSES[statusNewValue]);
                        $('#irreversible-status').text(GLOBAL_ORDER_STATUSES[alert[alert.length - 1]]);
                        $('#alertStatusModal').modal('show');
                    }

                    if (parseInt(statusNewValue) == STATUS_REFUNDED) {
                        $('#refundModal').modal('show');
                        getItemDetails();
                    }
                }

                if (statusNewValue == STATUS_TO_BE_ORDERED || (statusCurrentValue == STATUS_TO_BE_ORDERED && statusNewValue == STATUS_CANCELED)) {
                    $('.order-date').toggleClass('soft-hide', true);
                    $( "#order_date" ).rules( "remove" );
                    $( "#estimated_delivery_date_range" ).rules( "remove" );
                } else {
                    $('.order-date').toggleClass('soft-hide', false);
                    $('#order_date').rules('add', {
                        required: true
                    });
                    $('#estimated_delivery_date_range').rules('add', {
                        required: true
                    });
                }
            } else {
                statusesSelectize[0].selectize.clear();
            }
        }
    });

    if ($('#status_shipping').attr('data-id') !== '') {
        statusesSelectize[0].selectize.addItem($('#status_shipping').attr('data-id'), true);

	    if ($('#status_shipping').val() == STATUS_TO_BE_ORDERED) {
		    $('.order-date').toggleClass('soft-hide', true);
		    $( "#order_date" ).rules( "remove" );
		    $( "#estimated_delivery_date_range" ).rules( "remove" );
	    } else {
		    $('.order-date').toggleClass('soft-hide', false);
		    $('#order_date').rules('add', {
			    required: true
		    });
		    $('#estimated_delivery_date_range').rules('add', {
			    required: true
		    });
	    }
    }

    var categoriesSelectize = $('#asset_category_id').selectize({
        preload: true,
        maxItems: 1,
        create: false,
        valueField: 'id',
        labelField: 'title',
        searchField: 'title',
        options: [],
        sortField: [
            {field: 'type', direction: 'asc'},
            {field: 'title', direction: 'asc'}
        ],
        render: {
            option: function(option, escape) {
                var label;

                switch (escape(option.type)) {
                    case 'Consumable':
                        label = '<span class="label label-success">' + escape(option.type) + '</span>';
                        break;
                    case 'Valuable':
                        label = '<span class="label label-primary">' + escape(option.type) + '</span>';
                        break;
                }

                return '<div>'
                    + label
                    + '<span> ' + escape(option.title) + ' </span>'
                    + '</div>';
            },
            item: function(option, escape) {
                var label;

                switch (escape(option.type)) {
                    case 'Consumable':
                        label = '<span class="label label-success">' + escape(option.type) + '</span>';
                        break;
                    case 'Valuable':
                        label = '<span class="label label-primary">' + escape(option.type) + '</span>';
                        break;
                }

                return '<div>'
                    + label
                    + '<span> ' + escape(option.title) + ' </span>'
                    + '</div>';
            }
        },
        load: function(query, callback) {
	        $.ajax({
                url: GLOBAL_GET_CATEGORIES_URL,
                type: 'POST',
                dataType: 'json',
                error: function() {
                    callback();
                },
                success: function(res) {
                    if (res.status == 'error') {
                        notification(res);
                    } else {
                        callback(res);
                        if (query == '') {
                            if ($('#asset_category_id').attr('data-id') !== '') {
                                categoriesSelectize[0].selectize.addItem($('#asset_category_id').attr('data-id'), true);
                            }
                        }

                        checkLocationAvailability(true);

	                    if ($('.get-approval').hasClass('hide')) {
		                    if ($('#order_form').valid()) {
			                    $('.get-approval').removeClass('hide');
		                    }

		                    $('.has-error').removeClass('has-error');
		                    $('.has-success').removeClass('has-success');
	                    }
                    }
                }
            });
        },
        onChange: function(value) {
            var $locationTarget = $('#location_target');
            var selectedLocation = $locationTarget[0].selectize.getValue();

            $locationTarget[0].selectize.clearOptions();

            if (value.length) {
                $locationTarget[0].selectize.load(function (callback) {
                    $.ajax({
                        url: GLOBAL_GET_LOCATIONS_URL,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            query: '',
                            category_id: $('#asset_category_id').val()
                        },
                        error: function() {
                            callback();
                        },
                        success: function (res) {
                            if (res.status == 'error') {
                                notification(res);
                            } else {
                                callback(res);
                            }

                            if ($locationTarget[0].selectize.getOption(selectedLocation).length) {
                                $locationTarget[0].selectize.setValue(selectedLocation);
                            }
                        }
                    });
                });
            }

            checkLocationAvailability(false);
        }
    });

    $locationTarget.selectize({
        preload: true,
        maxItems: 1,
        create: false,
        valueField: 'id',
        labelField: 'text',
        searchField: 'text',
        sortField: [
            {field: 'label', direction: 'asc'},
            {field: 'text', direction: 'asc'}
        ],
        render: {
            option: function(option, escape) {
                var label;
                switch (escape(option.label)) {
                    case 'apartment':
                        label = '<span class="label label-success">Apartment</span>';
                        break;
                    case 'storage':
                        label = '<span class="label label-primary">Storage</span>';
                        break;
                    case 'office':
                        label = '<span class="label label-info">Office</span>';
                        break;
                    case 'building':
                        label = '<span class="label label-warning">Building</span>';
                        break;
                }
                return '<div>'
                    + label
                    + '<span> ' + escape(option.text) + ' </span>'
                    + '<small class="text-muted">' + escape(option.info) + '</small>'
                    + '</div>'
            },
            item: function(option, escape) {
                var label;
                switch (escape(option.label)) {
                    case 'apartment':
                        label = '<span class="label label-success">Apartment</span>';
                        break;
                    case 'storage':
                        label = '<span class="label label-primary">Storage</span>';
                        break;
                    case 'office':
                        label = '<span class="label label-info">Office</span>';
                        break;
                    case 'building':
                        label = '<span class="label label-warning">Building</span>';
                        break;
                }
                return '<div>'
                    + label
                    + '<span> ' + escape(option.text) + ' </span>'
                    + '<small class="text-muted">' + escape(option.info) + '</small>'
                    + '</div>'
            }
        }
    });

    if ($locationTarget.attr('data-item') !== '' && $locationTarget.attr('data-id') !== '') {
        item = JSON.parse($locationTarget.attr('data-item'));

        $locationTarget[0].selectize.addOption(item);
        $locationTarget[0].selectize.addItem($locationTarget.attr('data-id'), true);
    }

    function checkLocationAvailability(check) {
	    if (check && isEditMode) {
            return;
        }

        var categoryItems = categoriesSelectize[0].selectize.items;

        if (categoryItems.length > 0) {
	        $locationTarget[0].selectize.enable();
        } else {
	        $locationTarget[0].selectize.clear();
	        $locationTarget[0].selectize.disable();
        }
    }

    var supplierSelectize = $('#supplier_id').selectize({
        preload: true,
        maxItems: 1,
        create: false,
        valueField: 'id',
        labelField: 'name',
        searchField: 'name',
        sortField: [
            {field: 'name', direction: 'asc'}
        ],
        render: {
            option: function(option, escape) {
                return '<div>'
                    + '<span> ' + escape(option.name) + ' </span>'
                    + '</div>'
            },
            item: function(option, escape) {
                return '<div>'
                    + '<span> ' + escape(option.name) + ' </span>'
                    + '</div>'
            }
        },
        load: function(query, callback) {
            if (query == '') {
                if ($('#supplier_id').attr('data-item') !== '' && $('#supplier_id').attr('data-id') !== '') {
                    item = JSON.parse($('#supplier_id').attr('data-item'));

                    supplierSelectize[0].selectize.addOption(item);
                    supplierSelectize[0].selectize.addItem($('#supplier_id').attr('data-id'), true);
                }
            }

            if (!query.length || query.length < 2) return callback();

            $.ajax({
                url: GLOBAL_GET_SUPPLIERS_URL,
                type: 'POST',
                dataType: 'json',
                data: {
                    query: query
                },
                error: function() {
                    callback();
                },
                success: function(res) {
                    if (res.status == 'error') {
                        notification(res);
                    } else {
                        callback(res);
                        $('#supplier_id')[0].selectize.refreshOptions();
                    }
                }
            });
        },
        onChange: function(value) {
            if (!value) {
                supplierSelectize[0].selectize.clear();
            }
        }
    });

    if (jQuery().daterangepicker) {
        var reportRangeSpan = $('#reportrange span'),
            dateRangePickerOptions = {
                ranges: {
                    'Today': [moment(), moment()],
                    'This Week': [moment().startOf('week'), moment().endOf('week')],
                    'This Month': [moment().startOf('month'), moment().endOf('month')]
                },
                startDate: moment(),
                endDate: moment().subtract(-1, 'week'),
                format: globalDateFormat
            };

        $('#estimated_delivery_date_range').daterangepicker(dateRangePickerOptions, function (start, end) {
            reportRangeSpan.html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
        });
    }

    $('#order_date').datetimepicker({
        format: "M j, Y H:i",
		step: 10,
        autoclose: true
    });

    $('#order-new-status-approved').click(function(e) {
        e.preventDefault();
        $('#alertStatusModal').modal('hide');
    });

    $('#order-new-status-canceled').click(function(e) {
        e.preventDefault();
        statusesSelectize[0].selectize.addItem($('#status_shipping').attr('data-id'), true);
        $('#alertStatusModal').modal('hide');
    });

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

    if (isEditMode) {
        $('.team').toggleClass('soft-hide', false);
    } else {
        $('.team').toggleClass('soft-hide', true);
    }

	$('#btnChangeManagerTeam').on('click', function() {
        $.ajax({
            url: GLOBAL_CHANGE_MANAGER_URL,
            type: 'POST',
            dataType: 'json',
            data: {
                order_id: $('#order_id').val(),
                team_id:  $('#manager_team_id').val()
            },
            success: function (response) {
                if (response.status == 'success') {
                    $('#modal_change_manager_team').modal('hide');
                }

                notification(response);
            }
        });
	});

	var prepareData = function() {
		var type = $('.type').val(),
			costCenterList = [],
			accountVal = parseInt($('.account').val()),
			supplierVal = parseInt($('#supplier_id').val()),
			data = {
				order_id: $('#order_id').val(),
				price: $('#price').val(),
				currency: $('#currency').val(),
				po_id: $('.po-id').val(),

				// Type definition
				// 1 - Transaction account id
				// 2 - Supplier id
				supplier_type: accountVal ? 1 : 2,
				supplier_id: accountVal ? accountVal : supplierVal,
				account_reference: $('.supplier-reference').val(),
				cost_centers: costCenterList,
				sub_category_id: $('.sub-category').val(),
				type: type,
				period: $('.period').val(),
                is_attach_po: 1
			};

		$('.item-cost-centers .selectize-input div[data-value]').each(function() {
			costCenterList.push({
				id: $(this).attr('data-id'),
				type: $(this).attr('data-type'),
				currencyId: $(this).attr('data-currency-id')
			});
		});

		data.costCenters = costCenterList;

		return data;
	};

	$('.create-item').on('click', function(e) {
		e.preventDefault();

		if ($('#item-form').valid()) {
			var btn = $(this);

			btn.button('loading');

			if (form.valid()) {
				$.ajax({
					type: "POST",
					url: GLOBAL_CREATE_PO_ITEM,
					data: prepareData(),
					dataType: "json",
					success: function () {
						location.reload();
					}
				});
			} else {
				btn.button('reset');
				notification({
					status: 'warning',
					msg: 'Please fill in all the required fields first.'
				});
			}
		}
	});

	$('.create-item-without-po').on('click', function(e) {
		e.preventDefault();

		var btn = $(this);

		if (form.valid()) {
			btn.button('loading');
            obj = $('#order_form').serializeArray();

			if ($('#order_id').val() > 0) {
				$.ajax({
					type: "POST",
					url: GLOBAL_CREATE_PO_ITEM,
					data: obj,
					dataType: "json",
					success: function() {
						location.reload();
					}
				});
			} else {
				btn.button('reset');
				notification({"status": "error", "msg": "Fill required filed"});
			}
		} else {
			notification({
				status: 'warning',
				msg: 'Please fill in all the required fields first.'
			});
		}
	});

	$('#alertPOItemModal').on('shown.bs.modal', function() {
		var $currency = $('.currency'),
			$display = $currency.find('.display'),
			currencyId = $('#currency').val(),
			account = $('.account')[0].selectize;

		$('.type')[0].selectize.lock();
		$('.amount').val($('#price').val()).prop('readonly', true);

		$currency.find('button').addClass('disabled');
		$currency.find('a').each(function() {
			if ($(this).attr('data-currency-id') == currencyId) {
				$currency.attr('data-value', $(this).attr('data-value').toLowerCase());
				$display.attr('data-currency-id', $(this).attr('data-currency-id'));
				$display.text($(this).attr('data-value').toUpperCase());
			}
		});

		if ($('#supplier_id').val()) {
			account.addOption({
				unique_id: '0',
				account_id: 0,
				name: $('#supplier_id option:checked').text(),
				label: 'External',
				type: 4
			});
			account.addItem('0', true);
		}
	});


    if ($('.po-id').length) {

        var $poId = $('.po-id');
        $poId.selectize({
            create: false,
            plugins: ['remove_button'],
            searchField: ['value', 'text'],
            valueField: 'value',
            labelField: 'text',
            sortField: [
                {
                    field: 'text'
                }
            ]
        });
        $poId[0].selectize.clear();
    }


	// Validation
	$.validator.addMethod('dateEx', function(value, element) {
		return this.optional(element) || /^(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s[0-9]{2},\s[0-9]{4}$/i.test(value);
	}, 'Date is invalid.');

	$.validator.addMethod('amount', function(value, element) {
		return this.optional(element) || /^[0-9]+(\.[0-9]{1,2})?$/i.test(value);
	}, 'Amount is invalid.');

	$.validator.setDefaults({
		ignore: ':hidden:not(*)'
	});

	$('#item-form').validate({
		onfocusout: false,
		invalidHandler: function(form, validator) {
			var errors = validator.numberOfInvalids();

			if (errors) {
				validator.errorList[0].element.focus();
			}
		},
		highlight: function (element, errorClass, validClass) {
			if ($(element).prop('tagName') == 'TEXTAREA') {
				$(element).addClass('has-error');
			} else {
				$(element).parent().addClass('has-error');
			}
		},
		unhighlight: function (element, errorClass, validClass) {
			if ($(element).prop('tagName') == 'TEXTAREA') {
				$(element).removeClass('has-error');
			} else {
				$(element).parent().removeClass('has-error');
			}
		},
		success: function (label) {
			$(label).closest('form').find('.valid').removeClass('invalid');
		},
		errorPlacement: function (error, element) {
			// do nothing
		}
	});

	$('select.account').rules('add', {
		required: true
	});

	$('input.amount').rules('add', {
		required: true,
		amount: true,
		min: 0.01
	});

	$('select.po-id').rules('add', {
		required: true,
		number: true,
		min: 1
	});

	$('select.type').rules('add', {
		required: true,
		number: true,
		min: 0
	});

	$('select.item-cost-centers').rules('add', {
		required: true
	});

	$('select.category').rules('add', {
		required: true,
		number: true,
		min: 1
	});

	$('select.sub-category').rules('add', {
		required: true,
		number: true,
		min: 1
	});

    jQuery.validator.addMethod("LessThenOrEqualToOriginalPrice", function(value, element) {
        return parseFloat(value) <= parseFloat($('#money_account_transaction').val());
    }, "");

    $('#item-transaction-form').validate({
        ignore: '',
        rules: {
            'transaction_amount': {
                required: true,
                number: true,
                min: 1,
                LessThenOrEqualToOriginalPrice: true
            },
            'transaction_date': {
                required: true
            },
            money_accounts: {
                required: true,
                number: true,
                min: 1
            }
        },
        invalidHandler: function(form, validator) {
            var errors = validator.numberOfInvalids();

            if (errors) {
                validator.errorList[0].element.focus();
            }
        },
        highlight: function (element, errorClass, validClass) {
            if ($(element).prop('tagName') == 'TEXTAREA') {
                $(element).addClass('has-error');
            } else {
                $(element).parent().addClass('has-error');
            }
        },
        unhighlight: function (element, errorClass, validClass) {
            if ($(element).prop('tagName') == 'TEXTAREA') {
                $(element).removeClass('has-error');
            } else {
                $(element).parent().removeClass('has-error');
            }
        },
        success: function (label) {
            $(label).closest('form').find('.valid').removeClass('invalid');
        },
        errorPlacement: function (error, element) {
            // do nothing
        }
    });

    // Shipping status change
    $('#save_button').on('click', function(e, force) {
        var $shippingStatus = $('#status_shipping'),
            statusChanged = false,
            initialShippingStatus = $shippingStatus.attr('data-id');

        // Shipping status definition
        // 1 - To be ordered
        if (   (initialShippingStatus == '1' && $shippingStatus.val() != '1' && $shippingStatus.val() != '3')
            || (initialShippingStatus == '3' && $shippingStatus.val() == '2')) {
            statusChanged = true;
        }

        if (statusChanged) {
            if (force !== true) {
                e.preventDefault();

                if (form.valid()) {
                    var $moneyAccounts = $('.order-item-money-accounts'),
                        $currency = $('.money-account-currency'),
                        orderCurrency = $('#currency option:selected').text(),
                        orderAmount = $('#price').val(),
                        date = $('#data').attr('data-creation-date');

                    if (!$moneyAccounts.hasClass('selectized')) {
                        $moneyAccounts.selectize({
                            valueField: 'id',
                            labelField: 'name',
                            sortField: 'name',
                            searchField: ['name'],
                            hideSelected: true,
                            highlight: false,
                            options: JSON.parse($('#data').attr('data-money-accounts')),
                            render: {
                                option: function(item, escape) {
                                    return '<div><span class="label label-primary">' + escape(item.currency) + '</span> ' + escape(item.name) + ' <small class="text-info">' + escape(item.bank_name) + '</small></div>';
                                },
                                item: function(item, escape) {
                                    return '<div data-id="' + escape(item.id) + '"><span class="label label-primary">' + escape(item.currency) + '</span> ' + escape(item.name) + ' <small class="text-info">' + escape(item.bank_name) + '</small></div>';
                                }
                            },
                            onChange: function(value) {
                                if ($moneyAccounts.val()) {
                                    var currency = $moneyAccounts[0].selectize.sifter.items[$moneyAccounts.val()]['currency'],
                                        amount = orderAmount;

                                    $currency.text(currency);

                                    if (currency != orderCurrency) {
                                        amount = convertCurrency(date, orderAmount, orderCurrency, currency);
                                    }
                                    $('#money_account_transaction').val(amount);
                                    $('.transaction-amount').val(amount);
                                } else {
                                    $currency.html('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
                                }
                            }
                        });
                    }

                    $('#alertPOItemTransactionModal').modal('show');
                }
            }
        }
    });

    $('.create-item-transaction').on('click', function(e) {
        e.preventDefault();
        if ($('#item-transaction-form').valid()) {
            var btn = $(this);
            btn.button('loading');
            var data = {
                order_id: $('#order_id').val(),
                money_account_id: $('.order-item-money-accounts').val(),
                transaction_date: $('.order-item-transaction-date').val(),
                amount: $('.transaction-amount').val(),
                supplier_id: $('#supplier_id').val()
            };
            var $attachments = $('.items-attachments');
            var files = $attachments.length ? $attachments.get(0).files : [];
            var formDataFromOrder = new FormData();
            formDataFromOrder.append('data', JSON.stringify(data));
            if (files.length) {
                formDataFromOrder.append('file', files[0]);
            }
            $.ajax({
                url: btn.attr('data-url'),
                type: 'POST',
                data: formDataFromOrder,
                processData: false,
                contentType: false,
                error: function() {
                    btn.button('reset');
                    notification({
                        status: 'error',
                        msg: 'ERROR! Something went wrong (create item transaction)'
                    });
                },
                success: function(res) {
                    if (res.status == 'success') {
                        $('#alertPOItemTransactionModal').modal('hide');
                        $('#price').val($('.transaction-amount').val());
                        $('#currency').val(res.moneyTransactionAccountId);
                        btn.button('reset');

                        $('#save_button').trigger('click', [true]);
                    }
                }
            });
        }
    });

    /**
     * Request money modal open litener
     */
    $('#requestAdvanceModal').on('shown.bs.modal', function () {
        var currency = $('#currency option:selected').text();
        $(this).find('.money-account-currency').html(currency);
    });

    $('#refundModal').on('shown.bs.modal', function () {
        var currency = $('#currency option:selected').text();
        $(this).find('.money-account-currency').html(currency);
    });

    /**
     * Request Money
     *
     * @type {*|jQuery|HTMLElement}
     */
    var $moneyAccounts = $('.user-money-accounts'),
        $currency = $('.money-account-currency'),
        orderCurrency = $('#currency option:selected').text(),
        orderAmount = $('#price').val(),
        date = $('#data').attr('data-creation-date');

    if (!$moneyAccounts.hasClass('selectized')) {
        $moneyAccounts.selectize({
            valueField: 'id',
            labelField: 'name',
            sortField: 'name',
            searchField: ['name'],
            hideSelected: true,
            highlight: false,
            options: JSON.parse($('#data').attr('data-money-accounts')),
            render: {
                option: function(item, escape) {
                    var bankInfo = (item.bank_name != null) ? (' <small class="text-info">' + escape(item.bank_name) + '</small>') : '';
                    return '<div><span class="label label-primary">' + escape(item.currency) + '</span> ' + escape(item.name) + bankInfo + '</div>';
                },
                item: function(item, escape) {
                    var bankInfo = (item.bank_name != null) ? (' <small class="text-info">' + escape(item.bank_name) + '</small>') : '';
                    return '<div data-id="' + escape(item.id) + '"><span class="label label-primary">' + escape(item.currency) + '</span> ' + escape(item.name) + bankInfo + '</div>';
                }
            },
            onChange: function(value) {
                if ($moneyAccounts.val()) {
                    var currency = $moneyAccounts[0].selectize.sifter.items[$moneyAccounts.val()]['currency'],
                        amount = orderAmount;

                    $currency.text(currency);

                    if (currency != orderCurrency) {
                        amount = convertCurrency(date, orderAmount, orderCurrency, currency);
                    }
                    $('.request_advance_amount').val(amount);
                } else {
                    $currency.html('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
                }
            }
        });
    }

    /**
     * Save request money
     */
    $('.request-money').on('click', function(e) {
        e.preventDefault();

        var btn = $(this);

        if ($('#order_id').val() > 0) {
            btn.button('loading');

            var userMoneyAccounts = $('.user-money-accounts');
            var requestAdvanceAmount = $('.request_advance_amount');

            if (userMoneyAccounts.val() > 0 && requestAdvanceAmount.val() > 0) {
                $.ajax({
                    type: "POST",
                    url: GLOBAL_REQUEST_MONEY,
                    data: {
                        order_id: $('#order_id').val(),
                        money_account_name: $('#request-money-form .selectize-input > div').html(),
                        money_amount: requestAdvanceAmount.val(),
                        currency_iso: userMoneyAccounts[0].selectize.sifter.items[$moneyAccounts.val()]['currency']
                    },
                    dataType: "json",
                    success: function () {
                        location.reload();
                    }
                });
            } else {
                btn.button('reset');
                notification({"status": "error", "msg": "Fill required field"});
            }
        } else {
            btn.button('reset');
            notification({"status": "error", "msg": "Fill required field"});
        }
    });

    $('.refund-request').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);

        if ($('#order_id').val() > 0) {
            btn.button('loading');

            var refundAmount = $('.refund-amount');

            if (refundAmount.val() > 0) {
                $.ajax({
                    type: "POST",
                    url: GLOBAL_REQUEST_REFUND,
                    data: {
                        orderId: $('#order_id').val(),
                        poId: $('#po_id').val(),
                        refundAmount: refundAmount.val(),
                        orderAmount: $('#price').val(),
                    },
                    dataType: "json",
                    success: function () {
                        $('#save_button').trigger('click', [true]);
                    }
                }); return false;
            } else {
                btn.button('reset');
                notification({"status": "error", "msg": "Fill required field"});
            }
        } else {
            btn.button('reset');
            notification({"status": "error", "msg": "Fill required field"});
        }
    });

    function getItemDetails() {
        $.ajax({
            type: "POST",
            url: GLOBAL_GET_ITEM_ACCOUNT_DETAILS,
            data: {
                orderId: $('#order_id').val(),
                poId: $('#po_id').val(),
            },
            dataType: "json",
            success: function (data) {
                if (data.hasNotTransaction) {
                    $('#request-refund-form').hide();
                    $('div.refund-description').text('The order already has approved item. On changing status to Refunded the item will be refunded.');
                } else {
                    $('div.account-from').text(data.accountFrom);
                    $('div.account-to').text(data.accountTo);
                }
            }
        });
    }

    $('.refund-cancel').on('click', function() {
        statusesSelectize[0].selectize.setValue(CURRENT_ORDER_STATUS);
    });

    if ($('#status_shipping').attr('data-id') == STATUS_CANCELED) {
        $('.order-date').toggleClass('soft-hide', true);
        $( "#order_date" ).rules( "remove" );
        $( "#estimated_delivery_date_range" ).rules( "remove" );
    }

    $('.get-approval').on('click', function(e) {
        e.preventDefault();

        if (form.valid()) {
            $('#alertPOItemModal').modal('show');
        } else {
            notification({
                status: 'warning',
                msg: 'Please fill in all the required fields first.'
            });
        }
    });
});
