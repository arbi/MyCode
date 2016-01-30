$(function() {

    var $assetForm = $('#asset-form');
    var $categoryTypeBasedPart = $('#category-type-based-part');



    $.validator.setDefaults({
        ignore: ":hidden:not(*)"
    });

    $.validator.addMethod("notZero", function(value, element) {
        return value !== "0";
    }, "required");

    $.validator.addMethod("doesNotContainSpace", function(value, element) {
        return value.indexOf(" ") === -1;
    }, "required");

    jQuery.validator.addMethod("alphanumeric", function(value, element) {
        return this.optional(element) || /^[a-zA-Z0-9]+$/.test(value);
    });

    $assetForm.validate({
        onfocusout: false,
        invalidHandler: function(form, validator) {
            var errors = validator.numberOfInvalids();
            if (errors) {
                validator.errorList[0].element.focus();
            }
        },
        rules: {
            'category_id': {
                required: true,
                number: true,
                min: 1
            },
            'location': {
                required: true,
                notZero: true
            },
            'serial_number' : {
                required: true,
                alphanumeric: true
            },
            'sku' : {
                required: true,
                alphanumeric: true
            },
            'quantity': {
                required: true,
                number: true,
                min: 1
            }
        },
        highlight: function (element, errorClass, validClass) {
            if ($(element).prop("tagName") == 'TEXTAREA') {
                $(element).addClass('has-error');
            } else {
                $(element).parent().addClass('has-error');
            }
        },
        unhighlight: function (element, errorClass, validClass) {
            if ($(element).prop("tagName") == 'TEXTAREA') {
                $(element).removeClass('has-error');
            } else {
                $(element).parent().removeClass('has-error');
            }
        },
        success: function (label) {
            $(label).closest('form').find('.valid').removeClass("invalid");
        },
        errorPlacement: function (error, element) {
            // do nothing
        }
    });

    $('#category-id').change(function() {
        if ($(this).val() == 0) {
            $categoryTypeBasedPart.html('');
            return false;
        }
        var categoryType = parseInt(GLOBAL_MAP_OF_CATEGORY_TYPES[$(this).val()]);
        $.ajax({
            url: '/warehouse/asset/render-template',
            type: "POST",
            data: {
                categoryType : categoryType
            },
            cache: false,
            success: function (data) {
                if (data.status == 'error') {
                    notification(data);
                } else {
                    $categoryTypeBasedPart.html(data.partial_html);


                    var locationTargetSelectize = $('#location').selectize({
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
                                    default :
                                        label = ''
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
                                    default :
                                        label = ''
                                }
                                return '<div>'
                                    + label
                                    + '<span> ' + escape(option.text) + ' </span>'
                                    + '<small class="text-muted">' + escape(option.info) + '</small>'
                                    + '</div>'
                            }
                        }

                    });

                    locationTargetSelectize[0].selectize.clear();
                    locationTargetSelectize[0].selectize.addOption({'id' : 0, text: '-- Please Select --', info: '', 'label' : ''});
                    locationTargetSelectize[0].selectize.addItem(0);
                    $.each(JSON.parse(data.location_list), function(index, value) {
                        locationTargetSelectize[0].selectize.addOption(value);
                    });

                    switch (categoryType) {
                        case GLOBAL_TYPE_VALUABLE:
                                $('#assignee').selectize({
                                    plugins: ['remove_button'],
                                    selectOnTab: true
                                });
                                $('#shipment').closest('.form-group').hide();
                            break;
                    }
                }
            }
        });
    });


    $assetForm.submit(function(event) {
        event.preventDefault();
        if ($assetForm.valid()) {
            var data = {};
            var saveAction;
            data.category = $('#category-id').val();
            data.categoryType = parseInt(GLOBAL_MAP_OF_CATEGORY_TYPES[data.category]);
            data.location = $('#location').val();
            data.description = $('#description').val();

            switch (data.categoryType) {
                case GLOBAL_TYPE_CONSUMABLE:
                    data.quantity = $('#quantity').val();
                    data.sku = $('#sku').val();
                    saveAction = 'ajax-add-consumable';
                    break;
                case GLOBAL_TYPE_VALUABLE:
                    data.name = $('#name').val();
                    data.serialNumber = $('#serial-number').val();
                    data.assignee = $('#assignee').val();
                    saveAction = 'ajax-add-valuable';
                    break;
            }
            data.shipment = + $('#shipment').prop('checked');

            $.ajax({
                url: '/warehouse/asset/' + saveAction,
                type: "POST",
                data: data,
                cache: false,
                success: function (data) {
                    if (data.status == 'error') {
                        notification(data);
                    } else {
                        window.location.href = '/warehouse/asset/edit-' + data.type + '/' + data.id;
                    }

                }
            });
    }
    });
});
