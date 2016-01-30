$(function() {
    locationTargetSelectize = $('#location_target').selectize({
        preload: true,
        maxItems: 1,
        create: false,
        valueField: 'id',
        labelField: 'text',
        searchField: 'text',
        sortField: [
            {field: 'label', direction: 'asc'},
            {field: 'text', direction: 'asc'}
        ],
        render: {
            option: function(option, escape) {
                var label;
                switch (escape(option.label)) {
                    case 'apartment':
                        label = '<span class="label label-success">Apartment</span>';
                        break;
                    case 'storage':
                        label = '<span class="label label-primary">Storage</span>';
                        break;
                    case 'office':
                        label = '<span class="label label-info">Office</span>';
                        break;
                    case 'building':
                        label = '<span class="label label-warning">Building</span>';
                        break;
                }
                return '<div>'
                    + label
                    + '<span> ' + escape(option.text) + ' </span>'
                    + '<small class="text-muted">' + escape(option.info) + '</small>'
                    + '</div>'
            },
            item: function(option, escape) {
                var label;
                switch (escape(option.label)) {
                    case 'apartment':
                        label = '<span class="label label-success">Apartment</span>';
                        break;
                    case 'storage':
                        label = '<span class="label label-primary">Storage</span>';
                        break;
                    case 'office':
                        label = '<span class="label label-info">Office</span>';
                        break;
                    case 'building':
                        label = '<span class="label label-warning">Building</span>';
                        break;
                }
                return '<div>'
                    + label
                    + '<span> ' + escape(option.text) + ' </span>'
                    + '<small class="text-muted">' + escape(option.info) + '</small>'
                    + '</div>'
            }
        },
        onChange: function(value) {
            if (value) {
                // same process
            } else {
                locationTargetSelectize[0].selectize.clear();
            }
        }
    });

    $('#location_target')[0].selectize.load(function(callback) {
        $.ajax({
            url: GLOBAL_GET_LOCATIONS_URL,
            type: 'POST',
            dataType: 'json',
            data: {
                query: '',
                category_id: $('#asset_category_id').val(),
                category_type: CATEGORY_TYPE_VALUABLE
            },
            error: function() {
                callback();
            },
            success: function(res) {
                if (res.status == 'error') {
                    notification(res);
                } else {
                    callback(res);
                }
            }
        });
    });

    var form = $('#order_form');
    if (form.length > 0) {
        form.validate({
            ignore: '',
            rules: {
                'location_target': {
                    required: true
                },
                url_template: {
                    required: false,
                    url: true
                }
            },
            highlight: function (element, errorClass, validClass) {
                $(element).closest('.controls').removeClass('has-success').addClass('has-error');
            },
            unhighlight: function (element, errorClass, validClass) {
                $(element).closest('.controls').removeClass('has-error').addClass('has-success');
            },
            success: function (label) {
                $(label).closest('form').find('.valid').removeClass("invalid");
            },
            errorPlacement: function (error, element) {}
        });
    }

    $('#add-name-quantity-pair').click(function(event){
       event.preventDefault();
        if ($(this).hasClass('disabled')) {
            return false;
        }
        var $title_template         = $('input[name="title_template"]');
        var $url_template           = $('input[name="url_template"]');
        var $quantity_template      = $('input[name="quantity_template"]');
        var $quantity_type_template = $('select[name="quantity_type_template"]');

        var quanity_type_active_option = $quantity_type_template.find('option:selected');
        var quanity_type_options       = '';
        $quantity_type_template.find('option').each(function() {
            var selected_status = '';
            if (quanity_type_active_option.val() == $(this).val()) {
                var selected_status = ' selected';
            }
            quanity_type_options += '<option value="'+ $(this).val() +'" '+ selected_status +'>'+ $(this).text() +'</option>';
        });

        var title                   = $title_template.val();
        var url                     = $url_template.val();
        var quantity                = $quantity_template.val();
        var HTMLtoBeAdded =
           '<div class="row margin-bottom-15 cloned">' +
                '<div class="col-sm-1">' +
                    '<a href="#" class="btn btn-danger btn-sm glyphicon glyphicon-minus remove-name-quantity-pair" ></a>' +
                '</div>' +
                '<div class="col-sm-3 controls">' +
                    '<input type="text" name="title[]" class="form-control order_title"  value="' + title +'">' +
                '</div>' +
                '<div class="col-sm-2 controls">' +
                    '<input type="number" name="quantity[]"  class="form-control order_quantity"  value="' + quantity +'">' +
                '</div>' +
               '<div class="col-sm-3 controls">' +
                    '<select name="quantity_type[]" class="form-control order_quantity_type">' +
                        quanity_type_options +
                    '</select>' +
               '</div>' +
               '<div class="col-sm-3 controls">' +
                    '<input type="text" name="url[]" class="form-control order_url"  value="' + url +'">' +
               '</div>' +
           '</div>';
        $('#name-quantity-pairs-container').append(HTMLtoBeAdded);
        $title_template.val('');
        $quantity_template.val('');
        $quantity_type_template.find('option:first').attr('selected', 'selected');
        $url_template.val('');
        $(this).addClass('disabled');
        if (disableOrEnableCreateButton()) {
            $('#save_button').removeClass('disabled');
        } else {
            $('#save_button').addClass('disabled');
        }
    });

    $('input[name="title_template"], input[name="quantity_template"]').bind(("keyup change"), function(){
        var $title_template = $('input[name="title_template"]');
        var $quantity_template = $('input[name="quantity_template"]');
        var title = $title_template.val();
        var quantity = $quantity_template.val();
        if (disableOrEnableCreateButton()) {
            $('#save_button').removeClass('disabled').removeAttr('disabled');
        } else {
            $('#save_button').addClass('disabled').attr('disabled', 'disabled');
        }
        if (checkNameQuantitypair(title, quantity)) {
            $('#add-name-quantity-pair').removeClass('disabled');
        } else {
            $('#add-name-quantity-pair').addClass('disabled');
        }
    });

    $('input[name="title_template"], input[name="quantity_template"]').keypress(function(event){
        if (event.which == 13) {
            event.preventDefault();
            if ($('#add-name-quantity-pair').is(':visible')) {
                $('#add-name-quantity-pair').trigger('click');
            }
        }
    });

});

