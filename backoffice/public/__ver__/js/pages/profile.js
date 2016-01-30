$(document).ready(function() {
    $('#avatar').on('click', function(){
        $('#avatarFile').trigger('click');
    });

    $('#birthday').daterangepicker({
        'singleDatePicker': true,
        'format': globalDateFormat
    });

    $('#avatarFile').on("change", function()
    {
        var oldAvatar = $('#avatar').attr('src');
        $('#avatar').attr({
            'src' : VERSION_PUBLIC_PATH+'img/loading-blue.gif',
            'alt' : 'Uploading...'
        });

        var data = new FormData();
        data.append( 'userId', $('#userId').attr('value') );
        data.append( 'file', $('#avatarFile')[0].files[0] );

        $.ajax({
            url: $("#avatarForm").attr('action'),
            type: "POST",
            data: data,
            contentType: false,
            processData: false,
            cache: false,
            success: function(data){
                 if (data.status == 'error') {
                    notification(data);
                    $('#avatar').attr({
                        'src' : oldAvatar,
                        'alt' : ''
                    });
                }
                else{
                    notification(data);
                    $("#avatar").attr('src','//'+IMG_DOMAIN_NAME+'/profile/'+data.src);
                    $("#avatar").attr('data-toggle','tooltip');
                    $("#avatar").attr('data-original-title','Click to change profile image');
                }
            }
        });
    });

    state('change_password', function(e) {
        e.preventDefault();
        var validate = $('#changePasswordForm').validate();
        var obj = $('#changePasswordForm').serializeArray();
        if ($('#changePasswordForm').valid()) {
             $.ajax({
                type: "POST",
                url: GLOBAL_CHANGE_PASSWORD,
                data: obj,
                dataType: "json",
                cache: false,
                success: function(data){
                    if (data.status == 'success') {
                        location.reload();
                    }
                    else{
                        notification(data);
                    }
                }
            });
        } else {
            validate.focusInvalid();
        }
    });

    state('change_details', function(e) {
        e.preventDefault();
        var validate = $('#changeDetailsForm').validate();
        var obj = $('#changeDetailsForm').serializeArray();
        if ($('#changeDetailsForm').valid()) {
             $.ajax({
                type: "POST",
                url: GLOBAL_CHANGE_DETAILS,
                data: obj,
                dataType: "json",
                cache: false,
                success: function(data){
                    if (data.status == 'success') {
                        location.reload();
                    }
                    else{
                        notification(data);
                    }
                }
            });
        } else {
            validate.focusInvalid();
        }
    });

	$('.collapsed').hide();

	$('.td-more').click(function(e) {
		e.preventDefault();

		$('.collapsed').show('fast');
		$(this).hide();
	})

    $('#ginocoin-form').validate({
        onfocusout: false,
        rules: {
            'ginocoin-amount': {
                required: true,
                number: true
            },
            'ginocoin-pin': {
                required: true,
                number: true,
            }
        },
        invalidHandler: function(form, validator) {
            var errors = validator.numberOfInvalids();

            if (errors) {
                validator.errorList[0].element.focus();
            }
        },
        highlight: function (element, errorClass, validClass) {
            if ($(element).prop('tagName') == 'TEXTAREA') {
                $(element).addClass('has-error');
            } else {
                $(element).parent().addClass('has-error');
            }
        },
        unhighlight: function (element, errorClass, validClass) {
            if ($(element).prop('tagName') == 'TEXTAREA') {
                $(element).removeClass('has-error');
            } else {
                $(element).parent().removeClass('has-error');
            }
        },
        success: function (label) {
            $(label).closest('form').find('.valid').removeClass('invalid');
        },
        errorPlacement: function (error, element) {
            // do nothing
        }
    });

    $('#save-ginocoin').on('click', function (e) {
        e.preventDefault();

        var btn = $('#save-ginocoin');
        btn.button('loading');

        if ($('#ginocoin-form').valid()) {
            $.ajax({
                type: "POST",
                url: GLOBAL_SAVE_GINOCOIN,
                data: {
                    amount: $('#ginocoin-amount').val(),
                    pin:    $('#ginocoin-pin').val()
                },
                dataType: "json",
                cache: false,
                success: function (data) {
                    if (data.status == 'success') {
                        $('#ginocoinModal').modal('hide');
                    }

                    notification(data);
                }
            });
        }

        btn.button('reset');
    });
});

function vacationsRequset(id, val, objcet){
    var btn = $(objcet);
    btn.button('loading');
    var obj = {value:val, id:id};
    $.ajax({
        type: "POST",
        url: GLOBAL_VACATION,
        data: obj,
        dataType: "json",
        success: function(data) {
            if(data.status == 'success'){
                location.reload();
            } else {
                btn.button('reset');
            }
        }
    });
}
