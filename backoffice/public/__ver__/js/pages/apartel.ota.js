$(function() {
    $('#ota-form').validate({
        rules: {
             ota_name:{
                required: true,
                digits: true,
                min: 1
             },
             ota_ref:{
                required: true
             },
             ota_url:{
                required: true,
                url: true
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
        errorPlacement: function (error, element) {
        }
    });

    $('#ota-button').click(function(e) {
        var validate = $('#ota-form').validate();
        if ($('#ota-form').valid()) {
            var btn = $('#ota-button');
            btn.button('loading');
            var url = $('#ota-form').attr('action');
            var obj = $('#ota-form').serializeArray();
            $.ajax({
                type: "POST",
                url: url,
                data: obj,
                dataType: "json",
                success: function(data) {
                    if(data.status == 'success'){
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
