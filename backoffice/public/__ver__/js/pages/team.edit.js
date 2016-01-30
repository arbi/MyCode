$(function() {
    $('#officers').selectize({
        valueField: 'id',
        labelField: 'name',
        create: false,
        plugins: ['remove_button'],
        selectOnTab: true,
        searchField: 'name',
        render: {
            item: function(item, escape) {
                return '<div>'
                + '<span>' + item.name + '</span>'
                + '</div>'
            }
        }
    });
    $('#members').selectize({
        valueField: 'id',
        labelField: 'name',
        create: false,
        plugins: ['remove_button'],
        selectOnTab: true,
        searchField: 'name',
        render: {
            item: function(item, escape) {
                return '<div>'
                + '<span>' + item.name + '</span>'
                + '</div>'
            }
        }
    });

    $('#frontier-apartments').selectize({
        plugins: ['remove_button']
    });

    $("#usage_frontier").click(function() {
        $("#frontier-options").toggle();
        if (!$(this).prop("checked")) {
            notification({
                msg: "Unchecking this usage will clear all frontier options from this team",
                status: "warning"
            });
        } else {
            $("#timezone").rules('add', {
                required: true
            });

        }
    });
});

state('save_button', function() {
    var validate = $('#team_manage_table').validate();
    if ($('#team_manage_table').valid()) {

        var btn = $('#save_button');
        btn.button('loading');
        $('#default_member').remove();
        $('#default_officer').remove();

        var obj = $('#team_manage_table').serialize();
        $.ajax({
            type: "POST",
            url: GLOBAL_SAVE_DATA,
            data: obj,
            dataType: "json",
            success: function(data) {
                if (data.status == 'success') {
                    if (parseInt(data.id) > 0) {
                        window.location.href = GLOBAL_BASE_PATH + 'team/edit/' + data.id;

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

$('#deactivate').click(function () {
    if (parseInt(IS_ASSOCIATED_TEAM)) {
        var data = {status: 'error', msg: "This team is default team for all <b>" + TASK_NAME + "</b> tasks. Hence it can't be deactivated."};
        notification(data);
    } else {
        $('#deactivateModal').modal('show');
    }
});

$('#change_active_status').on('click', function() {
    var id = $('#team_id').val();
    if (parseInt(id) > 0) {
        $.ajax({
            type: "POST",
            url: GLOBAL_CHANGE_ACTIVE_STATUS,
            data: {id:id},
            dataType: "json",
            success: function(data) {
                if(data.status == 'success'){
                    window.location.href = GLOBAL_BASE_PATH + 'team';
                } else {
                    notification(data);
                }
            }
        });
    }
});

$('#usage_department').on('click', function() {
    if ($("#department").attr("data-department") == 1) {

        if ($("#usage_department").prop('checked') == false) {
            var data = {'status' : 'warning', 'msg' : 'Removing this team as a Department will clear the Department of all of it\'s members' };

            notification(data);
        }
    }
});
