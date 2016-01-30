	$('.startup').each(function() {
		var spend = $('<span class="spend"></span>').appendTo($(this)),
			data_currency_sign = $(this).attr('data-currency-sign'),
			data_spend_cost = $(this).attr('data-spend-cost'),
			data_spend = $(this).attr('data-spend'),
            data_cost_overall = $(this).attr('data-cost-overall');

        if (data_spend >= 80 && data_spend < 100) {
            spend.addClass('bg-warning');
        }

        if (parseFloat(data_spend_cost) > parseFloat(data_cost_overall)) {
            spend.addClass('bg-danger');
            data_spend = 100;

        }

		spend.html('<span>' + data_currency_sign + data_spend_cost + '</span>');
		spend.animate({
			width: data_spend + '%'
		}, 500);
	});

	$('.running').each(function() {
		var provided_container = $('<span class="provided-container"></span>').appendTo($(this)),
			provided = $('<span class="provided"></span>').appendTo($(this)),
			spend = $('<span class="spend"></span>').appendTo($(this)),
			data_currency_sign = $(this).attr('data-currency-sign'),
			data_spend_cost = $(this).attr('data-spend-cost'),
			data_provided = $(this).attr('data-provided'),
			data_spend = $(this).attr('data-spend'),
            data_cost_overall = $(this).attr('data-cost-overall');

		provided.css('width', data_provided + '%');
		provided_container.css('width', data_provided + '%');
		spend.html('<span>' + data_currency_sign + data_spend_cost + '</span>');

        if (data_spend >= data_provided) {
            spend.addClass('bg-warning');
        }
        
        if (parseFloat(data_spend_cost) > parseFloat(data_cost_overall)) {
            spend.addClass('bg-danger');
            data_spend = 100;
        }

		spend.animate({
			width: data_spend + '%'
		}, 500);
	});