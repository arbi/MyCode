
$('#usage_building').click(function () {

    if (this.checked) {
        $('#usage_building_val').attr('value', '1');
    } else {
        $('#usage_building_val').attr('value', '0');
    }

    $("#div_facilities").toggle(this.checked);
});

state('save_button', function () {
    if ($('#name_is_changed').val() == '1') {
        $('#renameModal').modal('show');
        return true;
    }

    if (!IS_BUILDING && parseInt($('#usage_building_val').val())) {
        $('#create-building-process').modal('show');
    } else {
        saveProcess();
    }
});

function saveProcess(disable_building, forceRename) {
    disable_building    = typeof disable_building !== 'undefined' ? disable_building : false;
    forceRename         = typeof forceRename !== 'undefined' ? forceRename : false;

    checkRenameFromSave = true;

    checkRename(function(){
        $('#create-building-process').modal('hide');
        $('#renameModal').modal('hide');

        var validate = $('#form_edit_apartment_group').validate();

        if ($('#form_edit_apartment_group').valid()) {
            var btn = $('#save_button'),
                obj = $('#form_edit_apartment_group').serializeArray(),
                add_data = {};

            if (disable_building && IS_BUILDING) {
                obj.push({name: 'usage_building', value: '0'});
                obj.push({name: 'usage_building_val', value: '1'});
            } else if (IS_BUILDING) {
                obj.push({name: 'usage_building', value: '1'});
                obj.push({name: 'usage_building_val', value: '1'});
            }

            btn.button('loading');

            $.ajax({
                type: "POST",
                url: GLOBAL_SAVE_DATA,
                data: obj,
                dataType: "json",
                success: function (data) {
                    if (data.status == 'success') {
                        if (parseInt(data.id) > 0) {
                            window.location.href = GLOBAL_BASE_PATH + 'concierge/edit/' + data.id;
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
    }, checkRenameFromSave, forceRename);
}

$(function () {
    $("#user").autocomplete({
        source: function (request, response) {
            $.ajax({
                url: GLOBAL_USER_AUTOCOMPLATE,
                data: {
                    txt: $("#user").val(),
                    user_id: $("#user_id").val()
                },
                dataType: "json",
                type: "POST",
                success: function (data) {
                    var obj = [];
                    if (data && data.rc == '00') {
                        for (var row in data.result) {
                            var item = data.result[row],
                                    new_obj = {};

                            new_obj.value = item.name;
                            new_obj.id = item.id;
                            obj.push(new_obj);
                        }
                    }

                    response(obj);
                }
            });
        },
        max: 10,
        minLength: 1,
        autoFocus: true,
        select: function (event, ui) {
            if (ui.item) {
                $('#user_id').val(ui.item.id);
            }
        }
    });

    //fill apartments regarding to the country
    if ($('#apartment_group_id').val() == '') {
       //only for adding new group, not for editing
        $('#country_id').change(function(){
            $('#skills')[0].selectize.clear();
            $('#skills')[0].selectize.clearOptions();
            var country_id = $(this).val();
            if (country_id != '') {
                $.ajax({
                    type: "POST",
                    url: GLOBAL_APARTMENTS_FOR_COUNTRY,
                    data: {country_id:country_id},
                    dataType: "json",
                    success: function(data) {
                        if(data.status == 'success') {
                            $.each(data.apartments, function( index, value ) {
                                $('#skills')[0].selectize.addOption(value);
                            });
                        }
                    }
                });
            }

        })
    }

    $('#name').change(function () {
        checkRename(function () {});
    });
});

function checkRename(callback, fromSave, forceRename) {
    fromSave    = fromSave ? fromSave : false;
    forceRename = forceRename ? forceRename : false;

    if($('.is-apartel').length && !forceRename) {
        $.ajax({
            type: 'POST',
            url: GLOBAL_CHECK_GROUP_NAME,
            data: {
                id:     $("#apartment_group_id").val(),
                name:   $('#name').val()
            },
            success: function (data) {
                if (data.status == 'error') {
                    if (data.duplicate) {
                        disableSave();
                        notification(data);
                    } else if (
                        typeof $('#save_button').attr('disabled') !== typeof undefined
                        && $('#save_button').attr('disabled') !== false
                    ) {
                        enableSave();
                    }

                    if (data.is_changed) {
                        $('#name_is_changed').val(1);

                        if (fromSave) {
                            $('#renameModal').modal('show');
                        }
                    }
                } else {
                    if ($('#name_is_changed').val() == '1') {
                        $('#name_is_changed').val(0);
                    }

                    callback();
                }
            }
        });
    } else {
        callback();
    }
}

function disableSave() {
    $('#save_button').attr('disabled', 'desabled');
}

function enableSave() {
    $('#save_button').removeAttr('disabled');
}

// deactivate apartment group
$('#btn_deactivate_apartment_group').on('click', function () {
    $.ajax({
        type: "GET",
        url: GLOBAL_DEACTIVATE_GROUP,
        dataType: "json",
        success: function (data) {
            if (data.status == 'success') {
                window.location.href = GLOBAL_BASE_PATH + 'apartment-group';
            } else {
                notification(data);
            }
        }
    });
});

// create apartel
$('#create-apartel').on('click', function () {
    var  btn = $('#create-apartel');
    btn.button('loading');

    $.ajax({
        type: "POST",
        data: {
            groupId:GLOBAL_GROUP_ID
        },
        url: GLOBAL_CREATE_APARTEL,
        dataType: "json",
        success: function (data) {
            if (data.status == 'success') {
               window.location.href = '/apartel/' + data.apartelId;
            } else {
                notification(data);
                btn.button('reset');
            }
        }
    });
})

// deactivate apartel
$('#deactivate-apartel').on('click', function () {
    var  btn = $('#create-apartel');
    btn.button('loading');
    $.ajax({
        type: "POST",
        data: {
            groupId:GLOBAL_GROUP_ID
        },
        url: GLOBAL_DEACTIVATE_APARTEL,
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
});