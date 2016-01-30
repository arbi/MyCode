$(function() {
	var $transferContainer = $('.transfer-container'),
		$moneyAccounts = $('.money-account'),
		$moneyAccountFrom = $('.money-account-from'),
		$moneyAccountTo = $('.money-account-to'),
		$supplier = $('.supplier'),
		$supplierFrom = $('.supplier-from'),
		$supplierTo = $('.supplier-to'),
		$addTransfer = $('.add-transfer'),
		$addPendingTransfer = $('.add-pending-transfer'),
		$accountType = $('.account-type'),
		$form = $('.add-transfer-form'),
		$tabForm = $('#tab-form'),
		$tabController = $('.tab-controller'),
		$resNumbers = $('.res-numbers'),
		$amount = $('.amount'),
		$dateFrom = $('.date-from'),
		$partnersFrom = $('.partners-from'),
		$partnersTo = $('.partners-to'),
		$dateTo = $('.date-to'),
		$amountFrom = $('.amount-from'),
		$amountTo = $('.amount-to'),
		$distTotal = $('.dist-total'),
		$distTotalAmount = $('.dist-total-amount'),
		$collectionTotal = $('.collection-total'),
		$distTotalCurrency = $('.dist-total-currency'),
		$collectionTotalCurrency = $('.collection-total-currency'),
		$partnerCollectionTotal = $('#partner-collection .total'),
		$debitTotal = $('.dynamic-expense-addition .total'),
		$totalCurrency = $('.total-currency'),
		$choose = $('.choose'),
		$collectionChoose = $('.collection-choose'),
		$date = $('.date'),
		$dynamicExpense = $('.dynamic-expense-addition'),
		$distribution = $('.apartments-and-apartels'),
		$distResNumberList = $('.dist-res-number-list'),
		$collectionTransactionList = $('.collection-transaction-list'),
		$collectionPeriod = $('.collection-period'),
		$psp = $('.psp'),
		$amountFromContainer = $amountFrom.closest('.form-group'),
		$amountToContainer = $amountTo.closest('.form-group'),
		moneyAccounts = JSON.parse($transferContainer.attr('data-money-accounts')),
		resNumberList = JSON.parse($transferContainer.attr('data-reservations')),
		pendingTransferId = parseInt($transferContainer.attr('data-pending-transfer-id')),
		refundTypes = [2, 4, 5, 6],// Refund, Chargeback Dispute, Chargeback Fraud, Chargeback Other
		getPartnerPaymentReservationHtml = function(item) {
			return '<tr data-apartment-id="' + item.apartment_id + '" data-amount="' + item.partner_balance + '" data-reservation-id="' + item.id + '">' +
				'<td>' +
					'<a href="/booking/edit/' + item.res_number + '" target="_blank" class="partner-reservations text-bold">' + item.res_number + '</a>' +
				'</td>' +

				'<td>' +
					'<small class="text-muted text-bold crop">' + item.apartment_name.toUpperCase() + '</small>' +
				'</td>' +

				'<td>' +
					'<small class="nowrap">' + item.departure_date + '</small>' +
				'</td>' +

				'<td class="text-primary text-bold text-right partner-amount" data-amount="' + item.partner_balance + '">' +
					item.partner_balance + ' ' + item.symbol +
				'</td>' +

				'<td class="text-primary w1">' +
					'<a class="btn btn-xs btn-danger res-remove" data-id="' + item.id + '">Remove</a>' +
				'</td>' +
			'</tr>'
		},
		getCollectionReadyTransactionsHtml = function(item) {
			var isRefund = (
					refundTypes.indexOf(parseInt(item.type)) !== -1
				),
				tdClass = isRefund ? 'danger' : '';

			return '<tr class="' + tdClass + '" data-amount="' + item.bank_amount + '" data-transaction-id="' + item.id + '">' +
				'<td>' +
					'<a href="/booking/edit/' + item.res_number + '" target="_blank" class="text-bold">' + item.res_number + '</a>' +
				'</td>' +

				'<td>' +
					'<small class="nowrap">' + item.transaction_date + '</small>' +
				'</td>' +

				'<td>' +
					'<small class="text-muted">' + item.departure_date + '</small>' +
				'</td>' +

				'<td class="text-primary text-bold text-right collection-ready-amount" data-amount="' + item.bank_amount + '">' +
					item.bank_amount + ' ' + item.symbol +
				'</td>' +

				'<td class="text-primary w1">' +
					'<a class="btn btn-xs btn-danger transaction-remove" data-id="' + item.id + '">Remove</a>' +
				'</td>' +
			'</tr>'
		},
		hasDuplicateReservation = function(resNumber) {
			var hasDuplicate = false;

			$('.partner-reservations').each(function() {
				if ($(this).text() == resNumber) {
					hasDuplicate = true;
				}
			});

			return hasDuplicate;
		},
		getApartmentCosts = function() {
			var costs = {};

			$distResNumberList.find('tr').each(function() {
				var apartmentId = parseInt($(this).attr('data-apartment-id')),
					amount = parseFloat($(this).attr('data-amount'));

				if (costs[apartmentId] == undefined) {
					costs[apartmentId] = amount;
				} else {
					costs[apartmentId] += amount;
				}
			});

			return costs;
		},
		getReservations = function() {
			var reservationList = [];

			$distResNumberList.find('tr').each(function() {
				var reservationId = parseInt($(this).attr('data-reservation-id'));

				reservationList.push(reservationId);
			});

			return reservationList;
		},
		serializeObject = function(object, name) {
			var pairs = [];

			for (var element in object) {
				if (object.hasOwnProperty(element)) {
					if (object[element] == undefined) {
						pairs.push(name + '[]=' + element);
					} else {
						pairs.push(name + '[' + element + ']=' + object[element]);
					}
				}
			}

			return pairs.join('&');
		},

		ACCOUNT_PARTNER_COLLECTION = 'partner-collection',
		ACCOUNT_PARTNER_PAYMENT = 'partner-payment',
		ACCOUNT_TRANSFER = 'transfer',
		ACCOUNT_RECEIVE = 'receive',
		ACCOUNT_PAY = 'pay',
		ACCOUNT_PSP = 'psp';

	$date.daterangepicker({
		'singleDatePicker': true,
		'format': globalDateFormat
	});

	$tabController.find('a').click(function() {
		$('.' + $(this).attr('data-class') + ' a').trigger('click');

		$(this).closest('.dropdown').find('.dropdown-toggle span').text(
			$(this).text()
		);
	});

	$supplier.selectize({
		valueField: 'unique_id',
		labelField: 'name',
		searchField: ['name'],
		persist: false,
		hideSelected: true,
		highlight: false,
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
				url: $transferContainer.attr('data-account-url'),
				type: 'POST',
				data: {'q': encodeURIComponent(query)},
				error: function() {
					callback();
				},
				success: function(res) {
					callback(res.data);
				}
			});
		}
	});

	$moneyAccounts.selectize({
		valueField: 'id',
		searchField: ['name', 'currency'],
		highlight: false,
		options: moneyAccounts,
		persist: true,
		hideSelected: true,
		render: {
			option: function(item, escape) {
				return '<div><span class="label label-primary">' + escape(item.currency) + '</span> ' + escape(item.name) + '</div>';
			},
			item: function(item, escape) {
				return '<div data-currency="' + escape(item.currency) + '"><span class="label label-primary">' + escape(item.currency) + '</span> ' + escape(item.name) + '</div>';
			}
		},
		onItemAdd: function(value, $item) {
			$item.closest('.direction').find('.amount-addon').text(
				$item.attr('data-currency')
			);
		}
	});

	$partnersTo.selectize();
	$partnersFrom.selectize({
		onItemAdd: function(value, $item) {
			$resNumbers[0].selectize.enable();
		},
		onItemRemove: function(value) {
			$resNumbers[0].selectize.disable();
		},
		onChange: function() {
			var resNumSelectize = $resNumbers[0].selectize,
				items = resNumSelectize.sifter.items,
				item;

			resNumSelectize.clear();
			$partnerCollectionTotal.text('0.00');

			for (item in items) {
				if (items.hasOwnProperty(item)) {
					resNumSelectize.removeOption(item, true);
				}
			}

			for (item in resNumberList) {
				if (resNumberList.hasOwnProperty(item)) {
					resNumSelectize.addOption(
						resNumberList[item]
					);
				}
			}

			for (item in items) {
				if (items.hasOwnProperty(item)) {
					if (parseInt(items[item].partner_id) != parseInt($partnersFrom.val())) {
						resNumSelectize.removeOption(item, true);
					}
				}
			}
		}
	});

	$distribution.selectize({
		valueField: 'id',
		labelField: 'type',
		searchField: ['name', 'type'],
		highlight: false,
		persist: true,
		hideSelected: true,
		options: [],
		render: {
			option: function(item, escape) {
				return '<div><span class="label label-' + (parseInt(item.type_id) == 1 ? 'primary' : 'success') + '">' + escape(item.type) + '</span> ' + escape(item.name) + '</div>';
			},
			item: function(item, escape) {
				return '<div><span class="label label-' + (parseInt(item.type_id) == 1 ? 'primary' : 'success') + '">' + escape(item.type) + '</span> ' + escape(item.name) + '</div>';
			}
		},
		load: function(query, callback) {
			if (query.length < 3) {
				return callback();
			}

			$.ajax({
				url: $transferContainer.attr('data-distribution-list-url'),
				type: 'POST',
				data: {'q': encodeURIComponent(query)},
				error: function() {
					callback();
				},
				success: function(res) {
					callback(res);
				}
			});
		}
	});

	$psp.selectize();

	$collectionPeriod.daterangepicker({
		startDate: moment(),
		endDate: moment(),
		format: 'YYYY-MM-DD',
		locale: {
			firstDay: 1
		}
	});

	$amount.blur(function() {
		var val = $(this).val();

		if (val != '') {
			$(this).val(
				parseFloat(val).toFixed(2)
			);
		}
	});

	$amountTo.blur(function() {
		if ($accountType == ACCOUNT_PARTNER_COLLECTION) {
			$(document).trigger('disbalance');
		}
	});

	$dateFrom.blur(function() {
		if ($dateFrom.val() != '' && $dateTo.val() == '') {
			$dateTo.val($dateFrom.val());
		}
	});

	$dateTo.blur(function() {
		if ($dateTo.val() != '' && $dateFrom.val() == '') {
			$dateFrom.val($dateTo.val());
		}
	});

	$resNumbers.selectize({
		delimiter: ',',
		persist: true,
		maxItems: null,
		valueField: 'id',
		searchField: ['res_number', 'apartment'],
		options: resNumberList,
		render: {
			item: function(item, escape) {
				return '<div class="row full-width" data-currency-symbol="' + escape(item.currency_symbol) + '">' +
					'<div class="col-xs-9">' +
						'<div class="row">' +
							'<div class="col-xs-7">' +
								'<span class="label label-info" href="/booking/edit/" target="_blank">' + escape(item.status) + '</span> ' +
								'<a class="text-bold text-primary" href="/booking/edit/' + escape(item.res_number) + '" target="_blank">' + escape(item.res_number) + '</a> ' +
								(parseInt(item.issue) ? '<small title="Issue detected"><i class="glyphicon glyphicon-warning-sign text-danger"></i></small>' : '') +
								'<span class="pull-right text-small text-muted" style="margin-right: -20px">Departure:</span> ' +
							'</div>' +
							'<div class="col-xs-5">' +
								'<span class="text-small">' + escape(item.checkout) + '</span>' +
							'</div>' +
						'</div>' +
						'<div class="row text-small">' +
							'<div class="col-xs-7 text-bold text-muted crop">' +
								escape(item.apartment.toUpperCase()) +
							'</div>' +
							'<div class="col-xs-5 text-bold text-muted crop">' +
								escape(item.partner.toUpperCase()) +
							'</div>' +
						'</div>' +
					'</div>' +
					'<div class="col-xs-3 text-right">' +
						'<span class="text-muted text-small">&nbsp;</span>' +
						'<div class=" text-big text-primary"> ' + escape(item.balance) + ' ' + escape(item.currency_symbol) + '</div>' +
					'</div>' +
				'</div>';
			},
			option: function(item, escape) {
				return '<div class="row">' +
					'<div class="col-xs-9">' +
						'<div class="row">' +
							'<div class="col-xs-7">' +
								'<span class="btn label label-info">' + escape(item.status) + '</span> ' +
								'<span class="text-bold text-primary">' + escape(item.res_number) + '</span> ' +
								(parseInt(item.issue) ? '<small><i class="glyphicon glyphicon-warning-sign text-danger"></i></small>' : '') +
								'<span class="pull-right text-small text-muted" style="margin-right: -20px">Departure:</span> ' +
							'</div>' +
							'<div class="col-xs-5">' +
								'<span class="text-small">' + escape(item.checkout) + '</span>' +
							'</div>' +
						'</div>' +
						'<div class="row text-small">' +
							'<div class="col-xs-7 text-bold text-muted crop">' +
								escape(item.apartment.toUpperCase()) +
							'</div>' +
							'<div class="col-xs-5 text-bold text-muted crop">' +
								escape(item.partner.toUpperCase()) +
							'</div>' +
						'</div>' +
					'</div>' +
					'<div class="col-xs-3 text-right">' +
						'<span class="text-muted text-small">&nbsp;</span>' +
						'<div class=" text-big text-primary"> ' + escape(item.balance) + ' ' + escape(item.currency_symbol) + '</div>' +
					'</div>' +
				'</div>';
			}
		},
		onItemAdd: function(value, $item) {
			var itemSelectize = $resNumbers[0].selectize,
				itemBalance = itemSelectize.sifter.items[value].balance;

			$partnerCollectionTotal.text(
				(parseFloat($partnerCollectionTotal.text()) + parseFloat(itemBalance)).toFixed(2)
			);

			$totalCurrency.text(
				$item.attr('data-currency-symbol')
			);

			//// Extra logic to prevent multi currency transactions in a one box
			//if (itemSelectize.items.length == 1 && itemSelectize.currentResults.items.length > 0) {
			//	for (var i in itemSelectize.currentResults.items) {
			//		if (itemSelectize.currentResults.items.hasOwnProperty(i)) {
			//			if (itemSelectize.sifter.items[value].currency_id == itemSelectize.sifter.items[itemSelectize.items[0]].currency_id) {
			//				itemSelectize.removeOption(
			//					itemSelectize.currentResults.items[i]['id']
			//				);
			//			}
			//		}
			//	}
			//}
		},
		onItemRemove: function(value) {
			var itemBalance = $resNumbers[0].selectize.sifter.items[value].balance;
			$partnerCollectionTotal.text(
				(parseFloat($partnerCollectionTotal.text()) - parseFloat(itemBalance)).toFixed(2)
			);

			if (!$resNumbers[0].selectize.items.length) {
				$totalCurrency.text('');
			}
		},
		onChange: function() {
			$(document).trigger('disbalance');
		},
		onInitialize: function() {
			$('.tab-content').removeClass('hide');
		}
	});

	$resNumbers[0].selectize.disable();

	// Scrabble (Payment)
	$choose.click(function(e) {
		e.preventDefault();

		$distTotal.text('0.00');
		$distTotalAmount.val('0');
		$distTotalCurrency.text('');

		var partner = $partnersTo.val(),
			apartmentOrApartel = $distribution.val();

		$.ajax({
			url: $transferContainer.attr('data-distribution-reservations-url'),
			type: 'POST',
			data: {
				partner: partner,
				date_from: $('.date-to-from').val(),
				date_to: $('.date-to-to').val(),
				dist: apartmentOrApartel
			},
			error: function() {
				$distResNumberList.html('<tr><td colspan="10">No Data</td></tr>');

				notification({
					status: 'error',
					msg: 'ERROR! Something went wrong'
				});
			},
			success: function(data) {
				if (data.status == 'error') {
					$distResNumberList.html('<tr><td colspan="10">No Data</td></tr>');
					notification(data);
				} else {
					data = data.data;

					$distResNumberList.html('');

					if (data.length) {
						for (var item in data) {
							if (data.hasOwnProperty(item)) {
								// In case of performance issue - delete statement below
								if (!hasDuplicateReservation(data[item].res_number)) {
									$distResNumberList.append(
										getPartnerPaymentReservationHtml(data[item])
									);
								}
							}
						}

						$distTotalCurrency.text(data[item].symbol);
					}

					$(document).trigger('calculate-partner-payment-total');
				}
			}
		});
	});

	$collectionChoose.click(function(e) {
		e.preventDefault();

		var pspId = $psp.val(),
			dateRange = $collectionPeriod.val();

		$.ajax({
			url: $transferContainer.attr('data-transactions-to-collect-url'),
			type: 'POST',
			data: {
				pspId: pspId,
				dates: dateRange
			},
			error: function() {
				$collectionTransactionList.html('<tr><td colspan="10">No Data</td></tr>');

				notification({
					status: 'error',
					msg: 'ERROR! Something went wrong'
				});
			},
			success: function(data) {
				if (data.status == 'error') {
					$collectionTransactionList.html('<tr><td colspan="10">No Data</td></tr>');
					notification(data);
				} else {
					data = data.data;

					$collectionTransactionList.html('');

					if (data.length) {
						for (var item in data) {
							if (data.hasOwnProperty(item)) {
								// In case of performance issue - delete statement below
								if (!hasDuplicateReservation(data[item].res_number)) {
									$collectionTransactionList.append(
										getCollectionReadyTransactionsHtml(data[item])
									);
								}
							}
						}

						$collectionTotalCurrency.text(data[item].symbol);
					}

					$(document).trigger('calculate-collection-ready-reservations-total');
				}
			}
		});
	});

	$addTransfer.click(function(e) {
		e.preventDefault();

		var url = $form.attr('action'),
			data = $form.serialize(),
			btn = $(this);

		if ($form.valid()) {
			btn.button('loading');

			switch ($accountType.val()) {
				case ACCOUNT_PSP:
					$collectionTransactionList.find('tr').each(function() {
						data += ('&transactions[]=' + $(this).attr('data-transaction-id'));
					});

					break;
				case ACCOUNT_RECEIVE: // aka Credit
					var accountFrom = $supplierFrom[0].selectize.sifter.items[$supplierFrom.val()];

					// Replace account id with supplier id and type
					data = data.replace(/supplier_from=\d+/, 'supplier_from=' + accountFrom.account_id + '&supplier_from_type=' + accountFrom.type);

					break;
				case ACCOUNT_PAY: // aka Debit
					var accountTo = $supplierTo[0].selectize.sifter.items[$supplierTo.val()];

					// Replace account id with supplier id and type
					data = data.replace(/supplier_to=\d+/, 'supplier_to=' + accountTo.account_id + '&supplier_to_type=' + accountTo.type);

					break;
				default:
					if (pendingTransferId) {
						data += ('&pending_transfer_id=' + pendingTransferId);
					}

					data += ('&' + serializeObject(getApartmentCosts(), 'costs'));
					data += ('&' + serializeObject(getReservations(), 'reservations'));
			}

			$.ajax({
				type: 'POST',
				url: url,
				data: data,
				dataType: 'json',
				success: function(data) {
					if (data.status == 'success') {
						location.href = location.pathname;
					} else {
						btn.button('reset');
					}

					notification(data);
				}
			});
		} else {
			if (!pendingTransferId && $accountType.val() == ACCOUNT_TRANSFER && $moneyAccountFrom.val() && $moneyAccountTo.val()) {
				$('#pending-transfer-modal').modal('show');
			}
		}
	});

	$addPendingTransfer.on('click', function(e) {
		e.preventDefault();

		var self = $(this);

		self.button('loading');

		$.ajax({
			type: 'POST',
			url: $(this).closest('.modal').attr('data-url'),
			data: {
				money_account_from: $moneyAccountFrom.val(),
				amount_from: $amountFrom.val(),
				date_from: $('.date-from').val(),

				money_account_to: $moneyAccountTo.val(),
				amount_to: $('.amount-to').val(),
				date_to: $('.date-to').val(),

				description: $('.description').val()
			},
			dataType: 'json',
			success: function(data) {
				if (data.status == 'success') {
					location.reload();
				} else {
					self.button('reset');
					notification(data);
				}
			}
		});
	});

	// On partner collection if calculated amount more than they want
	// ATTENTION! This feature is suspended
	$(document).on('disbalance', function () {
		//var $accountFrom = $('.money-account-from');
		//
		//if (parseFloat($amountTo.val()) + parseFloat($partnerCollectionTotal.text()) > 0 && $accountType.val() == ACCOUNT_PARTNER_COLLECTION && !$accountFrom.hasClass('active')) {
		//	$('#receive').addClass('active');
		//} else {
		//	$('#receive').removeClass('active');
		//}
	});

	$tabForm.find('a').click(function(e) {
		e.preventDefault();

		var $moneyAccountToTab = $('.tab-money-account'),
			$partnerPaymentToTab = $('.tab-partner-payment-to'),
			$accountToTab = $('.tab-account');

		$('.to-tabs').hide();

		$amountFromContainer.show();
		$amountToContainer.show();
		$dateFrom.closest('.form-group').show();
		$dateTo.closest('.form-group').show();

		$moneyAccounts[0].selectize.clear();
		$supplierTo[0].selectize.clear();

		// Permanently delete -- Not Listed -- option from list
		$supplierTo[0].selectize.removeOption('78', true);

		$dynamicExpense.addClass('hide');

		// Transfer type fixation
		$accountType.val(
			$(this).attr('href').substr(1)
		);

		switch ($(this).attr('href')) {
			case '#' + ACCOUNT_TRANSFER:
				$(this).closest('.direction').find('.tab-pane').removeClass('active');
				$(this).closest('.direction').find('.tab-pane:first').addClass('active');

				$moneyAccountToTab.show().find('a').trigger('click');

				break;
			case '#' + ACCOUNT_PAY:
				$(this).closest('.direction').find('.tab-pane').removeClass('active');
				$(this).closest('.direction').find('.tab-pane:first').addClass('active');

				$accountToTab.show().find('a').trigger('click');

				$amountToContainer.hide();
				$amountFromContainer.hide();
				$dateTo.closest('.form-group').hide();

				$dynamicExpense.removeClass('hide');

				break;
			case '#' + ACCOUNT_RECEIVE:
				$amountFromContainer.hide();
				$dateFrom.closest('.form-group').hide();

				$moneyAccountToTab.show().find('a').trigger('click');

				break;
			case '#' + ACCOUNT_PARTNER_COLLECTION:
				$dateFrom.closest('.form-group').hide();
				$amountFromContainer.hide();

				$moneyAccountToTab.show().find('a').trigger('click');

				break;
			case '#' + ACCOUNT_PARTNER_PAYMENT:
				$(this).closest('.direction').find('.tab-pane').removeClass('active');
				$(this).closest('.direction').find('.tab-pane:first').addClass('active');

				$amountToContainer.hide();
				$dateTo.closest('.form-group').hide();

				$partnerPaymentToTab.show().find('a').trigger('click');

				break;
			case '#' + ACCOUNT_PSP:
				$(this).closest('.direction').find('.tab-pane').removeClass('active');
				$(this).closest('.direction').find('.tab-pane:first').addClass('active');

				$amountFromContainer.hide();
				$dateFrom.closest('.form-group').hide();

				$moneyAccountToTab.show().find('a').trigger('click');

				break;
		}
	});

	setTimeout(function() {
		$tabForm.find('a:first').trigger('click');
	}, 100);

	// If pending transfer
	if (pendingTransferId) {
		setTimeout(function() {
			$moneyAccounts.each(function() {
				var id = $(this).parent().attr('data-id');

				if (id != undefined) {
					$(this)[0].selectize.addItem(id, false);
				}
			});
		}, 500);

		$('.direction').eq(0).find('.nav-tabs li:not(:first)').hide();
	}

	$(document).on('calculate-partner-payment-total', function() {
		var total = 0;

		$('.partner-amount').each(function() {
			total += parseFloat($(this).attr('data-amount'));
		});

		$distTotalAmount.val(total.toFixed(2));

		$('.dist-total').text(
			total.toFixed(2)
		);
	});

	$(document).on('calculate-collection-ready-reservations-total', function() {
		var total = 0;

		$('.collection-ready-amount').each(function() {
			total += parseFloat($(this).attr('data-amount'));
		});

		$collectionTotal.text(total.toFixed(2));
	});

	$(document).on('click', '.res-remove', function(e) {
		e.preventDefault();

		$(this).closest('tr').remove();

		$(document).trigger('calculate-partner-payment-total');
	});

	$(document).on('click', '.transaction-remove', function(e) {
		e.preventDefault();

		$(this).closest('tr').remove();

		$(document).trigger('calculate-collection-ready-reservations-total');
	});

	$('.add-reservation').click(function(e) {
		e.preventDefault();

		var input = $(this).closest('tr').find('input');

		if (input.val() == '') {
			input.focus();
		} else {
			$.ajax({
				url: $transferContainer.attr('data-distribution-reservations-url'),
				type: 'POST',
				data: {
					res_number: input.val()
				},
				error: function() {
					notification({
						status: 'error',
						msg: 'ERROR! Something went wrong'
					});
				},
				success: function(data) {
					if (data.status == 'error') {
						notification(data);
					} else {
						data = data.data;

						input.val('');

						if (data.length) {
							if (hasDuplicateReservation(data[0].res_number)) {
								notification({
									status: 'warning',
									msg: 'Reservation already in list'
								});
							} else {
								$distResNumberList.append(
									getPartnerPaymentReservationHtml(data[0])
								);

								$distTotalCurrency.text(data[0].symbol);
								$(document).trigger('calculate-partner-payment-total');
							}
						}
					}
				}
			});
		}
	});

	$('.dynamic-expense-add').on('click', function(e, isFirst) {
		e.preventDefault();
		isFirst = isFirst | false;

		var row = $('.template.hide').clone();

		if (isFirst) {
			row.find('.dynamic-expense-remove').remove();
		}

		$dynamicExpense.find('tbody').append(
			row.removeClass('hide')
		);
	}).trigger('click', [true]);

	$dynamicExpense.delegate('.dynamic-expense-remove', 'click', function(e) {
		e.preventDefault();

		$(this).closest('tr').remove();
	});

	$dynamicExpense.delegate('.dynamic-expense-id', 'blur', function() {
		var that = $(this),
			expenseId = that.val(),
			isInt = function (num) {
				return /\d+/.test(num)
			};

		if (isInt(expenseId)) {
			var $dynamicExpenseId = $('.dynamic-expense-id');

			$.ajax({
				url: $transferContainer.attr('data-expense-item-balance-url'),
				type: 'POST',
				data: {
					expenseId: expenseId
				},
				error: function() {
					$dynamicExpenseId.closest('.form-group').removeClass('has-success').addClass('has-error');
					$dynamicExpenseId.rules('add', {
						email: true
					});

					notification({
						status: 'error',
						msg: 'ERROR! Something went wrong'
					});
				},
				success: function(data) {
					if (data.status == 'error') {
						$dynamicExpenseId.closest('.form-group').removeClass('has-success').addClass('has-error');
						$dynamicExpenseId.rules('add', {
							email: true
						});
						notification({
							status: 'warning',
							msg: data.msg
						});
					} else {
						data = data.data;
						$dynamicExpenseId.closest('.form-group').removeClass('has-error').addClass('has-success');
						$dynamicExpenseId.rules('remove', 'email');
						that.parent().find('.expense-amount-currency-addon').text(
							data.balance + ' ' + data.currency
						);
					}
				}
			});
		}
	});

	$(document).on('calculate-dynamic-expense', function() {
		var total = 0;

		$('.dynamic-expense-amount').each(function() {
			if ($(this).val()) {
				total += parseFloat($(this).val());
			}
		});

		$debitTotal.text(
			total.toFixed(2)
		);
	});

	$dynamicExpense.delegate('.dynamic-expense-amount', 'input', function() {
		$(document).trigger('calculate-dynamic-expense');
	});
});
