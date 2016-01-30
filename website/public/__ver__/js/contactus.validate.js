var fn = function() {
    if ($('#contact-us-form').valid()) {
        $('#submit').removeAttr('disabled');
    } else {
        $('#submit').attr('disabled', 'disabled');
    }
};

$.validator.setDefaults({
	highlight: function(element) {
		$(element).parent().addClass('has-error');
	},
	unhighlight: function(element) {
		$(element).parent().removeClass('has-error');
	},
	errorElement: 'span',
	errorClass: 'help-block',
	errorPlacement: function(error, element) {},
	onfocusout: fn,
	onkeyup: fn
});


$(function() {
	$("#contact-us-form").validate({
		rules: {
			"name": {
				required: true
			},
			"email": {
				required: true,
				email: true
			},
			"remarks": {
				required: true
			}
		}
	});
});
