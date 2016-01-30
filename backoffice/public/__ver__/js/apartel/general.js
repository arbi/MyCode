$(function() {
    $( "#save_button" ).click(function() {
        var form = $('#apartel_general').validate();
        if (form.valid()) {
            saveChanges();
        } else {
            validate.focusInvalid();
        }
    });

    // fiscal part start
    $('#fiscal-form').validate({
        ignore: [],
        rules: {
            'fiscal_name': {
                required: true
            },
            'channel_partner_id': {
                required: true,
                number: true,
                min: 1
            },
            'partner': {
                required: true,
                number: true,
                min: 1
            }
        },
        highlight: function (element, errorClass, validClass) {
            $(element).closest('.row').removeClass('has-success').addClass('has-error');
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).closest('.row').removeClass('has-error').addClass('has-success');
        },
        success: function (label) {
            $(label).closest('form').find('.valid').removeClass("invalid");
        },
        errorPlacement: function (error, element) {
            element.closest('.row').find('.help-block').html(error.text());
        }
    });

    var $partner = $('#partner');
    $partner.selectize({
        create: false,
        plugins: ['remove_button'],
        searchField: ['value', 'text'],
        valueField: 'value',
        labelField: 'text',
        sortField: [
            {
                field: 'text'
            }
        ]
    });
    $partner[0].selectize.clear();

    // add new fiscal
    $('.add-new-fiscal').click(function(e) {
        e.preventDefault();
        $('#fiscal_id').val(0);
        $('#fiscal_name').val('');
        $('#channel_partner_id').val('');
        $partner[0].selectize.clear();
        $('#fiscal-dialog').modal('show');
        $('.save-fiscal').text('Add Fiscal');
    });

    // edit fiscal
    $('.edit-fiscal').click(function(e) {
        e.preventDefault();
        $('#fiscal-dialog').modal('show');
        $('.save-fiscal').text('Edit Fiscal');

        $('#fiscal_name').val($(this).data('name'));
        $partner[0].selectize.addItem($(this).data('partner-id'), true);
        $('#fiscal_id').val($(this).data('id'));
        $('#channel_partner_id').val($(this).data('channel-partner-id'));
    });

    // delete fiscal
    $('.delete-item-fiscal').click(function(e) {
        e.preventDefault();
        $('.delete-fiscal').attr('href', GLOBAL_GENERAL_PATH + '/delete-fiscal/' + $(this).data('id'));
        $('#delete-dialog').modal('show');
    });

    // save fiscal data
    $('.save-fiscal').on('click', function () {
        var $formObj = $('#fiscal-form');
        var validate = $formObj.validate();
        if ($formObj.valid()) {
            var  btn = $('.save-fiscal');
            btn.button('loading');
            var $formData = $formObj.serializeArray();
            $.ajax({
                type: "POST",
                data: $formData,
                url: GLOBAL_FISCAL_SAVE,
                dataType: "json",
                success: function (data) {
                    if (data.status == 'success') {
                        location.reload();
                    } else {
                        notification(data);
                        btn.button('reset');
                    }
                }
            });
        } else {
            validate.focusInvalid();
        }
    });
    // fiscal end
});

function saveChanges()
{
    var btn = $('#save_button');
    btn.button('loading');

    var form = $('#apartel_general'),
        data = new FormData();

    $("form#apartel_general").serializeArray().forEach(function(field) {
        data.append(field.name, field.value);
    });

    $.ajax({
        type: "POST",
        url: GLOBAL_GENERAL_SAVE_PATH,
        data: data,
        processData: false,
        contentType: false,
        cache: false,
        success: function(data) {
            notification(data);
            btn.button('reset');
        }
    });


}