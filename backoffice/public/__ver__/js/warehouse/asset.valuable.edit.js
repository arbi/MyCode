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
            'status': {
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
                doesNotContainSpace: true
            },
            'name' : {
                required: false
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

    $.each(GLOBAL_LOCATION_LIST, function(index, value) {
        locationTargetSelectize[0].selectize.addOption(value);
    });
    locationTargetSelectize[0].selectize.addItem(GLOBAL_SELECTED_LOCATION);

    $('#comment-status').keyup(function() {
       if ($(this).val().length) {
           $('#submit-comment').removeClass('hidden');
       } else {
           $('#submit-comment').addClass('hidden');
       }
    });

    $('#submit-comment').click(function(event) {
        event.preventDefault();
        window.statusComment = $('#comment-status').val();
        $assetForm.trigger('submit');
    });

    $assetForm.submit(function(event) {
        event.preventDefault();
        if ($assetForm.valid()) {

            var data = {};
            data.location = $('#location').val();
            data.description = $('#description').val();
            data.status = parseInt($('#status').val());
            data.name = $('#name').val();
            data.id = parseInt($('#asset-id').val());
            data.serialNumber = $('#serial-number').val();
            data.assignee = $('#assignee').val();

            var toContinue = true;
            if (GLOBAL_SET_STATUS != data.status) {
                if (typeof window.statusComment == 'undefined') {
                    toContinue = false;
                    $('#submit-comment').addClass('hidden');
                    $('#comment-status').val('');
                    $('#comment-status-change').modal('show');
                } else {
                    toContinue = true;
                    data.statusComment = window.statusComment;
                    delete window.statusComment;
                }
            }
            if (toContinue) {
                $.ajax({
                    url: '/warehouse/asset/edit-save-valuable',
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
        }
    });

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
