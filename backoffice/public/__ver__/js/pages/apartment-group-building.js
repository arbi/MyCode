state('save_button', function () {
    $form = $('#form_apartment_group_building');
    if ($form.valid()) {
        var btn = $('#save_button');
        btn.button('loading');

        $form.ajaxSubmit({
            type: "POST",
            url: GLOBAL_SAVE_DATA,
            data: {
                id: APARTMENT_GROUP_ID
            },
            dataType: "json",
            success: function (data) {
                if (data.status == 'success') {
                    if (parseInt(data.id) > 0) {
                         window.location.href = GLOBAL_BASE_PATH + 'concierge/edit/' + data.id + '/building';
                    } else {
                        notification(data);
                    }
                } else {
                    notification(data);
                }

                btn.button('reset');
            }
        });
    }
});

$(function() {
    var $building = $("#building");
    var $downloadAttachment = $('#download-attachment');
    var $attachment = $('#map_attachment');
    var $kiPageType = $('#key_instruction_page_type');

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
    $building.delegate("#uploadAttachment", "click", function() {
        $attachment.trigger('click');
    });


    // select upload file
    $attachment.change(function(e){

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

    // waiting action for delet attachment file
    $building.delegate("#deleteAttachment", "click", function() {
        if($('#remove-attachment').length > 0){
            $('#delete_attachment').val(1);
        }
        $('.preview').remove();
        $attachment.val('');
        $('#attachmentFileName').remove();
        $('#uploadAttachmentSet').after($.buttons.uploadButton).remove();
    });

    // waiting action for delet attachment file
    $building.delegate("#downloadAttachment", "click", function() {
        if($downloadAttachment.length > 0){
            $downloadAttachment.trigger('click');
        } else {

        }
    });

    // copy download url to clipboard
    $building.delegate("#copyAttachmentUrl", "click", function() {
        if($downloadAttachment.length > 0){
            if (window.clipboardData) // Internet Explorer
            {
                window.clipboardData.setData("Text", text);
            }
            else
            {
                var text_val=eval($downloadAttachment);
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

    var kiTextline = function() {
        var kType = $kiPageType.find(":selected").text();
        if (kType == 'Reception') {
            $('#apartment').show();
            $('#reception').show();
        } else {
            $('#reception').hide();
            $('#apartment').show();
        }
        $kiPageType.on('change', function(){
            var kType = $kiPageType.find(":selected").text();
            if (kType == 'Reception') {
                $('.location_part').show();
                //$('#reception').show();
                //$('#apartment').show();
            } else {
                $('.location_part').hide();
                //$('#apartment').show();
                //$('#reception').hide();
            }
        });
    };

    kiTextline();

    if ($('#key_instruction_page_type option:selected').val() == 2) {
        $('.location_part').show();
    }

    // section part
    $('#section-form').validate({
        ignore: [],
        rules: {
            'section_name': {
                required: true
            }
            ,
            'lock': {
                required: true,
                number: true,
                min: 1
            }
        },
        highlight: function (element, errorClass, validClass) {
            $(element).closest('.row').removeClass('has-success').addClass('has-error');
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).closest('.row').removeClass('has-error').addClass('has-success');
        },
        success: function (label) {
            $(label).closest('form').find('.valid').removeClass("invalid");
        },
        errorPlacement: function (error, element) {
            element.closest('.row').find('.help-block').html(error.text());
        }
    });

    var $lock = $('#lock');
    $lock.selectize({
        create: false,
        plugins: ['remove_button'],
        searchField: ['value', 'text'],
        valueField: 'value',
        labelField: 'text',
        sortField: [
            {
                field: 'text'
            }
        ]
    });
    $lock[0].selectize.clear();

    // add new section
    $('.add-new-section').click(function(e) {
        e.preventDefault();
        $('#section_id').val(0);
        $('#section_name').val('');
        $('#lock')[0].selectize.clear();
        $('#lots')[0].selectize.clear();
        $('#section-dialog').modal('show');
        $('.save-section').text('Add Section');
    });

    // edit section
    $('.edit-section').click(function(e) {
        e.preventDefault();
        $('#section-dialog').modal('show');
        $('.save-section').text('Edit Section');

        $('#section_name').val($(this).data('name'));
        $('#lock')[0].selectize.addItem($(this).data('lock'), true);
        $('#section_id').val($(this).data('id'));

        // lots
        var $lots = $(this).data('lots');
        var $lotsSelectize = $('#lots')[0].selectize;
        $lotsSelectize.clear();
        if ($lots) {
            if ($.isNumeric($lots)) {
                $lotsSelectize.addItem($lots, true);
            } else {
                var $lotsArray = $lots.split(',');
                for(var i in $lotsArray) {
                    var item = $lotsArray[i];
                    $lotsSelectize.addItem(item, true);
                }
            }
        }
    });

    // delete section
    $('.delete-item-section').click(function(e) {
        e.preventDefault();
        $('.delete-section').attr('href', '/concierge/edit/' + APARTMENT_GROUP_ID + '/building/delete/' + $(this).data('id'));
        $('#delete-dialog').modal('show');
    });

    // save section data
    $('.save-section').on('click', function () {
        var $formObj = $('#section-form');
        var validate = $formObj.validate();
        if ($formObj.valid()) {
            var  btn = $('.save-section');
            btn.button('loading');
            var $formData = $formObj.serializeArray();
            $.ajax({
                type: "POST",
                data: $formData,
                url: GLOBAL_SAVE_SECTION,
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
        } else {
            validate.focusInvalid();
        }
    });
});