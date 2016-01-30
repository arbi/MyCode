$(function() {
    var form = $('#contact_form');
    var contactId = $('#contact_id').val();

	$.validator.setDefaults({
		ignore: ':hidden:not(*)'
	});

    if (form.length > 0) {
        form.validate({
            rules: {
                name: {
                    required: true
                },
	            team_id: {
		            required: function() {
			            return ($('#scope').val() === '1');
		            }
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

    $('#save_button').click(function() {
        var btn = $('#save_button');
        btn.button('loading');

        if (form.valid()) {
            var formData = form.serialize();
            if (contactId > 0) {
                updateContact(formData);
            } else {
                createContact(formData);
            }
        }

        btn.button('reset');
    });

    $('#delete_button').click(function() {
        $('#deleteModal').modal('show');
    });

    $('#go_delete_button').click(function() {
        $('#deleteModal').modal('hide');

        var btn = $('#delete_button');

        btn.button('loading');

        deleteContact(contactId);

        btn.button('reset');
    });

    var $scope = $('#scope');

    $scope.change(function() {
        if (SCOPE_TEAM == parseInt($(this).val())) {
            $('#team-block').slideDown();
        } else {
            $('#team-block').slideUp();
        }
    });

    $scope.change();

    $('#apartment_id, #building_id, #partner_id, #scope, #team_id').selectize({
        create: false
    });

    $('#apartment_id').change(function() {
        $.ajax({
            type: "POST",
            url: GLOBAL_GET_BUILDING_URL + $(this).val(),
            dataType: "json",
            success: function(result) {
                if (result.id) {
                    $('#building_id')[0].selectize.setValue(result.id);
                }
            }
        });
    });

    phoneNumberCountryCode = $('#phone_mobile_country_id, #phone_company_country_id, #phone_other_country_id, #phone_fax_country_id').selectize({
        preload: true,
        create: false,
        maxItems: 1,
        valueField: 'id',
        labelField: 'name',
        searchField: ['name', 'code'],
        sortField: [
            {
                field: 'name'
            }
        ],
        render: {
            option: function(option, escape) {
                return '<div>'
                    + '<span> ' + escape(option.name) + ' </span>'
                    + '<small class="text-muted">+' + escape(option.code) + '</small>'
                    + '</div>'
            },
            item: function(option, escape) {
                return '<div>+'
                    + escape(option.code)
                    + '</div>'
            }
        },
        load: function(query, callback) {
            $.ajax({
                url: GLOBAL_GET_PHONE_CODES,
                type: 'POST',
                dataType: 'json',
                error: function() {
                    callback();
                },
                success: function(res) {
                    if (res.status == 'error') {
                        notification(res);
                    } else {
                        callback(res);
                        if (contactId > 0) {
                            if (FORM_CURRENT_PHONE_MOBILE_PH)
                            { phoneNumberCountryCode[0].selectize.addItem(FORM_CURRENT_PHONE_MOBILE_PH); }

                            if (FORM_CURRENT_PHONE_COMPANY_PH)
                            { phoneNumberCountryCode[1].selectize.addItem(FORM_CURRENT_PHONE_COMPANY_PH); }

                            if (FORM_CURRENT_PHONE_OTHER_PH)
                            { phoneNumberCountryCode[2].selectize.addItem(FORM_CURRENT_PHONE_OTHER_PH); }

                            if (FORM_CURRENT_PHONE_FAX_PH)
                            { phoneNumberCountryCode[3].selectize.addItem(FORM_CURRENT_PHONE_FAX_PH); }
                        }
                    }
                }
            });
        },
        onItemAdd: function(value, item) {
            var nextInput = item.closest('.phone-row').find('.phone-number > input');
            nextInput.focus();
        }
    });
});


function updateContact(data) {
    var $form = $('#contact_form');
    var saveUrl = $form.attr('action');

    $.ajax({
        type: "POST",
        url: saveUrl,
        data: data,
        dataType: "json",
        success: function(result) {
            notification(result);
        }
    });
}

function deleteContact(id) {
    $.ajax({
        type: "POST",
        url: GLOBAL_DELETE_CONTACT_URL + id,
        data: id,
        dataType: "json",
        success: function(result) {
            if (result.status == 'success') {
                window.location.href = GLOBAL_CONTACT_URL;
            } else {
                notification(result);
            }
        }
    });
}

function createContact(data) {
    var $form = $('#contact_form');
    var saveUrl = $form.attr('action');
    $.ajax({
        type: "POST",
        url: saveUrl,
        data: data,
        dataType: "json",
        success: function(result) {
            if (result.status == 'success') {
                window.location.href = GLOBAL_EDIT_CONTACT_URL + result.id;
            } else {
                notification(result);
            }
        }
    });
}
