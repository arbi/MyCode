$(function() {
    $('#budget-form').validate({
        rules: {
            name: {
                required: true
            },
            category: {
                required: true,
                number: true,
                min: 1
            },
            status: {
                required: true,
                number: true,
                min: 1
            },
            period: {
                required: true
            },
            amount: {
                required: true,
                number: true
            },
            description: {
                required: true
            },
            department_id: {
                required: function() {
	                return !$('#is_global').is(':checked');
                },
                number: true,
                min: 1
            },
            country_id: {
                required: false,
                number: true
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

    if (jQuery().daterangepicker) {
        $('#period').daterangepicker({
	        ranges: {
		        'Today': [moment(), moment()],
		        'Next 7 Days': [moment(), moment().subtract(-6, 'days')],
		        'Next 30 Days': [moment(), moment().subtract(-29, 'days')],
		        'Until The End Of This Month': [moment(), moment().endOf('month')]
	        },
	        //minDate: moment().subtract(1, 'days'),
	        maxDate: moment().subtract(-1, 'years'),
	        startDate: moment(),
	        endDate: moment().subtract(-1, 'months'),
	        format: globalDateFormat
        });
    }

    if (typeof DISABLE_FORM != 'undefined' && DISABLE_FORM == 'yes') {
        $("#budget-form :input").attr("disabled", true);
        $(".page-actions a").hide();
    }

	$('#is_global').change(function() {
		if ($(this).is(':checked')) {
			$('#department_id').val(-1);
			$('#department_id').closest('.form-group').hide('fast');
		} else {
			$('#department_id').closest('.form-group').show('fast');
		}
	}).trigger('change');
});
