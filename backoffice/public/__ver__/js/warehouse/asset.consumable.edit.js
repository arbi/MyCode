$(function() {

    var $assetForm = $('#asset-form');

    $.validator.setDefaults({
        ignore: ":hidden:not(*)"
    });

    $.validator.addMethod("notZero", function(value, element) {
        return value !== "0";
    }, "required");

    $.validator.addMethod("doesNotContainSpace", function(value, element) {
        return value.indexOf(" ") === -1;
    }, "required");

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
            'quantity': {
                required: true,
                number: true
            },
            'threshold': {
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
                        label = '';
                }
                return '<div>'
                    + label
                    + '<span> ' + escape(option.text) + ' </span>'
                    + '<small class="text-muted">' + escape(option.info) + '</small>'
                    + '</div>';
            }
        }

    });

    locationTargetSelectize[0].selectize.clear();

    $.each(GLOBAL_LOCATION_LIST, function(index, value) {
        locationTargetSelectize[0].selectize.addOption(value);
    });
    locationTargetSelectize[0].selectize.addItem(GLOBAL_SELECTED_LOCATION);

    $assetForm.submit(function(event) {
        event.preventDefault();
        if ($assetForm.valid()) {
            var data = {};
            data.category = parseInt($('#category-id').val());
            data.quantity = parseInt($('#quantity').val());
            data.location = $('#location').val();
            data.description = $('#description').val();
            data.id = parseInt($('#asset-id').val());
            data.threshold = parseInt($('#threshold').val());
            $.ajax({
                url: '/warehouse/asset/edit-save-consumable',
                type: "POST",
                data: data,
                cache: false,
                success: function (data) {
                    if (data.status == 'error') {
                        notification(data);
                    } else {
                        location.reload();
                    }

                }
            });
        }
    });


    if ($('#asset-id').val()) {
        locationTargetSelectize[0].selectize.disable();
        $('#category-id').attr('disabled', true);
    }

    if (HISTORY_DATA.length > 0) {
        $('#history_clean').hide();

        var dataTableHistory = $('#datatable_history').DataTable({
            bFilter: true,
            bInfo: true,
            bServerSide: false,
            bProcessing: false,
            bPaginate: true,
            bAutoWidth: false,
            bStateSave: false,
            iDisplayLength: 10,
            sAjaxSource: false,
            sPaginationType: "bootstrap",
            aaSorting: [[0, 'desc']],
            aaData: HISTORY_DATA,
            sDom: 'l<"enabled">frti<"bottom"p><"clear">',
            aoColumns: [
                {
                    "name": "date",
                    "width": "150px"
                }, {
                    "name": "user",
                    "width": "200px"
                }, {
                    "name": "message",
                    "sortable": false
                }
            ]
        });
    } else {
        $('#datatable_history').hide();
        $('#history_clean').show();
    }

});
