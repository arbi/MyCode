/**
 * Created by Hryar papikyan on 3/11/15.
 */
$(function() {

   $('#type_id').change(function(){
       var selectedType = $('#type_id').val();
       if (parseInt(selectedType)) {
           getAndDrawSettingsForType(selectedType);
       }
       else {
           $('#additional-settings').html('<div class="alert alert-warning" role="alert"><span class="glyphicon glyphicon-warning-sign" aria-hidden="true"></span> There is no type chosen</div>');
       }

   })

    $('#locks-form').submit(function(event){
        event.preventDefault();
        if ($(this).valid()){
            var data = {};

            data.name        = $('#name').val();
            data.description = $('#description').val();
            data.type_id     = $('#type_id').val();
            data.is_physical = $('#is_physical').prop('checked') ? 1 : 0;

            data.additional_settings = {};
            $('.generated-setting').each(function(index){
                data.additional_settings[$(this).attr('name')] = $(this).val();
            });
            saveNewLock(data);
        }

    })

})

function saveNewLock(data)
{
    $("#locks-form input[type='submit']").attr('disabled','disabled');
    $.ajax({
        type: "POST",
        url: GENERAL_SAVE_NEW_LOCK,
        data: data,
        dataType: "json",
        success: function(data) {
            if (data.status == 'success') {
                window.location.href = GENERAL_RETURN_PATH;
            } else {
                notification(data);
            }
            $("#locks-form input[type='submit']").removeAttr('disabled');
        }
    });
}

function getAndDrawSettingsForType(type_id)
{
    $('p.help-block').hide();
    $('p.help-block[data-id="' + type_id + '"]').show();
    $.ajax({
        type: "POST",
        url: GENERAL_GET_SETTINGS_FOR_TYPE,
        data: {type_id: type_id},
        dataType: "json",
        success: function(data) {
            if (data.status == 'success') {
                if (data.generatedHtml != '') {
                    $('#additional-settings').html(data.generatedHtml);

                    $('.generated-setting-required').each(function () {
                        $(this).rules("add", {
                            required: true
                        });
                    });

                }
                else {
                    $('#additional-settings').html('<div class="alert alert-success" role="alert"><span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span> This type of lock does not have any additional settings</div>');

                }
            } else {
                notification(data);
            }
        }
    });
}