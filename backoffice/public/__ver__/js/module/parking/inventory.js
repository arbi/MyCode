$(function () {
    $lotsId = $('#lots');
    $lotsId.selectize({
        create: false,
        plugins: ['remove_button'],
        valueField: 'id',
        labelField: 'name',
        searchField: ['name'],
        sortField: [
            {
                field: 'name'
            }
        ]
    });
    $lotsId[0].selectize.clear();

    if (jQuery().daterangepicker) {
        $dateRangePickeroptions = {
            ranges: {
                'Today': [moment(), moment()],
                'Next 7 Days': [moment(), moment().subtract(-6, 'days')],
                'Next 30 Days': [moment(), moment().subtract(-29, 'days')],
                'Until The End Of This Month': [moment(), moment().endOf('month')]
            },
            startDate: moment(),
            endDate: moment().subtract(-1, 'months'),
            format: 'YYYY-MM-DD'
        };

        $('#inventory_date_range').daterangepicker(
            $dateRangePickeroptions,
            function (start, end) {
                $('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
            }
        );
    }

    $("#parking-inventory-from").validate({
        ignore: [],
        errorClass: "invalidField",
        rules: {
            lots: {
                required: true
            }
        },
        messages: {
            apartment_group_id: 'Please select group'
        },
        highlight: function(element, errorClass, validClass) {
            $(element).closest('div').removeClass('has-success').addClass('has-error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).closest('div').removeClass('has-error').addClass('has-success');
        },
        success: function(label) {
            $(label).closest('form').find('.valid').removeClass("invalid");
        },
        errorPlacement: function(error, element) {
        }
    });
});

$('#parking_inventory_show').click(function(e) {
    var parkingForm = $('#parking-inventory-from');
    var validate = parkingForm.validate();

    if (parkingForm.valid()) {
        var btn = $('#parking_inventory_show');
        btn.button('loading');
        var url = parkingForm.attr('action');
        var obj = parkingForm.serializeArray();
        $.ajax({
            type: "POST",
            url: url,
            data: obj,
            dataType: "json",
            success: function(data) {
                if (data.status == 'success') {
                    $('#result_view').html(data.result);

                    if($('.reservation-item').length || $('.unmovable').length) {
                        $('#print-spots').closest('div').show();
                    } else {
                        $('#print-spots').closest('div').hide();
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

$('#lots').change(function(e) {
    $('#lot_name_print_view').html($(this).text());
});

$('#print-spots').click(function(e) {
    window.print();
});