function checkNameQuantitypair(title, quantity)
{
    return (title != '' && !isNaN(parseInt(quantity)) && quantity == parseInt(quantity) && quantity > 0);
}

function disableOrEnableCreateButton()
{
    if (!$('.row.cloned').length && !checkNameQuantitypair($('input[name="title_template"]').val(), $('input[name="quantity_template"]').val())) {
        return false;
    }
    var enable = true;
    $('.row.cloned').each(function(){
        var title = $(this).find('.order_title').val();
        var quantity = $(this).find('.order_quantity').val();
        if (!checkNameQuantitypair(title, quantity)) {
            enable = false;
            //break
            return false;
        }
    });
    return enable;
}

$(document).on('keyup change', '.order_title, .order_quantity', function(){
    if (disableOrEnableCreateButton()) {
        $('#save_button').removeClass('disabled').removeAttr('disabled');
    } else {
        $('#save_button').addClass('disabled').attr('disabled', 'disabled');
    }
});

$(document).on('click','.remove-name-quantity-pair',function(event) {
    event.preventDefault();
    var $row = $(this).closest('.row.cloned');
    $row.remove();
    if (disableOrEnableCreateButton()) {
        $('#save_button').removeClass('disabled').removeAttr('disabled');
    } else {
        $('#save_button').addClass('disabled').attr('disabled', 'disabled');
    }
});

$('#order_form').submit(function () {
    if ($('#order_form').valid()) {
        $('#save_button').button('loading');
        return true;
    } else {
        return false;
    }
});
