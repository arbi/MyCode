/**
 * Created by Hryar papikyan on 3/11/15.
 */
$(function() {
    $('.generated-setting-required').each(function () {
        $(this).rules("add", {
            required: true
        });
    });

    $('#locks-form').submit(function(event){
        event.preventDefault();
        if ($(this).valid()){
            var data = {};

            data.id          = $('[name="id"]').val();
            data.name        = $('#name').val();
            data.description = $('#description').val();
            data.is_physical = $('#is_physical').prop('checked') ? 1 : 0;

            data.additional_settings = {};
            $('.generated-setting').each(function(index){
                data.additional_settings[$(this).attr('name')] = $(this).val();
            });
            editLock(data);
        }
    })

    $('#lock_delete_button').click(function(){
        deleteLock($('[name="id"]').val());
    })

});

function deleteLock(lockId)
{
    $.ajax({
        type: "POST",
        url: GENERAL_DELETE_LOCK,
        data: {lock_id : lockId},
        dataType: "json",
        success: function(data) {
            if (data.status == 'success') {
                window.location.href = GENERAL_RETURN_PATH;
            }
            else {
                $('#delete-modal').modal('hide');
                notification(data);
            }
        }
    });
}

function editLock(data)
{   $("#locks-form input[type='submit']").attr('disabled','disabled');
    $.ajax({
        type: "POST",
        url: GENERAL_EDIT_LOCK,
        data: data,
        dataType: "json",
        success: function(data) {
                notification(data);
            $("#locks-form input[type='submit']").removeAttr('disabled');
        }
    });
}
