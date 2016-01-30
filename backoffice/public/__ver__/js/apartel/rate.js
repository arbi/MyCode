$( function() {
    $("input[type=\"radio\"]").parent().addClass('radio');

	$( "input[name='penalty_type']" ).change(function() {
		var radioValue = $("input[name='penalty_type']:checked").val();

		switch (parseInt(radioValue)) {
			case 1:
				$("#penalty_percent_row").css("visibility", "visible");
				$("#penalty_amount_row").css("visibility", "hidden");
				$("#penalty_night_row").css("visibility", "hidden");
				break;
			case 2:
				$("#penalty_percent_row").css("visibility", "hidden");
				$("#penalty_amount_row").css("visibility", "visible");
				$("#penalty_night_row").css("visibility", "hidden");
				break;
			case 3:
				$("#penalty_percent_row").css("visibility", "hidden");
				$("#penalty_amount_row").css("visibility", "hidden");
				$("#penalty_night_row").css("visibility", "visible");
				break;
            default :
                $("#penalty_percent_row").css("visibility", "hidden");
                $("#penalty_amount_row").css("visibility", "hidden");
                $("#penalty_night_row").css("visibility", "hidden");
                break;
		}
	});

	$( "input[name='is_refundable']" ).change(function() {
		var radioValue = $("input[name='is_refundable']:checked").val();

		switch (parseInt(radioValue)) {
			case 2:
				$("#refundable_options").hide();
				break;
			case 1:
				$("#refundable_options").show();
				break;
		}
	});

	$( "input[name='is_refundable']" ).change();
	$( "input[name='penalty_type']" ).change();

	var prices = $('#week_price, #weekend_price, #penalty_fixed_amount');
	prices.blur(function() {
		if ($(this).valid()) {
			var val = $(this).val();

			if (val != '') {
				val = parseFloat(val);
				val = val.toFixed(2);

				$(this).val(val);
			}
		}
	});

    $('#save_button').click(function() {
        var btn = $('#save_button');
        btn.button('loading');
        if($('#apartel-rate').valid()) {
            $('#apartel-rate').submit();
        } else {
            btn.button('reset');
        }
    });

    if($('#refundable_before_hours').length > 0) {
        $('#refundable_before_hours').change(function(){
            $('#release_window_start').val(this.value/24);
        });
    }

    if($('.plus-minus-switcher').length) {
        $('.plus-minus-switcher').click(function(){
            var $this = $(this),
                symbol = $this.closest('.input-group-btn').find('.plus-minus-symbol');
            if (parseInt(symbol.val()) > 0) {
                $this.removeClass('btn-success');
                $this.addClass('btn-danger');
                $this.find('span').removeClass('glyphicon-plus');
                $this.find('span').addClass('glyphicon-minus');
                symbol.val(-1);
            } else {
                $this.removeClass('btn-danger');
                $this.addClass('btn-success');
                $this.find('span').removeClass('glyphicon-minus');
                $this.find('span').addClass('glyphicon-plus');
                symbol.val(1);
            }

            $('.current-rate-price').trigger('input');
        });
    }

    if (GLOBAL_IS_PARENT == 0) {
        $('.current-rate-price').on('input', function() {
            var symbol = $(this).closest('.input-group').find('.plus-minus-symbol'), viewPrice;
            var price = $(this).attr('id') == 'weekend_price' ? GLOBAL_PARENT_WEEKEND_PRICE : GLOBAL_PARENT_WEEK_PRICE;
            if (symbol.val() > 0) {
                viewPrice = parseFloat(price) + parseFloat(price * this.value / 100);
            } else {
                viewPrice = parseFloat(price) - parseFloat(price * this.value / 100);
            }
            viewPrice = viewPrice > 0 ? precise_round(viewPrice) : 0;
            $(this).closest('.form-group').find('.current-rate-price-view').html(viewPrice);
        });
    }

});
