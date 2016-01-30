$(function() {
	$('#save-document').click(function(e) {
		e.preventDefault();
        var noErrors = true;
        if ($('#document_description').val() == '') {
            noErrors = false;
            $('#document_description').closest('.form-group').removeClass('has-success').addClass('has-error');
        }
        else {
            $('#document_description').closest('.form-group').removeClass('has-error').addClass('has-success');
        }
        if ($('#document_url').val() != '') {
            var url = $('#document_url').val();
            var expression = /[-a-zA-Z0-9@:%_\+.~#?&//=]{2,256}\.[a-z]{2,4}\b(\/[-a-zA-Z0-9@:%_\+.~#?&//=]*)?/gi;
            var regex = new RegExp(expression);
            if (!url.match(regex)) {
                noErrors = false;
                $('#document_url').closest('.form-group').removeClass('has-success').addClass('has-error');
            }
            else {
                $('#document_url').closest('.form-group').removeClass('has-error').addClass('has-success');
            }
        }
        if (noErrors) {
            var elem = $(this),
                url = elem.attr('data-url');

            elem.button('loading');

            var data = new FormData();
            data.append('type_id', $('#document_type_id').val());
            data.append('url', $('#document_url').val());
            data.append('description', $('#document_description').val());
            data.append('fileInfo', $('#document_attachment')[0].files[0]);

            $.ajax({
                url: url,
                type: 'POST',
                data: data,
                contentType: false,
                processData: false,
                cache: false,
                success: function(data) {
                    if (data.status == 'success') {
                        window.location.href = '/user/edit/' + data.userId + '#documents-tab';
                    } else {
                        elem.button('reset');
                        notification(data);
                    }
                }
            });
        }

	});

    var attachment = $('#document_attachment');
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
       $('#document_attachment').after($.buttons.uploadButtonFullSet);
    } else {
        $('#document_attachment').after($.buttons.uploadButton);
    }

    // waiting for select a file to upload
    $("#edit-document-from").delegate("#uploadAttachment", "click", function(e) {
        $('#document_attachment').trigger('click');
    });


    // select upload file
    $('#document_attachment').change(function(e){

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
            //$("#validAttachment").rules("add", {number: false, min:0});
        }
    });

    // waiting action for delete attachment file
    $("#edit-document-from").delegate("#deleteAttachment", "click", function(e) {
        e.preventDefault();
        // $("#validAttachment").rules("add", {number: false, min:0});

        if($('#remove-attachment').length > 0){
            $('#remove-attachment').click();
        } else {
            $('#document_attachment').val('');
            $('#attachmentFileName').remove();
            $('#uploadAttachmentSet').after($.buttons.uploadButton).remove();
        }
        $('.file-name').hide();
    });

    // waiting action for download attachment file
    $("#downloadAttachment").on("click", function() {
        if($('#download-attachment').length > 0){
            $('#download-attachment').trigger('click');
        }
    });
});
