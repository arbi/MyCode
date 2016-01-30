$(function() {
    $form = $('#parking-general');
    $file = $('#file');
    $("#save-button").click(function() {
        var validate = $form.validate();
        if ($form.valid()) {
            saveChanges();
        } else {
            validate.focusInvalid();
        }
    });
    setInterval(
        function(){
            if ($('#progress .progress-bar').text() == '100%') {
                setTimeout(function(){
                    $('#progress').hide();
                },1000)
            }
        },
        2000);

    $("#parking-lot-deactivate-button").click(function() {
        changeStatus(0);
    });

    $("#parking-lot-activate-button").click(function() {
        changeStatus(1);
    });

    $("#upload-button").click(function() {
        $file.trigger("click");
    });

    $file.on('change', function(e) {
        e.preventDefault();
        $('.btn').addClass('disabled');
        hideErrors();
        if ($file.val() == '') {
            showErrors('No file(s) selected');
            return;
        }
        //$.fn.ajaxSubmit.debug = true;
        $(this).closest("form").ajaxSubmit({
            url: UPLOAD_URL,
            type: 'post',
            xhr: function()
            {
                var xhr = new window.XMLHttpRequest();
                //Upload progress
                xhr.upload.addEventListener("progress", function(evt){
                    if (evt.lengthComputable) {
                        var percentComplete = Math.round(evt.loaded / evt.total * 100);
                        //Do something with upload progress
                        showProgress(percentComplete, percentComplete + '%');
                    }
                }, false);
                return xhr;
            },
            success: function (response, statusText, xhr, $form) {
                $('#upload-button').removeProp('disabled');

                if (response.status == 'success') {
                    $('#permit-preview').html('<img src="//' + TMP_IMAGES_URL + response.tmpName + '">').fadeIn();
                    $('#parking-permit').val(response.tmpName);
                }
                $file.val('');
                $('.btn').removeClass('disabled');
            },
            error: function(a, b, c) {
                notification({
                    status: 'error',
                    msg: 'Failed to upload the file'
                });
                $('.btn').removeClass('disabled');
                $file.val('');
            }
        });
    });

    $('#country-id').change(function() {
        var countryId = $(this).val();
        populateProvinceOptions(countryId);
    });

    $('#province-id').change(function() {
        var provinceId = $(this).val();
        populateCityOptions(provinceId);
    });
});

function changeStatus(newStatus)
{
    $.ajax({
        type: "POST",
        url: CHANGE_STATUS_URL,
        data: {
            status: newStatus
        },
        dataType: "json",
        success: function(data) {
            window.location.reload();
        },
        error: function() {
            notification({
                msg: "Something went wrong. Please refresh the page and try again.",
                status: "error"
            })
        }
    });
}

function saveChanges()
{
    var $btn = $('#save-button');
    $btn.button('loading');

    var obj = $('#parking-general').serializeArray();
    $.ajax({
        type: "POST",
        url: SAVE_DATA,
        data: obj,
        dataType: "json",
        success: function(data) {
            if(data.url != '' && data.status == 'success'){
                window.location.href = data.url;
            }
            $('#parking-permit').val('');
            notification(data);
            $btn.button('reset');
        }
    });
}

/**
 * Error Message functions
 */
function hideErrors() {
    $('#file-controls').removeClass('error');
    $('#file-errors').hide();
}
function showErrors(message) {
    $('#file-controls').addClass('error');
    $('#file-errors').show().html(message);
}


function showProgress(amount, message) {
    $progress = $('#progress');
    $progress.show();
    $progress.find('.progress-bar').width(amount + '%').text(message);
    if (amount < 100) {
        $progress.find('.progress')
            .addClass('active')
            .addClass('progress-info')
            .removeClass('progress-success');
    } else {
        $progress.find('.progress')
            .removeClass('active')
            .removeClass('progress-info')
            .addClass('progress-success');
    }
}

function populateProvinceOptions(countryId) {
    $.getJSON(getProvinceOptionsURL + '/' + countryId, function(data) {
        var html = '';
        $provinceId = $('#province-id');

        for (var i in data) {
            var item = data[i];
            html += '<option value="' + item.id + '">' + item.name + '</option>';
        }

        $provinceId.html(html);

        $provinceId.trigger('change');
    });
}

function populateCityOptions(provinceId) {
    $.getJSON(getCityOptionsURL + '/' + provinceId, function(data) {
        var html = '';

        for (var i in data) {
            var item = data[i];
            html += '<option value="' + item.id + '">' + item.name + '</option>';
        }

        $('#city-id').html(html);
    });
}