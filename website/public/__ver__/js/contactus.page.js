$(function() {
	var ajaxUrl = $('#contact-us-form').attr('action');

    $('#submit').click(function() {
        var dataForm = $('#contact-us-form').serialize();

        $.ajax({
            type: "POST",
            url: ajaxUrl,
            data: dataForm,
            success: function(data) {
                if (data.status == 'success') {
                    $('#form-div').slideUp();
                    $('#thank-you-div').slideDown();
                    $('#thank-you-div').removeClass('hidden');
                } else {
                    $('#error-div').slideDown();
                    $('#error-div').removeClass('hidden');
                }
            }
        });
    });
});
