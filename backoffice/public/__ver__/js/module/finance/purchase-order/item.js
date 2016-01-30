window.item = {};
var self = window.item,
	getCurrencies = function(url, dateList) {
		return $.ajax({
			url: url,
			data: {
				dateList: jQuery.unique(dateList)
			},
			type: 'POST',
			error: function() {
				notification({
					status: 'error',
					msg: 'ERROR! Something went wrong (currency list)'
				});
			},
			success: function(data) {
				if (data.status == 'success') {
					data = data.data;

					self.reservoir.currencyList = {};
					self.reservoir.currencyListEx = data;

					for (var date in data) {
						for (var currency in data[date]) {
							self.reservoir.currencyList[data[date][currency]['code']] = {
								id: data[date][currency]['id'],
								code: data[date][currency]['code'],
								symbol: data[date][currency]['symbol']
							};
						}

						break;
					}
				} else {
					notification(data);
				}
			}
		});
	},
	getSubCategories = function(url) {
		return $.ajax({
			url: url,
			type: 'POST',
			error: function() {
				notification({
					status: 'error',
					msg: 'ERROR! Something went wrong (sub category list)'
				});
			},
			success: function(data) {
				if (data.status == 'success') {
					data = data.data;

					var categories = [],
						subCategoriesSimple = [],
						subCategoryAndCategories = [],
						subCategories = {},
						order = 1;

					for (var categoryId in data) {
						if (data.hasOwnProperty(categoryId)) {
							categories.push({
								value: categoryId,
								text: data[categoryId].name
							});

							subCategoryAndCategories.push({
								id: categoryId,
								name: data[categoryId].name,
								type: 1,
								order: order++
							});

							subCategories[categoryId] = [];

							for (var subCategoryId in data[categoryId].sub) {
								if (data[categoryId].sub.hasOwnProperty(subCategoryId)) {
									subCategories[categoryId].push({
										value: data[categoryId].sub[subCategoryId].id,
										text: data[categoryId].sub[subCategoryId].name
									});

									subCategoryAndCategories.push({
										id: data[categoryId].sub[subCategoryId].id,
										name: data[categoryId].sub[subCategoryId].name,
										type: 2,
										order: order++
									});

									subCategoriesSimple.push({
										value: data[categoryId].sub[subCategoryId].id,
										text: data[categoryId].sub[subCategoryId].name,
										categoryId: categoryId
									});
								}
							}
						}
					}

					self.reservoir.categoryList = categories;
					self.reservoir.subCategoryList = subCategories;
					self.reservoir.subCategoryListSimple = subCategoriesSimple;
					self.reservoir.subCategoryAndCategoryList = subCategoryAndCategories;
				} else {
					notification(data);
				}
			}
		});
	},
	prepareData = function() {
		var formData = new FormData(),
			$attachments = $('.items-attachments'),
			$data = $('#data'),
			$moneyAccount = $('.money-accounts'),
			$transactionDate = $('.transaction-date'),
			isAdd = !parseInt($data.attr('data-item-id')),
			type = $('.type').val(),
			costCenterList = [],
			files = $attachments.length ? $attachments.get(0).files : [],
			data = {
				poId: $('.po-list').val(),
				itemId: $data.attr('data-item-id'),
				accountId: $('.account').val(),
				accountReference: $('.supplier-reference').val(),
				comment: $('.item-comment').val(),
				costCenters: costCenterList,
				amount: $('.amount').val(),
				currencyId: $('.currency .display').attr('data-currency-id'),
				subCategoryId: $('.sub-category').val(),
				type: type,
				period: $('.period').val()
			};

        if ($('.is_startup').length) {
            data.isStartup = $('.is_startup').is(':checked') ? 1 : 0;
        }

        if ($('.is_deposit').length) {
            data.isDeposit = $('.is_deposit').is(':checked') ? 1 : 0;
        }

        if ($('.is_refund').length) {
            data.isRefund = $('.is_refund').is(':checked') ? 1 : 0;
        }

		// On Add and Request an Advance
		if (isAdd && type === '1' && $moneyAccount.val()) {
			var sifter = $moneyAccount[0].selectize.sifter.items[$moneyAccount.val()];

			$('.transaction').remove();

			data.moneyAccount = $moneyAccount.val();
			data.transactionDate = $transactionDate.val();
			data.comment += "\n\nPrefered Money Account - [" + sifter['currency'] + '] ' + sifter['name'] + ' / ' + sifter['bank_name'];
		}

		if (type === '0') {
			$('.transfer').remove();

			// Do not use $moneyAccount variable here
			data.moneyAccount = $('.money-accounts').val();
			data.transactionDate = $transactionDate.val();
		}

		$('.item-cost-centers .selectize-input div[data-value]').each(function() {
			costCenterList.push({
				id: $(this).attr('data-id'),
				type: $(this).attr('data-type'),
				currencyId: $(this).attr('data-currency-id')
			});
		});

		data['costCenters'] = costCenterList;

		formData.append('data', JSON.stringify(data));

		if (files.length) {
			formData.append('file', files[0]);
		}

		return formData;
	},
	convertCurrency = function (date, sourceAmount, sourceCurrency, destinationCurrency) {
		if (sourceCurrency == '' || destinationCurrency == '' || destinationCurrency == null) {
			return sourceAmount;
		}

		return (sourceAmount
			* self.reservoir.currencyListEx[date][destinationCurrency.toUpperCase()]['value']
			/ self.reservoir.currencyListEx[date][sourceCurrency.toUpperCase()]['value']).toFixed(2);
    },

    formatAmount = function (amount, numberOfDecimals) {
        numberOfDecimals = typeof numberOfDecimals !== 'undefined' ?  numberOfDecimals + 1 : 2;
        var formatted = amount.toFixed(numberOfDecimals).replace(/\d(?=(\d{3})+\.)/g, '$&,');

        // It's a javascript specific fix
        if (parseFloat(formatted) == 0 && formatted.indexOf('-') !== false) {
            formatted = formatted.replace('-', '');
        }

        if (numberOfDecimals == 1) {
            formatted = formatted.substring(0, formatted.length - 2)
        }

        return formatted;
    },

	getPoBalances = function() {
		var poList = self.reservoir.poList,
			poBalances = [];

		if (self.reservoir.poBalances == undefined) {
			if (poList.length) {
				for (var po in poList) {
					if (poList.hasOwnProperty(po)) {
						poBalances[poList[po].id] = {
							item_balance: poList[po].item_balance,
							limit: poList[po].limit
						}
					}
				}
			}

			self.reservoir.poBalances = poBalances;
		}

		return self.reservoir.poBalances;
	},

    showLimitAndPoItemBalancePlusCurrentItemAmount = function() {
        if ($('.po-list').length == 0 || $('.po-list').val() == "" || $('.amount') == "") {
            $('.show-expense-related-info').addClass('hidden');
            return false;
        }

	    var balances = getPoBalances(),
	        selectedValue = $('.po-list').val(),
	        poLimit = parseFloat(balances[selectedValue].limit),
		    poItemBalance = parseFloat(balances[selectedValue].item_balance);

        var today = $('.template.item').attr('data-date');
        var itemCurrency = $('.input-group-btn.custom-select.currency').attr('data-value');
        var poCurrency = $('.po-list').attr('data-po-currency-code');
        var itemAmountInItemCurrency = parseFloat($('.amount').val());
        var itemAmountInTicketCurrency = convertCurrency(today, itemAmountInItemCurrency, itemCurrency, poCurrency);
        var ItemAmountPlusTicketItemBalance = poLimit - parseFloat(itemAmountInTicketCurrency) - poItemBalance;

	    ItemAmountPlusTicketItemBalance = formatAmount(ItemAmountPlusTicketItemBalance, 0);
        poLimit = formatAmount(poLimit, 0);

        $('#po-limit-minus-po-item-balance-minus-this-item-amount').text(ItemAmountPlusTicketItemBalance);
        $('#po-limit').text(poLimit);
        $('#po-currency-code').text(poCurrency);
        $('.show-expense-related-info').removeClass('hidden');
    };

