$(function() {
    var form = $('#venue-charge');

    if (form.length > 0) {
        form.validate({
            ignore: '',
            rules: {
                'status_id': {
                    required: true
                },
                'order_status_id': {
                    required: true
                },
                'amount': {
                    required: true,
                    number: true,
                },
                'charged_user_id': {
                    required: true,
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
            errorPlacement: function (error, element) {}
        });
    }

    // Charged User
    var $chargedUser = $('#charged_user_id');

    $chargedUser.selectize({
        preload: true,
        maxItems: 1,
        create: false,
        valueField: 'id',
        searchField: 'text',
        sortField: [
            {field: 'text', direction: 'asc'}
        ],
        render: {
            option: function(option, escape) {
                return '<div>'
                    + '<span> ' + escape(option.text) + ' </span>'
                    + '</div>';
            },
            item: function(option, escape) {
                return '<div>'
                    + '<span> ' + escape(option.text) + ' </span>'
                    + '</div>';
            }
        }
    });

    if ($chargedUser.attr('data-id') == '') {
        $chargedUser[0].selectize.clear();
    } else {
        $chargedUser[0].selectize.setValue($chargedUser.attr('data-id'));
    }

    // Status
    var $status = $('#status_id');
    $status.selectize();
    if ($status.attr('data-id') == '') {
        $status[0].selectize.clear();
    } else {
        $status[0].selectize.setValue($status.attr('data-id'));
    }

    // Order Status
    var $orderStatus = $('#order_status_id');
    $orderStatus.selectize();
    if ($orderStatus.attr('data-id') == '') {
        $orderStatus[0].selectize.clear();
    } else {
        $orderStatus[0].selectize.setValue($orderStatus.attr('data-id'));
    }

    $('#save_charge').on('click', function(e) {
        e.preventDefault();

        if (form.valid()) {
            var btn = $(this);
            var formData = form.serialize();

            btn.button('loading');

            $.ajax({
                type: "POST",
                url: GLOBAL_VENUE_CHARGE_SAVE_URI,
                data: formData,
                dataType: "json",
                success: function(data) {
                    if(data.status == 'success' && data.url != ''){
                        window.location.href = data.url;
                    }

                    btn.button('reset');
                    notification(data);
                }
            });
        }
    });

    $('#archive-unarchive').click(function(){
        $('#is_archived').click();
        $('#save_charge').trigger('click');
    })
});