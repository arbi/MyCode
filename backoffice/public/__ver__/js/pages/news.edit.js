$(function(){
    if ($('.tinymce').length > 0) {
        var tinymceObj = {
            selector: ".tinymce",
            plugins: [
                "code", "autoresize", "link"
            ],
            skin: "clean",
            extended_valid_elements : "i[*]",
            verify_html : false,
            menu : {},
            toolbar: "undo redo | styleselect | bold italic underline |  aligncenter alignjustify alignleft alignright | bullist numlist outdent indent | link | print | fontsizeselect | code | removeformat",
            autoresize_min_height:280,
            browser_spellcheck : true,
            init_instance_callback: function(){
               ac = tinyMCE.activeEditor;
               ac.dom.setStyle(ac.getBody(), 'fontSize', '13px');
            }
        };
        tinymce.init(
            tinymceObj
        );
    }

    $('#date').daterangepicker({
        'singleDatePicker': true,
        'format': globalDateFormat
    });

    if ($('#news_form').length > 0) {

    $.validator.addMethod("titleContent", function(value, element) {
        var result = false;
        if (value.indexOf('.') === -1) {
            result = true;
        }

        return result;
        }, "Title is invalid"
    );
        $('#news_form').validate({
            rules: {
                title: {
                    required: true,
                    titleContent : true
                },
                body: {
                    required: true
                },
                date: {
                    required: true
                }
            },
            highlight: function (element, errorClass, validClass) {
                $(element).closest('.form-group').removeClass('has-success').addClass('has-error');
            },
            unhighlight: function (element, errorClass, validClass) {
                $(element).closest('.form-group').removeClass('has-error').addClass('has-success');
            },
            success: function (label) {
                $(label).closest('form').find('.valid').removeClass("invalid");
            },
            errorPlacement: function (error, element) {}
        });
    }
});

$('#save_button').click(function () {
    var validate   = $('#news_form').validate();
    var btn        = $('#save_button');
    var labelField = $('#body').closest('div.form-group').find('label');

    tinymce.triggerSave();

    if (!$('#body').val()) {
        btn.button('reset');
        notification(
            {
                status: "error",
                msg: "Description field is empty."
            }
        );
        $(labelField).css('color', '#a94442');
        return false;
    } else {
        $(labelField).css('color', '#555');
    }

    if ($('#news_form').valid())
    {
        btn.button('loading');
        var obj = $('#news_form').serialize();
        $.ajax({
            type: "POST",
            url: GLOBAL_SAVE,
            data: obj,
            dataType: "json",
            success: function(data) {
                if(data.status == 'success'){
                    if(parseInt(data.id) > 0){
                        window.location.href = GLOBAL_BASE_PATH + 'news/edit/' + data.id;
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

function tryDeleteGroup(id, name){
    $('#edit_id').val(id);
    $('#delete_group').html(name);
}

$('#delete_button').click(function () {
    $.ajax({
        type: "POST",
        url: GLOBAL_DELETE,
        data: {id:$('#edit_id').val()},
        dataType: "json",
        success: function(data) {
            if(data.status == 'success'){
                window.location.href = GLOBAL_BASE_PATH + 'news';
            } else {
                notification(data);
            }
        }
    });
});

$('#title').click(function () {
    if ($('#edit_id').val()) {
        var data = [];
        data = {
            status: 'warning',
            msg: 'You are going to broke SEO (Search Engine Optimization).'
        };

        notification(data);
    }
});




