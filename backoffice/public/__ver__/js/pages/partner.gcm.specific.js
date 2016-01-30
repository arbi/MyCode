$(function() {
    // temporary form validation
    $('#partner-gcm-value-add-form').validate({
        rules: {
            'keys[]': {
                required: true
            },
            'values[]': {
                required: true
            }
        },
        highlight: function (element, errorClass, validClass) {
            $(element).closest('.form-control').removeClass('has-success').addClass('has-error');
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).closest('.form-control').removeClass('has-error').addClass('has-success');
        },
        success: function (label) {
            $(label).closest('form').find('.valid').removeClass("invalid");
        },
        errorPlacement: function(error, element) {
        }
    });

    // actual form validation
    $('#partner-gcm-value-form').validate({
        rules: {
            'keys[]': {
                required: true
            },
            'values[]': {
                required: true
            }
        },
        highlight: function (element, errorClass, validClass) {
            $(element).closest('.form-control').removeClass('has-success').addClass('has-error');
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).closest('.form-control').removeClass('has-error').addClass('has-success');
        },
        success: function (label) {
            $(label).closest('form').find('.valid').removeClass("invalid");
        },
        errorPlacement: function(error, element) {
        }
    });

    // add value
    $("#add-value").click(function() {
        if($('#partner-gcm-value-add-form').valid()) {
            var $tr       = $(this).closest('tr');
            var $gcmKey   = $tr.find('#add-partner-gcm-key');
            var $gcmValue = $tr.find('#add-partner-gcm-value');

            var inputFormKey = '<div class="input-prepend input-append form-group margin-0">' +
                                    '<div class="col-sm-12">' +
                                        '<input name="keys[]" type="text" class="form-control" maxlength="100" value="' +
                                            $gcmKey.val() +
                                    '" data-id="" />' +
                                    '</div>' +
                                '</div>';

            var inputFormValue = '<div class="input-prepend input-append form-group margin-0" id="description_0">' +
                                    '<div class="col-sm-12">' +
                                        '<input name="values[]" type="text" class="form-control" value="' +
                                            $gcmValue.val() +
                                    '" data-id="" />' +
                                    '</div>' +
                                '</div>';


            var html = '<tr>\
                            <td>' + inputFormKey + '</td>\
                            <td>' + inputFormValue + '</td>\
                            <td>\
                                <a href="javascript:void(0)" class="btn btn-sm btn-danger btn-block deletePartnerValue">Delete</a>\
                            </td>\
                        </tr>';
            $('#values-list').prepend(html);

            $gcmKey.val('');
            $gcmValue.val('');
        }
    });

    // Submit form
    $('#submit-values').on('click', function(){
        var form = $('#partner-gcm-value-form');
        $(this).button('loading');
        if(form.valid()) {
            var data = form.serializeArray();
            saveData(data);
        } else {
            $(this).button('reset');
        }
    });

});

// delete partner value
$(document).on('click', '.deletePartnerValue', function(){
    var valueRow = $(this).closest('tr');
    valueRow.remove();
});

/**
 * Save data
 *
 * @param data
 */
function saveData(data) {
    $.ajax({
        type: "POST",
        url: GENERAL_SAVE_GCM_VALUES,
        data: data,
        dataType: "json",
        success: function(data) {
            notification(data);
            $("#submit-values").button('reset');
        }
    });
}