self.reservoir = {};

$(function() {
	var $data = $('#data'),
		$submit = $('.submit');

	// Load Resources
	$.when(
		getSubCategories($data.attr('data-sub-category-url')),
		getCurrencies($data.attr('data-currency-url'), [
			$data.attr('data-creation-date')
		])
	).then(function() {
        var isManager = parseInt($data.attr('data-able-to-approve'));

		// Draw Currency
		var $customSelect = $('.custom-select'),
			$category = $('.category'),
			$subCategory = $('.sub-category'),
			$costCenters = $('.item-cost-centers'),
			$moneyAccount = $('select.money-accounts'),
			$account = $('.account');

		$customSelect.each(function() {
			var $element = $(this),
				currencies = self.reservoir.currencyList;

			for (var i in currencies) {
				if (currencies.hasOwnProperty(i)) {
					$element.find('ul').append([
						'<li><a href="#" data-currency-id="', currencies[i].id, '" data-value="', currencies[i].code.toLowerCase(), '">', currencies[i].code.toUpperCase(), '</a></li>'
					].join(''));
				}
			}
		});

		// Setup daterangepicker
		$('.period').daterangepicker({
			format: 'YYYY-MM-DD',
			drops: 'up',
			locale: {
				firstDay: 1
			}
		});

		// Supplier
		$account.selectize({
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
					url: $data.attr('data-account-url'),
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
			persist: false,
			hideSelected: true,
			highlight: false
		});

		// Cost Center
		$costCenters.selectize({
			plugins: ['remove_button'],
			valueField: 'unique_id',
			searchField: ['name', 'label'],
			hideSelected: true,
			highlight: false,
			score: function() {
				return function(item) {
					return item.type * 1000 + item.id;
				};
			},
			render: {
				option: function (item, escape) {
					// Type definition: 1 - apartment, 2 - office, 3 - group
					var type = parseInt(item.type),
						label = (type == 1 ? 'primary' : 'success');

					return '<div><span class="label label-' + label + '">' + escape(item.label) + '</span> ' + escape(item.name) + '</div>';
				},
				item: function (item, escape) {
					// Type definition: 1 - apartment, 2 - office, 3 - group
					var type = parseInt(item.type),
						label = (type == 1 ? 'primary' : 'success');

					return '<div data-account="supplier" data-type="' + escape(type) + '" data-id="' + escape(item.id) + '" data-currency-id="' + escape(item.currency_id) + '"><span class="label label-' + label + '">' + escape(item.label) + '</span> ' + escape(item.name) + '</div>';
				}
			},
			load: function (query, callback) {
				if (query.length < 2) {
					return callback();
				}

				$.ajax({
					url: $data.attr('data-cost-center-url'),
					type: 'POST',
					data: {'q': encodeURIComponent(query)},
					error: function () {
						callback();
					},
					success: function (res) {
						callback(res.data);
					}
				});
			}
		});

		// Sub Category
		$subCategory.selectize({
			options: self.reservoir.subCategoryListSimple,
			onChange: function(value) {
				if (!value.length) {
					return;
				}

				$category[0].selectize.addItem(
					this.sifter.items[value].categoryId, true
				);
			}
		});

		// Category
		$category.selectize({
			options: self.reservoir.categoryList,
				onChange: function(value) {
				var subCategorySelectize = $subCategory[0].selectize;

				if (!value.length) {
					subCategorySelectize.disable();
					subCategorySelectize.clearOptions();

					self.reservoir.subCategoryListSimple.forEach(function(item, value) {
						subCategorySelectize.addOption(item);
					});

					subCategorySelectize.refreshOptions(false);
					subCategorySelectize.enable();

					return;
				}

				subCategorySelectize.disable();
				subCategorySelectize.clearOptions();

				for (var i in self.reservoir.subCategoryList[value]) {
					if (self.reservoir.subCategoryList[value].hasOwnProperty(i)) {
						subCategorySelectize.addOption(self.reservoir.subCategoryList[value][i]);
					}
				}

				subCategorySelectize.refreshOptions(false);
				subCategorySelectize.enable();
				subCategorySelectize.focus();
			}
		});

		var subCategoryValue = $subCategory.attr('data-sub-category-id'),
			uniqueId = $account.attr('data-unique-id'),
			accountId = $account.attr('data-account-id'),
			accountName = $account.attr('data-account-name'),
			accountType = $account.attr('data-account-type'),
			costCenters = $costCenters.attr('data-cost-centers');

		if (subCategoryValue) {
			$subCategory[0].selectize.setValue(subCategoryValue);
		}

		if (accountId && accountName && accountType) {
			var label = accountType == 5 ? 'People' : (accountType == 3 ? 'Partner' : 'External');

			$account[0].selectize.addOption({
				unique_id: uniqueId,
				account_id: accountId,
				type: accountType,
				name: accountName,
				label: label,
			});
			$account[0].selectize.setValue(uniqueId);
		}

		if (costCenters) {
			costCenters = JSON.parse(costCenters);

			if (costCenters && costCenters.length) {
				for (var cost in costCenters) {
					if (costCenters.hasOwnProperty(cost)) {
						$costCenters[0].selectize.addOption(costCenters[cost]);
						$costCenters[0].selectize.addItem(costCenters[cost]['unique_id'], true);
					}
				}
			}
		}
        if ($('.po-list').length) {
            self.reservoir.poList = jQuery.parseJSON($('.po-list').attr('data-po-list'));
        }

		// Attach Item to PO
		$('.po-list').selectize({
            valueField: 'id',
            labelField: 'title',
            sortField: 'title',
            searchField: ['title'],
            hideSelected: true,
            highlight: false,
            options: self.reservoir.poList,
			onItemAdd: function () {
				if (parseInt($data.attr('status')) != 4) {
					$submit.text($submit.attr('data-text-approval'));
				}
			},
			onItemRemove: function() {
				if (parseInt($data.attr('status')) != 4) {
					$submit.text($submit.attr('data-text-initial'));
				}
			},
			render: {
				option: function (item, escape) {
					return '<div><span class="label label-primary">' + escape(item.id) + '</span> ' + escape(item.title) + '</div>';
				},
				item: function (item, escape) {
                    $('.po-list').attr('data-po-limit', escape(item.limit)).attr('data-po-item-balance', escape(item.item_balance)).attr('data-po-currency-code', escape(item.code));
					return '<div><a class="label label-primary" href="/finance/purchase-order/ticket/' + escape(item.id) + '" target="_blank">' + escape(item.id) + '</a> ' + escape(item.title) + '</div>';
				}
			}
		});

		// Money Account
		$moneyAccount.selectize({
			valueField: 'id',
			labelField: 'name',
			sortField: 'name',
			searchField: ['name'],
			hideSelected: true,
			highlight: false,
			options: JSON.parse($data.attr('data-money-accounts')),
			render: {
				option: function(item, escape) {
					return '<div><span class="label label-primary">' + escape(item.currency) + '</span> ' + escape(item.name) + ' <small class="text-info">' + escape(item.bank_name) + '</small></div>';
				},
				item: function(item, escape) {
					return '<div data-id="' + escape(item.id) + '"><span class="label label-primary">' + escape(item.currency) + '</span> ' + escape(item.name) + ' <small class="text-info">' + escape(item.bank_name) + '</small></div>';
				}
			}
		});

		if ($moneyAccount.length && $moneyAccount.attr('data-id') != '') {
			$moneyAccount[0].selectize.addOption({
				id: $moneyAccount.attr('data-id'),
				name: $moneyAccount.attr('data-name'),
				bank_name: $moneyAccount.attr('data-bank'),
				currency: $moneyAccount.attr('data-currency'),
			});
			$moneyAccount[0].selectize.addItem($moneyAccount.attr('data-id'), true);
			$('.transaction-date').val($moneyAccount.attr('data-date'));
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
			required: function() {
				return ($('.type').val() === '0');
			}
		});

		if ($('.transaction-date').length) {
			$('.transaction-date').daterangepicker({
				'singleDatePicker': true,
				'format': globalDateFormat
			});

			$('.transaction-date').rules('add', {
				required: function() {
					return ($('.type').val() === '0');
				}
			});
		}

		if ($('select.money-accounts').length) {
			$('select.money-accounts').eq(
				$('select.money-accounts').length === 1 ? 0 : 1
			).rules('add', {
				required: function() {
					return ($('.type').val() === '0');
				}
			});
		}

		if ($('.item-comment').length) {
			$('.item-comment').rules('add', {
				required: true
			});
		}

		$('input.amount').rules('add', {
			required: true,
			amount: true,
			min: 0.01
		});

		$('select.type').rules('add', {
			required: true,
			number: [0, 2]
		});

        if (isManager) {
            $("select[name='account']").rules('add', {
                required: function() {
                    // no need to be required if the type is Order Expense
                    return ($('.type').val() !== '3');
                }
            });

            $("select[name='cost_centers[]']").rules('add', {
                required: true,
            });

            $("input[name='period']").rules('add', {
                required: true,
            });

            $("select[name='category']").rules('add', {
                required: true,
            });

            $("select[name='sub_category']").rules('add', {
                required: true,
            });
        }
	}).done(function() {
		var customSelect = $('.custom-select'),
			customSelectElement = customSelect.find('li a'),
			currency = customSelect.attr('data-value'),
			$type = $('.type'),
			isEdit = parseInt($data.attr('data-item-id')),
			isManager = parseInt($data.attr('data-able-to-approve'));

		// Type
		$type.selectize();

		if (isEdit) {
			$type[0].selectize.disable();
		}

		if (isManager && $('.transaction').length) {
			$('.money-accounts')[0].selectize.disable();
			$('.transaction-date').prop('disabled', true);
		}

		// Currency
		customSelectElement.on('click', function(e) {
			e.preventDefault();

			var $element = $(this).closest('.custom-select'),
				currencyId = $(this).attr('data-currency-id'),
				currencyText = $(this).text(),
				currencyValue = $(this).attr('data-value');

			$element.attr('data-value', currencyValue);
			$element.find('.display').text(currencyText);
			$element.find('.display').attr('data-currency-id', currencyId);
		});

		// Detect default currency
		if (currency) {
			customSelect.find('li a[data-currency-id="' + currency + '"]').trigger('click');
		} else {
			customSelectElement.eq(0).trigger('click');
		}
        showLimitAndPoItemBalancePlusCurrentItemAmount();
		// Submit button
		$('.submit').on('click', function(e) {
			e.preventDefault();

			var btn = $(this);

			if ($(this).attr('disabled') != 'disabled' && $('#item-form').valid()) {
				btn.button('loading');

				return $.ajax({
					url: $(this).attr('href'),
					data: prepareData(),
					type: 'POST',
					processData: false,
					contentType: false,
					error: function() {
						btn.button('reset');
						notification({
							status: 'error',
							msg: 'ERROR! Something went wrong (item save)'
						});
					},
					success: function(data) {
						if (data.status == 'success') {
							location.href = data['redirect-url'];
						} else {
							btn.button('reset');
							notification(data);
						}
					}
				});
			}
		});

		// Delete attachment
		$('.delete-item-attachment').on('click', function(e) {
			var btn 		 = $(this);
			var attachmentId = btn.data('id');

			btn.button('loading');

			return $.ajax({
				url: DELETE_ATTACHMENT_URL,
				data: {'attachmentId' : attachmentId},
				type: 'POST',
				success: function(data) {
					if (data.status == 'success') {
						location.reload();
					} else {
						btn.button('reset');
					}

					notification(data);
				}
			});
		});

		// Upload replacement
		$('.upload-item-attachment').on('click', function(e) {
			e.preventDefault();

			$(this).closest('.row').find('.items-attachments').trigger('click');
		});

		// Reject Item
		$('.item-reject').on('click', function(e) {
			e.preventDefault();

			var btn = $(this);

			btn.button('loading');

			return $.ajax({
				url: $(this).attr('href'),
				type: 'POST',
				error: function() {
					btn.button('reset');
					notification({
						status: 'error',
						msg: 'ERROR! Something went wrong (item reject)'
					});
				},
				success: function(data) {
					if (data.status == 'success') {
						location.reload();
					} else {
						btn.button('reset');
					}

					notification(data);
				}
			});
		});

		// Complete Item
		$('.item-complete').on('click', function(e) {
			e.preventDefault();

			var btn = $(this);

			btn.button('loading');

			return $.ajax({
				url: $(this).attr('href'),
				type: 'POST',
				error: function() {
					btn.button('reset');
					notification({
						status: 'error',
						msg: 'ERROR! Something went wrong (item complete)'
					});
				},
				success: function(data) {
					if (data.status == 'success') {
						location.reload();
					} else {
						btn.button('reset');
					}

					notification(data);
				}
			});
		});

        // Approve Item
        $('.item-approve').on('click', function(e) {
            e.preventDefault();

            var btn = $(this);

            btn.button('loading');

            return $.ajax({
                url: $(this).attr('href'),
                type: 'POST',
                error: function() {
                    btn.button('reset');
                    notification({
                        status: 'error',
                        msg: 'ERROR! Something went wrong (item approve)'
                    });
                },
                success: function(data) {
                    if (data.status == 'success') {
                        location.reload();
                    } else {
                        btn.button('reset');
                    }

                    notification(data);
                }
            });
        });

		// Remove Item
		$('.item-remove').on('click', function(e) {
			e.preventDefault();

			var btn = $(this);

			btn.button('loading');

			return $.ajax({
				url: $(this).attr('href'),
				type: 'POST',
				error: function() {
					btn.button('reset');
					notification({
						status: 'error',
						msg: 'ERROR! Something went wrong (item remove)'
					});
				},
				success: function(data) {
					if (data.status == 'success') {
						location.href = '/';
					} else {
						btn.button('reset');
						notification(data);
					}
				}
			});
		});

        $('.amount').keyup(function(){
            showLimitAndPoItemBalancePlusCurrentItemAmount();
        });
        $('.input-group-btn.currency').on('change', function (e) {
            showLimitAndPoItemBalancePlusCurrentItemAmount();
        });

        $('.custom-select a').on('click', function(e) {
            e.preventDefault();
            $('.input-group-btn.currency').trigger('change');
        });
		// Item type decided the action on edit
		$type.on('change', function() {
			var $transaction = $('.transaction'),
				$supporting = $('.supporting'),
				$transfer = $('.transfer'),
				$poList = $('.po-list'),
				$accessMessage = $('.access-message'),
				$submit = $('.submit'),
				isRejected = ($data.attr('data-status') === '3'),
				isCompleted = ($data.attr('data-status') === '4'),
				isApproved = ($data.attr('data-status') === '2'),
				isEdit = parseInt($data.attr('data-item-id'));

			$transaction.hide();
			$transfer.hide();
			$supporting.show();
			$accessMessage.show();

			(
				$accessMessage.length || (
					!EDITABLE
				)
			) ? $submit.attr('disabled', 'disabled') : $submit.removeAttr('disabled');

			// 0 - Declare an Expense
			// 1 - Request an Advance
			// 2 - Pay an Invoice
			switch ($(this).val()) {
				case '0':
					$submit.text($submit.attr('data-text-initial'));
					$transaction.show();

					break;
				case '1':
					$supporting.hide();
					$transfer.show();

					break;
				default:
					$submit.text($submit.attr('data-text-initial'));
					$accessMessage.hide();
					$accessMessage.length ? $submit.removeAttr('disabled') : null;
			}

			// Important because poId can be submitted with Request an Advance option which is not acceptable
			if ($(this).val() === '1' && $poList.length) {
				$poList[0].selectize.clear();
			}

			if (isManager) {
				if (!isCompleted && !isApproved) {
					$submit.text($submit.attr('data-text-approval'));
				}
			} else {
				if (isRejected) {
					$submit.text($submit.attr('data-text-resubmit'));
				}
			}

            if (isCompleted) {
                var $currencyBtn = $('.input-group-btn.currency button');
                var $uploadItemAttachment = $('a.upload-item-attachment');
                $currencyBtn.addClass('disabled').attr('disabled','disabled');
                $uploadItemAttachment.hide();
                $('.template.item select.selectized').each(function(){
                    var $_self = $(this);
                    $_self[0].selectize.disable();
                });
                $('.template.item input[type="text"]').addClass('disabled').attr('disabled','disabled');
                $('.template.item textarea').addClass('disabled').attr('disabled','disabled');
                $('.btn.submit').addClass('disabled');
            }

		}).trigger('change');

		$('.po-list').change(function () {
			$type.trigger('change');
            showLimitAndPoItemBalancePlusCurrentItemAmount();
		});

		$('.items-attachments').on('change', function(e) {
			var files = e.target.files,
				filename = 'Attach';

			if (files.length) {
				filename = files[0].name;

				if (filename.length > 20) {
					filename = $.trim(filename.substr(0, 10)) + '...' + $.trim(filename.substr(-8));
				}
			}

			$('.upload-item-attachment span').text(filename);
		});

        var $changeManagerButton =  $('#change-manager-button');
        if ($changeManagerButton.length > 0) {
            $changeManagerButton.click(function(event){
                event.preventDefault();
                var $modalChangeManager = $('#modal_change_manager');
                $modalChangeManager.modal('show');
            });
            var $btnChangeManagerSubmit = $('#btnChangeManagerSubmit');
            $btnChangeManagerSubmit.click(function(event){
                event.preventDefault();
                var newManagerId = $('#new_manager_id').val();
                var changeUrl = $(this).attr('href');
                return $.ajax({
                    url: changeUrl,
                    data: {
                        newManagerId: newManagerId
                    },
                    type: 'POST',
                    error: function() {
                        notification({
                            status: 'error',
                            msg: 'ERROR! Something went wrong'
                        });
                    },
                    success: function(data) {
                        if (data.status == 'success') {
                            window.location = "/";
                        } else {
                            notification(data);
                        }
                    }
                });
            });
        }
	});

    var checkedStatusesCount = $('#item-statuses :checked').length;
    checkAvailableChackboxesCount(checkedStatusesCount);

    $('#item-statuses').delegate('input', 'click', function (e) {
        if($(this).is(':checked')) {
            checkedStatusesCount++;
        } else {
            checkedStatusesCount--;
        }

        checkAvailableChackboxesCount(checkedStatusesCount);
    });
});

function checkAvailableChackboxesCount(checkedStatusesCount) {
    if (checkedStatusesCount > 1) {
        $('#item-statuses :checkbox:not(:checked)').attr('disabled', 'disabled');
    } else {
        $('#item-statuses :checkbox').attr('disabled', false);
    }
}
