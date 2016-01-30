$(function() {
    $('#ota-form').validate({
        rules: {
             ota_name: {
                required: true,
                digits: true,
                min: 1
             },
             ota_ref: {
                required: true
             },
             ota_url: {
                required: true,
                url: true
             }
        },
        highlight: function (element, errorClass, validClass) {
            $(element).closest('.control-group').removeClass('success').addClass('error');
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).closest('.control-group').removeClass('error').addClass('success');
        },
        success: function (label) {
            $(label).closest('form').find('.valid').removeClass("invalid");
        },
        errorPlacement: function (error, element) {
            element.closest('.control-group').find('.help-block').html(error.text());
        }
    });

    $('#ota-button').click(function(e) {
	    e.preventDefault();

        var validate = $('#ota-form').validate();

        if ($('#ota-form').valid()) {
            var btn = $('#ota-button'),
	            url = $('#ota-form').attr('action'),
	            obj = $('#ota-form').serializeArray();

	        btn.button('loading');

            $.ajax({
                type: "POST",
                url: url,
                data: obj,
                dataType: "json",
                success: function(data) {
                    if (data.status == 'success') {
                        location.reload();
                    }

                    notification(data);
                    btn.button('reset');
               }
            });
        } else {
            validate.focusInvalid();
        }
    });

	$('.check-button').click(function(e) {
		e.preventDefault();

		var btn = $(this);
		btn.button('loading');

		$.ajax({
			type: "POST",
			url: $(this).attr('data-url'),
			dataType: "json",
			success: function(data) {
				if (data.status == 'success') {
					window.location.reload();
				} else {
					notification(data);
					btn.button('reset');
				}
			}
		});
	});

    $(".delete-ota").click(function() {
        $('#OTADeleteModal').modal();
        var url = $(this).attr('data-url');
        $('#deleteOTAButton').prop('href', url);
    });
});
