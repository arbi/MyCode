$(function() {
    var $entityId = $('#entity_id');
    $entityId.selectize({
        valueField: 'id',
        labelField: 'text',
        searchField: ['text'],
        multiple: false,
        sortField: [
            {
                field: ['type']
            }
        ],
        render: {
            option: function(option, escape) {
                var label;
                switch (parseInt(escape(option.type))) {
                    case ENTITY_TYPE_APARTMENT:
                        label = '<span class="label label-success">Apartment</span>';
                        break;
                    case ENTITY_TYPE_APARTMENT_GROUP:
                        label = '<span class="label label-info">Building</span>';
                        break;
                }
                return '<div>'
                    + label
                    + '<span> ' + escape(option.text) + ' </span>'
                    + '</div>'
            },
            item: function(option, escape) {
                var label;
                switch (parseInt(escape(option.type))) {
                    case ENTITY_TYPE_APARTMENT:
                        label = '<span class="label label-success">A</span>';
                        break;
                    case ENTITY_TYPE_APARTMENT_GROUP:
                        label = '<span class="label label-info">B</span>';
                        break;
                }
                return '<div>'
                    + label
                    + '<span> ' + escape(option.text) + ' </span>'
                    + '</div>'
            }
        },
        load: function(query, callback) {
            if (!query.length || query.length < 2) return callback();
            $.ajax({
                url: GET_ENTITY_LIST_URL,
                type: 'POST',
                dataType: 'json',
                data: {
                    query: query
                },
                error: function() {
                    callback();
                },
                success: function(res) {
                    if (res.status == 'error') {
                        notification(res);
                    } else {
                        callback(res);
                        $('#entity_id')[0].selectize.refreshOptions();
                    }
                }
            });
        },
        onChange: function(value) {
            if (value) {
                $('#entity_type').val($entityId[0].selectize.sifter.items[value].type);
            }
        }
    });

    if (selectedEntity) {
        $entityId[0].selectize.addOption(selectedEntity);
        $entityId[0].selectize.setValue(selectedEntity.id);
    }

    var attachment = $('#attachment_doc');
    $.buttons = {};

    $.buttons.uploadButtonSet = '\
        <div class="btn-group pull-left" id="uploadAttachmentSet">\
            <button type="button" class="btn btn-success" id="uploadAttachment">\
                <span class="glyphicon glyphicon-upload"></span>\
                Upload Attachment\
            </button>\
            <button class="btn btn-success dropdown-toggle" data-toggle="dropdown">\
                <span class="caret"></span>\
            </button>\
            <ul class="dropdown-menu">\
                <li>\
                    <a id="deleteAttachment">\
                        <span class="glyphicon glyphicon-remove-circle"></span>\
                        Delete Attachment\
                    </a>\
                </li>\
            </ul>\
        </div>';

    $.buttons.uploadButtonFullSet = '\
        <div class="btn-group pull-left" id="uploadAttachmentFullSet">\
            <button type="button" class="btn btn-success" id="downloadAttachment">\
                <span class="glyphicon glyphicon-download"></span>\
                Download Attachment\
            </button>\
            <button class="btn btn-success dropdown-toggle" data-toggle="dropdown">\
                <span class="caret"></span>\
            </button>\
            <ul class="dropdown-menu">\
                <li>\
                    <a id="deleteAttachment">\
                        <span class="glyphicon glyphicon-remove-circle"></span>\
                        Delete Attachment\
                    </a>\
                    <a id="uploadAttachment">\
                        <span class="glyphicon glyphicon-upload"></span>\
                        Upload New Attachment\
                    </a>\
                </li>\
            </ul>\
        </div>';

    $.buttons.uploadButton = '\
        <button type="button" id="uploadAttachment" class="btn btn-success">\
            <span class="glyphicon glyphicon-upload"></span>\
            Upload Attachment\
        </button>';


    // find out whether there is already uploaded file
    if ($('#download-attachment').length  > 0) {
       $('#attachment_doc').after($.buttons.uploadButtonFullSet);
    } else {
        $('#attachment_doc').after($.buttons.uploadButton);
    }

    // waiting for select a file to upload
    $("#document-form").delegate("#uploadAttachment", "click", function() {
        $('#attachment_doc').trigger('click');
    });


    // select upload file
    $('#attachment_doc').change(function(e){

        var parrentLevel = 1;
        if ($('#uploadAttachmentFullSet').length > 0) {
            parrentLevel = 3;

        } else if ($('#uploadAttachmentSet').length > 0) {

        } else {
            $('#uploadAttachment').after($.buttons.uploadButtonSet).remove();
        }

        var $in = $(this);
        var filePath = $in.val();
        if(filePath.match(/fakepath/)) {
            // update the file-path text using case-insensitive regex
            filePath = filePath.replace(/C:\\fakepath\\/i, '');
        }
        if ($('#attachmentFileName').length > 0) {

            $('#attachmentFileName').html('File: "'+filePath+'"');
        } else {
            $('.file-name').append('<p id="attachmentFileName" class="margin-0 clear text-left text-muted">File: "'+filePath+'"</p>');
            $('.file-name').show();
        }

        if(this.files[0].size > $(this).attr('data-max-size')) {
            $('#attachmentFileName').append(' / <span class="text-error">Maximum allowed size is 50Mb</span>');
            $("#validAttachment").rules("add", {number: true, min:100000});
        } else {
            $("#validAttachment").rules("add", {number: false, min:0});
        }

    });

    // waiting action for delet attachment file
    $("#document-form").delegate("#deleteAttachment", "click", function(e) {
        e.preventDefault();
        $("#validAttachment").rules("add", {number: false, min:0});

        if($('#remove-attachment').length > 0){
            $('#remove-attachment').click();
        } else {
            $('#attachment_doc').val('');
            $('#attachmentFileName').remove();
            $('#uploadAttachmentSet').after($.buttons.uploadButton).remove();
        }
        $('.file-name').hide();
    });

    // waiting action for delet attachment file
    $("#document-form").delegate("#downloadAttachment", "click", function() {
        if($('#download-attachment').length > 0){
            $('#download-attachment').trigger('click');
        } else {

        }
    });

    // copy download url to clipboard
    $("#document-form").delegate("#copyAttachmentUrl", "click", function() {
        if($('#download-attachment').length > 0){
            if (window.clipboardData) // Internet Explorer
            {
                window.clipboardData.setData("Text", text);
            }
            else
            {
                var text_val=eval($('#download-attachment'));
                text_val.focus();
                text_val.select();
                if (!document.all) return; // IE only
                r = text_val.createTextRange();
                r.execCommand('copy');
            }
            //window.clipboardData.setData(window.location.pathname+$('#download-attachment').attr('value'),str);
            //addtoppath(window.location.pathname+$('#download-attachment').attr('value'));
        } else {

        }
    });
    
    $( "#save_button" ).click(function() {
        tinymce.triggerSave();
        if($('#document-form').valid()) {
           $('#save_button').button('loading');
           $('#document-form').submit();
        }
    });
    
    $('.custom-selectize').each(function (index) {
        var custom_selectize = $(this).selectize({
            onDropdownOpen: function (dropdown) {
                var inputField = dropdown.parent().find('input');
                var e;
                tempVal = custom_selectize[0].selectize.getValue();
                // 8 => Backspace
                e = jQuery.Event("keydown", {keyCode: 8});
                inputField.trigger(e);
            },
            onDropdownClose: function () {
                if (!custom_selectize[0].selectize.getValue()) {
                    custom_selectize[0].selectize.setValue(tempVal);
                }
            }
        })
    });

    $('#valid_from').daterangepicker({
        'singleDatePicker': true,
        'format': globalDateFormat
    });
    $('#valid_to').daterangepicker({
        'singleDatePicker': true,
        'format': globalDateFormat
    });
});
