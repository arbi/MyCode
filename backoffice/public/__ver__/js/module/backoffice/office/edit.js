$(function() {
    var $attachment = $('#map_attachment');
    $.buttons = {};

    $.buttons.uploadButtonSet = '\
        <div class="btn-group pull-right margin-left-10" id="uploadAttachmentSet">\
            <button type="button" class="btn btn-success" id="uploadAttachment">\
                <span class="glyphicon glyphicon-upload"></span>\
                Upload Map Image\
            </button>\
            <button class="btn btn-success dropdown-toggle" data-toggle="dropdown">\
                <span class="caret"></span>\
            </button>\
            <ul class="dropdown-menu">\
                <li>\
                    <a id="deleteAttachment">\
                        <span class="glyphicon glyphicon-remove-circle"></span>\
                        Delete Image\
                    </a>\
                </li>\
            </ul>\
        </div>';

    $.buttons.uploadButtonFullSet = '\
        <div class="btn-group pull-right margin-left-10" id="uploadAttachmentFullSet">\
            <button class="btn btn-success dropdown-toggle" data-toggle="dropdown">\
                <span class="caret"></span>\
            </button>\
            <ul class="dropdown-menu">\
                <li>\
                    <a id="deleteAttachment">\
                        <span class="glyphicon glyphicon-remove-circle"></span>\
                        Delete Image\
                    </a>\
                    <a id="uploadAttachment">\
                        <span class="glyphicon glyphicon-upload"></span>\
                        Upload New Image\
                    </a>\
                </li>\
            </ul>\
        </div>';

    $.buttons.uploadButton = '\
        <button type="button" id="uploadAttachment" class="btn btn-success">\
            <span class="glyphicon glyphicon-upload"></span>\
            Upload Map Image\
        </button>';

    // find out whether there is already uploaded file
    if ($('#attachment-container').length  > 0) {
        $attachment.after($.buttons.uploadButtonSet);
    } else {
        $attachment.after($.buttons.uploadButton);
    }

    // waiting for select a file to upload
    $('#office_manage_table').delegate("#uploadAttachment", "click", function() {
        $attachment.trigger('click');
    });


    // select upload file
    $('#office_manage_table').change(function(e){

        var parrentLevel = 1;
        if ($('#uploadAttachmentFullSet').length > 0) {
            parrentLevel = 3;

        } else if ($('#uploadAttachmentSet').length > 0) {

        } else {
            $('#uploadAttachment').after($.buttons.uploadButtonSet).remove();
        }

        var $in = $(this);
        var filePath = $in.val();
        var $attachmentFileName = $('#attachmentFileName');
        if(filePath.match(/fakepath/)) {
            // update the file-path text using case-insensitive regex
            filePath = filePath.replace(/C:\\fakepath\\/i, '');
        }
        if ($attachmentFileName.length > 0) {

            $attachmentFileName.html('File: "'+filePath+'"');
        } else {
            $('#uploadAttachment').parents().eq(parrentLevel).append('<div id="attachmentFileName" class="clear left ml0 mt10">File: "'+filePath+'"</div>');
        }

    });

    // waiting action for delete attachment file
    $('#office_manage_table').delegate("#deleteAttachment", "click", function() {
        if($('#remove-attachment').length > 0){
            $('#delete_attachment').val(1);
        }
        $('.preview').remove();
        $('#attachmentFileName').remove();
        $('#uploadAttachmentSet').after($.buttons.uploadButton).remove();
    });
});

