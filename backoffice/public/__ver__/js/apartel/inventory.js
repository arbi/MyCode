if (!('contains' in String.prototype)) {
	String.prototype.contains = function(str, startIndex) {
		return -1 !== String.prototype.indexOf.call(this, str, startIndex);
	};
}

$(function() {
	var requestArray = {};

	$('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
		var target = $(e.target);
		if (target.attr('href').contains('#action-price')) {
			$('.dropdown-price-label').text(target.text());
		}

		if (target.attr('data-type') == 'percent') {
			$('.price-label').text('Percent');
		} else {
			$('.price-label').text('Price');
		}
	});

    var disabledDate = new Date();
    disabledDate.setDate(disabledDate.getDate()-2);

    $dateRangePickeroptions = {
	    ranges: {
		    'Today': [moment(), moment()],
		    'Next 7 Days': [moment(), moment().subtract(-6, 'days')],
		    'Next 30 Days': [moment(), moment().subtract(-29, 'days')],
		    'Until The End Of This Month': [moment(), moment().endOf('month')]
		},
		minDate: moment().subtract(1, 'days'),
		maxDate: moment().subtract(-1, 'years'),
		startDate: moment(),
		endDate: moment().subtract(-1, 'months'),
		format: 'YYYY-MM-DD'
	};

	$('#date-range').daterangepicker(
		$dateRangePickeroptions,
	    function(start, end) {
	        $('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
	    }
	);

    $('#date-range').val(localStorage.inventory_date_range);


    // update price
    var save_button = $('.inventory-range-save');
    save_button.click(function(e) {
        e.preventDefault();
        requestArray.force_update_price = 0;
        updatePrice();
    });

    // force update price
    var force_save_button = $('.force-change-price');
    force_save_button.click(function(e) {
        e.preventDefault();
        requestArray.force_update_price = 1;
        $('#forceUpdatePrice').modal('hide');
        updatePrice();
    });

    // update price by range
    function updatePrice() {
		save_button.button('loading');

		// Collect necessary data
		var type = null;
		var errors = 0;
		var weekdays = [
			$('#week-mon').is(':checked') ? 1 : 0,
			$('#week-tue').is(':checked') ? 1 : 0,
			$('#week-wed').is(':checked') ? 1 : 0,
			$('#week-thu').is(':checked') ? 1 : 0,
			$('#week-fri').is(':checked') ? 1 : 0,
			$('#week-sat').is(':checked') ? 1 : 0,
			$('#week-sun').is(':checked') ? 1 : 0
		];

		var date_range = $('#date-range').val();
        var amount = $('#price-amount').val();
        if (!$.isNumeric(amount)) {
            errors++;
        }

        var price_type = null;
        var lock_price = $('#lock-price').is(':checked') ? 1 : 0;
        var type = 'price';
        // "Price" dropdown is selected
        if ($('a[href="#action-price"]').parent().hasClass('active')) {
            // "Set Price by Amount" dropdown selected

            if ($('a[data-id="0"]').parent().hasClass('active')) {
                price_type = 0;
            } else if ($('a[data-id="1"]').parent().hasClass('active')) {
                price_type = 1;
            } else if ($('a[data-id="2"]').parent().hasClass('active')) {
                price_type = 2;
            } else if ($('a[data-id="3"]').parent().hasClass('active')) {
                price_type = 3;
            } else if ($('a[data-id="4"]').parent().hasClass('active')) {
                price_type = 4;
            }
        }

		// Validate inputs
		if (type === null) { alert('This is impossible'); errors++; }

		if (!$('#inventory-range').valid()) {
			errors++;
		}

		if (parseInt(weekdays.join('')) == 0) {
			errors++;
		}

        if (amount == '' || parseFloat(amount) < 0 || price_type === null) {
            errors++;
        }

        requestArray.parent_price = amount;
        requestArray.lock_price = lock_price;
        requestArray.price_type = price_type;

		requestArray.date_range = date_range;
		requestArray.week_days = weekdays;

		if (!errors) {
            updateDateRange(requestArray);
		} else {
			save_button.button('reset');
			notification({
				status: 'error',
				msg: 'Some necessary fields was not filled right.'
			});
		}
	}


	function updateDateRange(requestArray)
	{
		$.post(GLOBAL_UPDATE, requestArray).done(function(data) {
            localStorage.inventory_date_range = $('#date-range').val();

			if (data === undefined) {
				return;
			}

            if (data.status == 'limit_exceed') {
                $('#forceUpdatePrice').modal('show');
                return;
            }

			notification(data);

		}).always(function() {
			save_button.button('reset');
		});
	}
});
