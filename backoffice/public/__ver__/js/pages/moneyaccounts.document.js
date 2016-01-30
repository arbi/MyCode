$(function() {
    $('#saveNewAttachBtn').hide();

    $.buttons = {};

    $("#uploadAttachment").click(function() {

        var newEl = $('.base .uploaded_files').clone();
        $('#reservoir').append(newEl);

        newEl.trigger('click');
    });

    // select upload file
    $('#reservoir').change(function(e) {
        var uploadfiles = e.target.files;
        $.each(uploadfiles, function(index, item) {
            $('.attach_files').append('<li class="list-group-item attachmentFileName">'+item.name+'</li>');
        });
            $('#deleteFiles').show();
            $('#saveNewAttachBtn').show();
    });

    $('#deleteFiles').click(function() {
        $('#reservoir').find('input').remove();
        $('.attach_files').find('li').remove();
        $('#saveNewAttachBtn').hide();
    });

    $("td").delegate("a.deleteAttachment", "click", function(e) {
       e.preventDefault();
       $('#delete_data').attr('data-docid', $(this).data('docid'));
       $('#delete_data').attr('data-moneyaccountid', $(this).data('moneyaccountid'));
       $("#delete-dialog").modal();
    });

    $("#delete_button").click(function () {
        var moneyAccountId = $('#delete_data').data('moneyaccountid'),
	        docId = $('#delete_data').data('docid');

        $.ajax({
            type: "POST",
            url: GLOBAL_DELETE_ATACHMENT,
            dataType: "json",
            data: {
                'doc_id': docId,
                'money_account_id': moneyAccountId
            },
            success: function(data) {
                if (data.status == 'success') {
                    location.reload();
                } else {
                    notification(data);
                }
                notification(data);
            }
        });
    });

    $( ".saveNewAttachBtn" ).click(function() {
        tinymce.triggerSave();

        var form_data = new FormData(),
	        description = $('#doc_description').val(),
	        moneyAccountId = $('#money_account_id').val(),
	        btn = $(this);

        if (description == '') {
            notification({
	            status : "error",
	            msg : "Description is empty."
            });
            btn.button('reset');
        }

        form_data.append('doc_description', description);
        form_data.append('money_account_id', moneyAccountId);

        var reserv = $('#reservoir'),
            z = 0;

        reserv.find('.uploaded_files').each(function(index, item) {
            var fileList = $(this);

            $.each(fileList.prop('files'), function (key, value) {
                form_data.append(z++, value);
            });
        });
        btn.button('loading');

        $.ajax({
            type: "POST",
            url: GLOBAL_UPLOAD_FILES,
            dataType: "json",
            cache: false,
            contentType: false,
            processData: false,
            data: form_data,
            success: function(data) {
                if (data.status == 'success') {
                    window.location.hash = '#account_attachments';
                    location.reload();
                } else {
                    notification(data);
                    btn.button('reset');
                }
            }
        });

    });

    $("td").delegate("a.image-resize img", "click", function(e) {
       e.preventDefault();

       var src = $(this).prop("src").replace("_96", "_orig"),
           big_img ='<img src="' + src + '" width="100%">';

       $("#image-dialog .modal-body").html(big_img);
       $("#image-dialog").modal();
    });
});