state('save_button', function() {
    var $form = $('#office_manage_table');
    var validate = $form.validate();

    if ($form.valid()) {
        var btn = $('#save_button');
        btn.button('loading');

        $form.ajaxSubmit({
            type: "POST",
            url: GLOBAL_SAVE_DATA,
            dataType: "json",
            success: function (data) {
                if (data.status == 'success') {
                    if (parseInt(data.id) > 0) {
                        window.location.href = GLOBAL_BASE_PATH + 'office/edit/' + data.id;
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
});

$('#office_delete_button').on('click', function() {
    var id = $('#office_id').val();
    if (parseInt(id) > 0) {
        $.ajax({
            type: "POST",
            url: GLOBAL_DELETE_OFFICE,
            data: {id:id},
            dataType: "json",
            success: function(data) {
                if(data.status == 'success'){
                    window.location.href = GLOBAL_BASE_PATH + 'office';
                } else {
                    notification(data);
                }
            }
        });
    }
});


$('#office_deactivate_button').on('click', function() {
    var id = $('#office_id').val();
    if (parseInt(id) > 0) {
        $.ajax({
            type: "POST",
            url: GLOBAL_DEACTIVATE_OFFICE,
            data: {id:id},
            dataType: "json",
            success: function(data) {
                if(data.status == 'success'){
                    window.location.href = GLOBAL_BASE_PATH + 'office';
                } else {
                    notification(data);
                }
            }
        });
    }
});



$('#office_activate_button').on('click', function() {
    var id = $('#office_id').val();
    if (parseInt(id) > 0) {
        $.ajax({
            type: "POST",
            url: GLOBAL_ACTIVATE_OFFICE,
            data: {id:id},
            dataType: "json",
            success: function(data) {
                if(data.status == 'success'){
                    window.location.href = GLOBAL_BASE_PATH + 'office';
                } else {
                    notification(data);
                }
            }
        });
    }
});


function populateProvinceOptions($countryID) {
    $.getJSON(GET_PROVINCE_LIST + '?country=' + $countryID, function(data) {
        var html = '';
        for (var i in data) {
            var item = data[i];
            html += '<option value="' + item.id + '">' + item.name + '</option>';
        }
        $('select#province_id').html(html);

        $( "select#province_id" ).trigger('change');
    });
}

function populateCityOptions($provinceID) {
    $.getJSON(GET_CITY_LIST + '?province=' + $provinceID, function(data) {
        var html = '';

        for (var i in data) {
            var item = data[i];
            html += '<option value="' + item.id + '">' + item.name + '</option>';
        }

        $('select#city_id').html(html);
    });
}



$( "select#country_id" ).change(function() {
    var $countryID = $(this).val();
    populateProvinceOptions($countryID);
});

$( "select#province_id" ).change(function() {
    var $provinceID = $(this).val();
    populateCityOptions($provinceID);
});


function removeRow(obj){
    var parent = $(obj).closest('.section_tr');
    $(parent).remove();
}


$("#addMore").click(function() {
    var $lastRow = $('#cost_section_table>tbody');
    var inputForm = '<div class="input-prepend input-append form-group margin-0" id="sec_0"><div class="col-sm-12"><input name="section[]" type="text" class="form-control" id="sec_0" maxlength="50" value="" /></div></div>';

    var html = '<tr class="section_tr">\
                    <td class="text-right addons-value">'+ inputForm +'</td>\
                    <td><a href="javascript:void(0)" class="btn btn-sm btn-danger btn-block sectionRemoveRow">Remove</a> <input value="0" type="hidden" name="disabled[]" class="removeRow"/></td>\
                </tr>';
    $lastRow.append(html);

    $( ".sectionRemoveRow" ).click(function() {
        removeRow(this);
    });
});

if ($(".sectionDisableRow").length > 0) {

    $( ".sectionDisableRow" ).click(function() {
        var secId = $(this).data('id'),
            self = $(this);

        self.button('loading');

        $.getJSON(CHANGE_SECTION_STATUS + '?secId=' + secId, function(data) {
            if (data.status == 'success') {
                    self.toggleClass('btn-danger');
                    self.toggleClass('btn-success');

                if (data.disable == '1') {
                    self.text('Enable');
                    $("#sec_"+secId).prop('readonly', true);
                } else {
                    self.text('Disable');
                    $("#sec_"+secId).prop('readonly', false);
                }
            }

            notification(data);
            self.button('disabled', false);
        });

    });
}
