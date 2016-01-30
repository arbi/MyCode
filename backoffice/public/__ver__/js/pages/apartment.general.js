$(function() {

    $( "#save_button" ).click(function() {
        tinymce.triggerSave();
        var validate = $('#apartment_general').validate();
        if ($('#apartment_general').valid()) {

            if($("#status").val() == STATUS_DISABLED) {
                $("#disable-warning-modal").modal("show");
            } else {
                saveChanges();
            }
        } else {
            validate.focusInvalid();
        }

    });

    $('.datetimepicker').datetimepicker({
        datepicker: false,
        format: 'H:i',
        step: 30
    });

    var statusOldValue;
    $("#status").on('focus', function() {
        statusOldValue = $(this).val();
    }).change(function() {
        var $this = $(this);
        if($(this).val() == STATUS_DISABLED) {
            $.ajax({
                type: "POST",
                url: CHECK_DISABLE_POSSIBILITY,
                dataType: "json",
                success: function(data) {
                    if(data.isPossible == false) {
                        var msg = {
                            status: "error",
                            msg: "Disabling this apartment is impossible, because it has current and/or future reservations."
                        }
                        $this.val(statusOldValue);
                        notification(msg);
                    }
                }
            });
        }
    });


    $('#apartment_name').change(function (){
        if(parseInt($('#aId').val()) > 0) {
            var data = {
                "status": "warning",
                "msg": "If apartment name is changed the current URL will not be valid anymore. It will also cause negative SEO Impact."
            };
            notification(data);
        }
    });

    $("#confirm-disable").click(function() {
        $("#disable-warning-modal").modal("hide");
        saveChanges();
    });


    $( "select#building_id" ).change(function() {
        var $buildingID = $(this).val();
        $.getJSON(getBuildingSectionURL + '/' + $buildingID, function(data) {
            var html = '';
            var select = $('select#building_section');
            if (data.length > 1) {
                select.closest('.form-group').show();
                html += '<option value="0">--Choose Section--</option>';
            } else {
                select.closest('.form-group').hide();
            }

            for (var i in data) {
                var item = data[i];
                html += '<option value="' + item.id + '">' + item.name + '</option>';
            }

            select.html(html);
        });
    });

});

function saveChanges()
{
    var btn = $('#save_button');
    btn.button('loading');

    var obj = $('#apartment_general').serializeArray();
    $.ajax({
        type: "POST",
        url: SAVE_DATA,
        data: obj,
        dataType: "json",
        success: function(data) {
            if(data.url != '' && data.status == 'success'){
                window.location.href = data.url;
            }
            notification(data);
            btn.button('reset');
            if($("#status").val() == STATUS_DISABLED) {
                    location.reload();
            }
        }
    });
}
