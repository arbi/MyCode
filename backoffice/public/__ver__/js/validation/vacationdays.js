$(function(){
    $.validator.addMethod("dateRange", 
        function(value, element) {
            return /^[0-9]{4}-[0-9]{2}-[0-9]{2}\s-\s[0-9]{4}-[0-9]{2}-[0-9]{2}$/.test(value);
        }, 
        "Invalid date range format."
    );

	/**
	 * Validation for start and end date intervals
	 *
	 * End date must be larger than start date
	 */
	$.validator.addMethod("checkInterval",
		function(value) {
			var parsed = value.split(" - ");

			var startDate = new Date(parsed[0]);
			var endDate   = new Date(parsed[1]);

			var endToStartDiff = endDate.getTime() - startDate.getTime();
			var endToStartDiffByDays = Math.ceil(endToStartDiff / (1000 * 3600 * 24));

			if (endToStartDiffByDays >= 0) {
				return true;
			}

			return false;
		},
		"Invalid date range format."
	);

	$.validator.addMethod("amount", function(value, element) {
		return this.optional(element) || /^[0-9]+(\.[0-9]{1,2})?$/i.test(value);
	}, "Amount is invalid");

	$('#vacationdays-form').validate({
		rules: {
			vacation_type: {
                digits: true,
                required: true
			},
			interval: {
				dateRange: true,
				checkInterval: true,
                required: true
			},
			total_number: {
				number: true,
                required: true,
				min: 0.1
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
		errorPlacement: function() {}
	});
});
