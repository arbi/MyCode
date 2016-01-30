$(window).resize(function() {
    $(".upload-plus").css('height', ($(".upload-plus").width() * 300 / 455 + 10)  + 'px'); // Please don't ask me about this calculation :D
    $(".upload-plus").css('line-height', $(".upload-plus").height() + 'px');
});

$(function() {
    loadImages();

    $('#images_context').delegate('#uploadButton', 'click', function() {
        $('#uploadImages').trigger('click');
    });

    var attachment = $('#uploadImages');

    attachment.change(function (evt) {
        var upload_count = evt.target.files.length,
	        allowed_count = 32 - $("#images-sort li").length;

        if (upload_count > allowed_count) {
            var data = {
                status: "error",
                msg: "Max allowed images count is 32. Please make sure to select no more than " + allowed_count + " images or delete existing ones to add some space."
            };

            notification(data);
            return false;
        }
        
        var files = document.getElementById('uploadImages').files;
        
        if (files.length > 5) {
            var data = {
                status: "error",
                msg: "Forbidden to upload more than 5 files at the same time"
            };

            notification(data);
            return false;
        }

        showLoader();
        disableButtons();
        $(this.form).submit();
    });


    $("#upload-form").ajaxForm({
         url: 'media/ajax-upload-images',
         dataType: 'json',
         contentType: false,
         processData: false,
         cache: false,
         success: function(data) {
             if (data.msg !== undefined) {
                 notification(data);
             }

             loadImages();
             hideLoader();
             enableButtons();
         },
         error: function(data) {
             data.status = "error";
             notification(data);
             hideLoader();
             enableButtons();
         }
    });

    $("#apartment_media").ajaxForm({
        dataType:  'json',
        success: function() {
            location.reload(false);
        }
    });

    $('.modal .btn').click(function(e) {
        e.preventDefault();
        $(this).parents('.modal').modal('hide');
    });

    $('#image-delete .btn-danger').click(function(e) {
        e.preventDefault();

        var ids = $(this).attr('data-id');

        $.ajax({
            url: 'media/ajax-delete-images',
            type: "POST",
            data: {imageNumbers: ids},
            cache: false,
            success: function(data) {
                if (data.msg !== undefined) {
                    data.status = "success";
                    notification(data);
                    loadImages();
                }
            }
        });
    });

    $("#images_context").delegate(".sortable-image img", "click", function(e) {
       e.preventDefault();
       var src = $(this).prop("src").replace("_445", "_orig"),
	       big_img ='<img src="'+src+'">';

       $("#image-dialog .modal-body").html(big_img);
       $("#image-dialog").modal();
    });


    $(document).delegate('#selectAll', 'click', function(event) {
        if ($("#selectAll").attr("data-type") === "check") {
            $('.sortable-image input[type=checkbox]').each(function() {
                this.checked = true;
            });

            $("#selectAll").attr("data-type","uncheck");
            $("#selectAll").text("Unselect All");

        } else {
            $('.sortable-image input[type=checkbox]').each(function() {
                this.checked = false;
            });

            $("#selectAll").attr("data-type","check");
            $("#selectAll").text("Select All");

        }
    });

});

function loadImages() {
    $.post("media/ajax-get-images", {size:'445'}, function(data) {
        var images = '<ul id="images-sort">',
	        imgCount = 1;

	    if (data.status == 'success') {
		    data = data.images;

	        for (var i = 1; i <= 32; i++) {
	            if (data['img' + i] != '') {
	                images += '\
	                    <li class="sortable-image col-sm-3" id="img' + imgCount + '">\
	                        <div>\
	                            <img src="' + data['img' + i] + '">\
	                            <div class="label label-primary">' + imgCount + '</div>\
	                            <input type="checkbox" name="delete[' + imgCount + ']">\
	                            <input type="hidden" name="iid" value="' +imgCount + '">\
	                        </div>\
	                    </li>';
	            } else {
	                break;
	            }

	            imgCount++;
	        }

	        if (imgCount < 33) {
	            images += '<div class="images-upload col-sm-3" id="uploadButton"><div class="upload-plus text-center" title="Upload more images">' +
	                '<span class="glyphicon glyphicon-plus"></span></div></div>';
	        }

	        images += '</ul>';

	        $('#images_context').html(images);

	        $('#images-sort').sortable({
	            update: function (event, ui) {
	                getSortValues();
	            }
	        }).disableSelection();

	        $('#images-sort').sort(function(a, b) {
	            return $(a).data('sid') > $(b).data('sid');
	        }).appendTo('#images-sort');

	        $(".upload-plus").css('height', ($(".upload-plus").width() * 300 / 455 + 10)  + 'px'); // Please don't ask me about this calculation :D
	        $(".upload-plus").css('line-height', $(".upload-plus").height() + 'px');
	    } else {
		    notification(data);
	    }
    });

    $("#deleteImages").click(function(e) {
        e.preventDefault();

        var delete_ids = [];

        $(".sortable-image input[type=checkbox]").each(function(index) {
            if ($(this).prop("checked")) {
                delete_ids.push(
	                $(this).parent().children("input[type=hidden]").val()
                );
            }
        });

        if (delete_ids.length) {
            del(delete_ids);
        } else {
            var data = {
                msg: "No images selected. Please make sure to select the images you want to delete.",
                status: "warning"
            }
            notification(data);
        }
    });

    $("#selectAll").attr("data-type","check");
}

function getSortValues() {
    var values = [];
    $('#images-sort > .sortable-image').each(function (index) {
        values.push($(this).attr("id").replace("img", ""));
    });

    reSort(values);
}

function reSort(values) {
    $.ajax({
        url: 'media/ajax-set-sort',
        type: "POST",
        data: {values: values},
        cache: false,
        success: function(data) {
        	if (data.msg !== undefined) {
                data.status = 'success';
                notification(data);
	            loadImages();
        	}
        }
    });
}

function del(ids) {
	$('#image-delete .btn-danger').attr('data-id', ids);
	$('#image-delete').modal();
}

function showLoader() {
    $("#loader-dialog").modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

function hideLoader() {
    $("#loader-dialog").modal('hide');
}

function disableButtons() {
    $( "input[type=button], input[type=submit], a[type=button], button" ).each(function(index) {
        $(this).prop('disabled', true);
    });
}

function enableButtons() {
    $( "input[type=button], input[type=submit], button" ).each(function(index) {
        $(this).prop('disabled', false);
    });
}
