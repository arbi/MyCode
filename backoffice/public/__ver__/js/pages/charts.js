$(function() {
	var chargeFilter = $('.charge-filter'),
		transactionFilter = $('.transaction-filter');

	$('.checkbox-trigger').each(function() {
		if (parseInt($(this).val()) == 1) {
			$('#' + $(this).attr('data-id'))
				.attr('data-status', 1)
				.addClass('active');
		} else {
			$('#' + $(this).attr('data-id'))
				.attr('data-status', 10)
				.removeClass('active');
		}
	});

	$('.checkbox-cover').click(function(e) {
		e.preventDefault();

		var status = $(this).attr('data-status');

		if (parseInt(status) == 1) {
			$(this)
				.attr('data-status', 0)
				.removeClass('active');
		} else {
			$(this)
				.attr('data-status', 1)
				.addClass('active');
		}

		$('.checkbox-trigger[data-id=' + $(this).attr('id') + ']')
			.val($(this).attr('data-status'));
	});

	if (jQuery().daterangepicker) {
		var dateRangePickerOptions = {
			ranges: {
				'Today': [moment(), moment()],
				'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
				'Last 7 Days': [moment().subtract(6, 'days'), moment()],
				'Last 30 Days': [moment().subtract(29, 'days'), moment()],
				'This Month': [moment().startOf('month'), moment().endOf('month')],
				'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
			},
			startDate: moment().subtract(29, 'days'),
			endDate: moment(),
			format: 'YYYY-MM-DD'
		};

		$('.daterange').daterangepicker(dateRangePickerOptions, function(start, end) {
		    $(this).val(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
	    });
	}

	$('.charge-search').on('click', function(e) {
		e.preventDefault();

		var self = $(this);

		self.button('loading');

		$.ajax({
			type: "POST",
			url: $(this).attr('href'),
			data: $('#charge').serializeArray(),
			dataType: "json",
			success: function(data) {
				if (data.status == 'success') {
					Object.size = function(obj) {
						var size = 0,
							key;

						for (key in obj) {
							if (obj.hasOwnProperty(key)) {
								size++;
							}
						}

						return size;
					};

					var data = data.data;

					if (Object.size(data)) {
						var amountData = [],
							countData = [];

						for (var index in data) {
							if (data.hasOwnProperty(index)) {
								amountData.push({
									name: data[index]['addon'],
									data: [data[index]['amount']]
								});
								countData.push({
									name: data[index]['addon'],
									data: [data[index]['count']]
								});
							}
						}

						$('#amount').highcharts({
							chart: {
								type: 'column'
							},
							title: {
								text: 'Charges'
							},
							xAxis: {
								categories: ['Charges']
							},
							yAxis: {
								title: {
									text: 'Amount (EUR)'
								}
							},
							series: amountData
						});

						$('#count').highcharts({
							chart: {
								type: 'column'
							},
							title: {
								text: 'Charge Count'
							},
							xAxis: {
								categories: ['Charge Count']
							},
							yAxis: {
								title: {
									text: 'Count'
								}
							},
							series: countData
						});
					} else {
						notification({
							status: 'error',
							msg: 'No Charges found for that case.'
						});
					}
				} else {
					notification(data);
				}

				self.button('reset');
			}
		});
	});

	$('.transaction-search').on('click', function(e) {
		e.preventDefault();

		$.ajax({
			type: "POST",
			url: $(this).attr('href'),
			data: $('#transaction').serializeArray(),
			dataType: "json",
			success: function(data) {
				if (data.status == 'success') {
					Object.size = function(obj) {
						var size = 0,
							key;

						for (key in obj) {
							if (obj.hasOwnProperty(key)) {
								size++;
							}
						}

						return size;
					};

					data = data.data;

					if (Object.size(data)) {
						var sortedData = [];

						for (var index in data) {
							if (data.hasOwnProperty(index)) {
								sortedData.push({
									name: data[index]['type'],
									data: [data[index]['amount']]
								});
							}
						}

						$('#amount').highcharts({
							chart: {
								type: 'column'
							},
							title: {
								text: 'Transactions'
							},
							xAxis: {
								categories: ['Transactions']
							},
							yAxis: {
								title: {
									text: 'Amount (EUR)'
								}
							},
							series: sortedData
						});
					} else {
						notification({
							status: 'error',
							msg: 'No Charges found for that case.'
						});
					}
				} else {
					notification(data);
				}
			}
		});
	});

	// Filters
	$(".apt-filter-btn").click(function() {
		var prod = $(this).parent().prev(),
			prod_id = $(this).parent().parent().next(),
			prod_type = prod_id.next(),
			span = $(this).children("span.hide");

		prod_id.val(0);
		prod.val('');

		$(this).children("span").toggleClass("hide");

		if (span.hasClass("glyphicon-filter")) {
			prod_type.attr("value", 1);
		} else {
			prod_type.attr("value", 0);
		}
	});

	$("#product").keyup(function() {
		if ($(this).val().length >= 3) {
			$("#product").autocomplete({
				source: function(request, response) {
					$.ajax({
						url: $('.charge-filter').attr('data-product-url'),
						data: {
							txt: $("#product").val(),
							mode: $("#product_type").val()
						},
						dataType: "json",
						type: "POST",
						success: function(data) {
							var obj = [];

							if (data && data.rc == '00') {
								for (var row in data.result) {
									var item = data.result[row],
										new_obj = {};

									new_obj.value = item.name;
									new_obj.id = item.id;
									obj.push(new_obj);
								}
							}

							response(obj);
						}
					});
				},
				max: 10,
				minLength: 1,
				autoFocus: true,
				select: function(event, ui) {
					if (ui.item) {
						$('#product_id').val(ui.item.id);
					}
				},
				search: function(event, ui) {
					$('#product_id').val('');
				},
				focus: function(event, ui) {
					event.preventDefault();
				}
			});
		}
	});

	$("#assigned_product").keyup(function() {
		if ($(this).val().length >= 3) {
			$("#assigned_product").autocomplete({
				source: function(request, response) {
					$.ajax({
						url: $('.charge-filter').attr('data-product-url'),
						data: {
							txt: $("#assigned_product").val(),
							mode: $("#assigned_product_type").val()
						},
						dataType: "json",
						type: "POST",
						success: function(data) {
							var obj = [];

							if (data && data.rc == '00') {
								for (var row in data.result) {
									var item = data.result[row],
										new_obj = {};

									new_obj.value = item.name;
									new_obj.id = item.id;
									obj.push(new_obj);
								}
							}

							response(obj);
						}
					});
				},
				max: 10,
				minLength: 1,
				autoFocus: true,
				select: function(event, ui) {
					if (ui.item) {
						$('#assigned_product_id').val(ui.item.id);
					}
				},
				search: function(event, ui) {
					$('#assigned_product_id').val('');
				},
				focus: function(event, ui) {
					event.preventDefault();
				}
			});
		}
	});

	// Clear Filter
	$('.filter-reset').click(function(e) {
		e.preventDefault();

		var context = $(this).closest('form').attr('name'),
			filter;

		if (context == 'charge') {
			filter = chargeFilter;
		} else if (context == 'transaction') {
			filter = transactionFilter;
		}

		filter.find('input').each(function(index, item) {
			$(item).val('');
		});

		filter.find('select').each(function(index, item) {
			$(item).val(
				$(item).find('option:first').val()
			);
		});

		$('#status').val(1);
	});

	// Search on [Enter]
	$('#charge').keypress(function (e) {
		if (e.which == 13) {
			$('.transaction-search').trigger('click');
		}
	});

	$('#transaction').keypress(function (e) {
		if (e.which == 13) {
			$('.transaction-search').trigger('click');
		}
	});

	// Download CSV
	$("#btn_download_charge_filtered_csv").click(function(e) {
		e.preventDefault();

		window.location = $(this).attr('href') + '?' + $('#charge').serialize();
	});


	$("#btn_download_transaction_filtered_csv").click(function(e) {
		e.preventDefault();

		window.location = $(this).attr('href') + '?' + $('#transaction').serialize();
	});
});
