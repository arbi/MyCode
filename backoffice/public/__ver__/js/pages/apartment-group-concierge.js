$(function() {
    $('#form_apartment_group_concierge').validate({
        rules: {
            concierge_email: {
                required: false,
                email: true
            }
        },
        messages: {
            name: {
                remote: "Group Name is in use"
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
        errorPlacement: function(error, element) {}
    });
});

$("#concierge_email").keypress(function (e) {
    if (e.which == 13) {
        $( "#save_button" ).trigger('click');
        e.preventDefault();
    }
});

state('save_button', function () {
    var validate = $('#form_apartment_group_concierge').validate();

    if ($('#form_apartment_group_concierge').valid()) {
        var btn = $('#save_button'),
            obj = $('#form_apartment_group_concierge').serialize();
            obj += "&id=" + APARTMENT_GROUP_ID;

        btn.button('loading');

        $.ajax({
            type: "POST",
            url: GLOBAL_SAVE_DATA,
            data: obj,
            dataType: "json",
            success: function (data) {
                if (data.status == 'success') {
                    if (parseInt(data.id) > 0) {
                        window.location.href = GLOBAL_BASE_PATH + 'concierge/edit/' + data.id + '/concierge';
                    } else {
                        notification(data);
                    }
                } else {
                    notification(data);
                }

                btn.button('reset');
            }
        });
    } else {
        validate.focusInvalid();
    }
});

