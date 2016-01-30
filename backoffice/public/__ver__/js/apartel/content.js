$(function() {
    tinymce.init({
        selector: ".tinymce",
        skin: "clean",
        plugins: [
            "code", "link"
        ],
        menu : {},
        height: 468,
        browser_spellcheck : true,
        extended_valid_elements : "i[*]",
        verify_html : false,
        toolbar: "undo redo | styleselect | bold italic underline |  aligncenter alignjustify alignleft alignright | bullist numlist outdent indent | link | print | fontsizeselect | code | removeformat"
    });

    $( "#save_button" ).click(function() {
        tinymce.triggerSave();

        var form = $('#apartel_content').validate();

        if (form.valid()) {
            saveChanges();
        } else {
            validate.focusInvalid();
        }

    });

    var attachmentElement = $('#bg_image'),
        attachmentButton = {};

    attachmentButton = '\
        <button type="button" id="uploadAttachment" class="btn btn-success">\
            <span class="glyphicon glyphicon-upload"></span>\
            Upload Attachment\
        </button>';

    attachmentElement.after(attachmentButton);

    $("#apartel_content").delegate("#uploadAttachment", "click", function(e) {
        attachmentElement.trigger('click');
    });

    attachmentElement.change(function(e){
        var filePath = $(this).val();

        if(filePath.match(/fakepath/)) {
            // update the file-path text using case-insensitive regex
            filePath = filePath.replace(/C:\\fakepath\\/i, '');
        }

        if ($('#attachmentFileName').length > 0) {
            $('#attachmentFileName').html('File: "'+filePath+'"');
        } else {
            $('#bg_image_file_name').after('<div id="attachmentFileName" class="margin-left-5 clear text-left text-muted">File: "'+filePath+'"</div>');
        }
    });


});

function saveChanges()
{
    var btn = $('#save_button');
    btn.button('loading');

    var form = $('#apartel_content'),
        data = new FormData();

    $("form#apartel_content").serializeArray().forEach(function(field) {
        data.append(field.name, field.value);
    });

    data.append('apartel_bg_image', $('#bg_image')[0].files[0]);

    $.ajax({
        type: "POST",
        url: GLOBAL_GENERAL_SAVE_PATH,
        data: data,
        processData: false,
        contentType: false,
        cache: false,
        success: function(data) {
            if (data.img !== undefined) {
                $('#bg-image')
                    .css('background', 'url(' + GLOBAL_IMAGE_PATH + data.img + ') 0% 0% no-repeat')
                    .css('height', '200px')
                    .css('background-size', 'cover');
            }

            notification(data);
            btn.button('reset');
        }
    });


}