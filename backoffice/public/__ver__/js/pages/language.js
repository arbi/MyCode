$(function() {
    /** Datatable configuration */
    if (jQuery().dataTable) {
        gTable = $('#datatable_languages').dataTable({
            bFilter: true,
            bInfo: true,
            bServerSide: false,
            bProcessing: false,
            bPaginate: true,
            bAutoWidth: true,
            bStateSave: true,
            iDisplayLength: 25,
            sAjaxSource: "/language/get-languages-json",
            sPaginationType: "bootstrap",
            aoColumns:[
                {
                    name: "id",
                    sortable: false,
                    width: "4%"
                }, {
                    name: "iso_code",
                    width: "12%"
                }, {
                    name: "name",
                    width: "72%"
                }, {
                    name: "status",
                    sortable: false,
                    searchable: false,
                    width: "12%"
                }
            ],
            aoColumnDefs: [
                {
                    aTargets: [3],
                    fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {
                        var $cell = $(nTd);
                        var value = $cell.text();
                        if ( value == 1) {
                            $cell.html('<div class="text-center"><span class="label label-info">Enabled</span></div>');
                        }else {
                            $cell.html('<div class="text-center"><span class="label label-warning">Disabled</span></div>');
                        }
                    }
                }
            ]
        });
    }

    if ($('.tinymce').length > 0) {
        var tinymceObj = {
            selector: ".tinymce",
            skin: "clean",
            plugins: [
                "code", "autoresize", "link"
            ],
            menu : {},
            extended_valid_elements : "i[*]",
            verify_html : false,
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


    var rmEvent = 'removeImg()';
	$.buttons = {};

    if (parseInt($('#edit_id').val()) > 0) {
        rmEvent = 'removeImges(' + parseInt($('#edit_id').val()) + ')';
    }

    $.buttons.imgButtonSet = '\
        <div class="btn-group pull-left">\
            <button type="button" class="btn btn-success" id="imgButton" name="imgButtonSet">\
                <i class="icon-upload icon-white"></i>\
                Upload Image File\
            </button>\
            <button class="btn btn-success dropdown-toggle helper-margin-right-04em" data-toggle="dropdown">\
                <span class="caret"></span>\
            </button>\
            <ul class="dropdown-menu">\
                <li>\
                    <a onclick="'+rmEvent+'">\
                        <i class="icon-remove-circle icon-black"></i>\
                        Delete Image File\
                    </a>\
                </li>\
            </ul>\
        </div>';

    $.buttons.imgButton = '\
        <button type="button" id="imgButton" class="btn btn-success" name="imgButton">\
            <i class="icon-upload icon-white"></i>\
            Upload Image File\
        </button>';


    if ($('#img_attachment-container').length > 0) {
	    // if Image file already uploaded
        $('#img').after($.buttons.imgButtonSet);
    } else {
        // if Image file still not uploaded
        $('#img').after($.buttons.imgButton);
    }

    $("#blog_form").delegate("#imgButton", "click", function() {
        $('#img').trigger('click');
    });

    var attachment = $('#img');
    attachment.change(function handleFileSelect(evt) {
        var self = $(this);
        var files = evt.target.files; // FileList object

        // Loop through the FileList and render image files as thumbnails.
        for (var i = 0, f; f = files[i]; i++) {
            // Only process image files.
            if (!f.type.match('image.*')) {
                    continue;
            }

            var reader = new FileReader();

            // Closure to capture the file information.
            reader.onload = (function(theFile) {
                    return function(e) {
                            var img = new Image;

            if ($('#img_attachment-container')) {
                $('#img_attachment-container').remove();
            }

            img.src = e.target.result;
            img.onload = function() {
                if($('#imgButton').attr('name') === 'imgButton'){
                    $('#imgButton').after($.buttons.imgButtonSet).remove();
                }

                var ratio = img.height / img.width;
                var img_height = ((180 * ratio) / 2) - 22;

                var container =
                                '<div id="img_attachment-container" class="clear left mt10">' +
                                        '<img style="width: 100px;" src="' + e.target.result + '">' +
                                '</div>';

                $('#img_preview').append(container);
            };
                        };
            })(f);

            // Read in the image file as a data URL.
            reader.readAsDataURL(f);
	}

        var data = new FormData();
        data.append('file', $('#img')[0].files[0]);

        $.ajax({
            url: GLOBAL_UPLOAD_IMG,
            type: "POST",
            data: data,
            contentType: false,
            processData: false,
            cache: false,
            success: function(data){
                if (data.status == 'success') {
                   $("#img_post").val(data.src);
                } else {
                    notification(data);
                }
            }
        });
	});
});

$('#save_button').click(function() {
    var validate = $('#blog_form').validate();
    if ($('#blog_form').valid())
    {
        if($('#edit_title').val() !='' && $('#edit_title').val() != $('#title').val()){
            $('#changeUrlModal').modal();
            return;
        }
        saveBlog();
    } else {
        validate.focusInvalid();
    }
});

$('#save_modal_button').click(function () {
    $('#changeUrlModal').modal('hide');
    saveBlog();
});

function saveBlog() {
    var btn = $('#save_button');
    btn.button('loading');
    tinymce.triggerSave();
    var obj = $('#blog_form').serialize();

    $.ajax({
        type: "POST",
        url: GLOBAL_SAVE,
        data: obj,
        dataType: "json",
        success: function(data) {
            if (data.status == 'success') {
                if (parseInt(data.id) > 0) {
                    window.location.href = GLOBAL_BASE_PATH + 'blog/edit/' + data.id;
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

function removeImg() {
    var button = $.buttons.imgButton;

    $('#img_post').val('');
    $('#img_preview').html('');
    $('#imgButton').parent().remove();
    $('#img').after(button);
}

function removeImges(id) {
    $.ajax({
        type: "POST",
        url: GLOBAL_DELETE_IMG,
        data: {
                id: id
        },
        dataType: "json",
        success: function(data) {
                if (data.status == 'success') {
                        removeImg();
        notification(data);
                } else {
                        notification(data);
                }
        }
    });
}

$(function() {
    if ($('#blog_form').length > 0) {
        $('#blog_form').validate({
            rules: {
                title: {
                        required: true
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

function tryDeleteGroup(id, name) {
    $('#edit_id').val(id);
    $('#delete_group').html(name);
}

$('#delete_button').click(function() {
    $.ajax({
        type: "POST",
        url: GLOBAL_DELETE,
        data: {
	        id: $('#edit_id').val()
        },
        dataType: "json",
        success: function(data) {
            if (data.status == 'success') {
                window.location.href = GLOBAL_BASE_PATH + 'blog';
            } else {
                notification(data);
            }
        }
    });
});





