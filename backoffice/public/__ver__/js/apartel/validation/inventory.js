$(function(){
	$.validator.setDefaults({ ignore: ":hidden" });

	$('#inventory-range').validate({
		rules: {
			'date-range': {
				required: true
			}
		},
		highlight: function (element, errorClass, validClass) {
			$(element).parent().removeClass('has-success').addClass('has-error');
		},
		unhighlight: function (element, errorClass, validClass) {
			$(element).parent().removeClass('has-error').addClass('has-success');
		},
		success: function (label) {
			$(label).parent().find('.valid').removeClass("invalid");
		},
		errorPlacement: function (error, element) {
			element.parent().find('.help-block').html(error.text());
		}
	});
});
