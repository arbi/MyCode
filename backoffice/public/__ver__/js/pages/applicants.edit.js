var participantsSelectize;
$(function() {
    $('.nav-tabs a').click(function (e) {
        $(this).tab('show');
        var scrollmem = $('body').scrollTop();
        window.location.hash = this.hash;
        $('html,body').scrollTop(scrollmem);
    });

    participantsSelectize = $(".participants-selectize").selectize();
    participantsSelectize = participantsSelectize[0].selectize;


    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        tab_name = $(e.target).attr("data-tab-name");
        $(".page-actions .btn").hide();
        $(".page-actions .btn." + tab_name + "-tab-btn").show();

        if (tab_name == 'applicant') {
            $('#downloadAttachment').show();
        } else {
            $('#downloadAttachment').hide();
        }
    });

    var hash = window.location.hash;

    if (hash = '#applicant_details') {
        $('#downloadAttachment').show();
    } else {
        $('#downloadAttachment').hide();
    }

    hash && $('ul.nav a[href="' + hash + '"]').tab('show');

    $(".datetimepicker").datetimepicker({
        format: 'M j, Y H:i',
        step: 15
    });

    /** Datatable configuration */
    if (jQuery().dataTable) {
        gTable = $('#datatable_history').dataTable({
            bFilter: true,
            bInfo: true,
            bServerSide: false,
            bProcessing: false,
            bPaginate: true,
            bAutoWidth: false,
            bStateSave: false,
            iDisplayLength: 25,
            sAjaxSource: null,
            sPaginationType: "bootstrap",
            aaSorting: [[0, 'desc']],
            aaData: COMMENT_AADAtA,
            aoColumns:[
                {
                    name: "date",
                    width: "150"
                }, {
                    name: "commenter"
                }, {
                    name: "comment"
                }
            ]
        });

        gTableInterviews = $('#datatable_interview').dataTable({
            bFilter: true,
            bInfo: true,
            bServerSide: false,
            bProcessing: false,
            bPaginate: true,
            bAutoWidth: false,
            bStateSave: false,
            iDisplayLength: 25,
            sAjaxSource: '/recruitment/applicants/get-interviews?applicant_id=' + $("#applicant_id").val(),
            ajax: '/recruitment/applicants/get-interviews?applicant_id=' + $("#applicant_id").val(),
            sPaginationType: "bootstrap",
            aaSorting: [[0, 'desc']],
            aoColumns:[
                {
                    name: "interviewer"
                }, {
                    name: "from",
                    width: "145px"
                }, {
                    name: "to",
                    width: "145px"
                }, {
                    name: "place"
                }, {
                    name: "status",
                    class: "hide"
                }, {
                    name: "interviewerId",
                    class: "hide"
                }, {
                    name: "action",
                    width: 1,
                    sortable: false,
                    searchable: false,
                    visible: INTERVIEW_MANAGER
                }
            ],
            "fnInitComplete": function(oSettings, json) {
                if (json.aaData.length == 0) {
                    $('#datatable_interview_table').hide();
                    $('#have_not_interview').show();
                }
            }
        });
    }

    $('#save_button').click(function() {
        var validate = $('#applicant_manage_table').validate();

        if ($('#applicant_manage_table').valid()) {

            var btn = $('#save_button');
            btn.button('loading');
            var obj = $('#applicant_manage_table').serialize();

            $.ajax({
                type: "POST",
                url: GLOBAL_SAVE_DATA,
                data: obj,
                dataType: "json",
                success: function(data) {
                    if (data.status == 'success') {
                        if (parseInt(data.id) > 0) {

                            window.location.hash = '#history_details';
                            window.location.reload(true);
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

    $("#applicant-status").change( function() {
        $.ajax({
            type: "POST",
            url: CHANGE_APPLICANT_STATUS,
            data: {
                status: $(this).val(),
                id: $("#applicant_id").val()
            },
            dataType: "json",
            success: function(data) {
                window.location.hash;
                window.location.reload();
            }
        });
    });

    $("#add-interview-btn").click(function() {
        $("#interview-modal-action").text("Add");
        $("#interview-edit-modal form")[0].reset();
        participantsSelectize.clear();
        $("#interview-edit-modal").modal("show");
    });

    $( "body" ).delegate(".edit-interview", "click", function(e) {
        e.preventDefault();
        $("#interview-modal-action").text("Edit");
        $("#interview-edit-modal form")[0].reset();
        participantsSelectize.clear();
        var tr = $(this).closest("tr");
        $("#interview_id").val($(this).attr('data-id'));
        $("#from").val(tr.find("td:nth-child(2)").text());
        $("#to").val(tr.find("td:nth-child(3)").text());
        $("#place").val(tr.find("td:nth-child(4)").text());
        var interviewers = tr.find("td:nth-child(6)").text().split(', ');
        $.each(interviewers, function( index, value ) {
            participantsSelectize.addItem(value);
        });
        $("#interview-edit-modal").modal("show");
    });

    $("#save-interview").on("click", function() {
        var form = $("#interview-edit-modal form");
        if(form.valid()) {
            var data = form.serialize();
            $.ajax({
                type: "POST",
                url: GLOBAL_SAVE_INTERVIEW,
                data: data,
                success: function(data) {
                    location.reload();
                },
                error: function() {
                    notification({
                        status: 'error',
                        msg: 'Server Error. Please try again.'
                    })
                }
            });
            $("#interview-edit-modal").modal("hide");
            return false;
        }
    });

    var attachment = $('#attachment_doc');
    $.buttons = {};

    $.buttons.uploadButtonSet = '\
        <div class="btn-group pull-left dropup" id="uploadAttachmentSet">\
            <button type="button" class="btn btn-success" id="uploadAttachment" type="button" aria-haspopup="true" aria-expanded="false">\
                <span class="glyphicon glyphicon-upload"></span>\
                Upload Resume\
            </button>\
            <button class="btn btn-success dropdown-toggle" data-toggle="dropdown" type="button" aria-haspopup="true" aria-expanded="false">\
                <span class="caret"></span>\
            </button>\
            <ul class="dropdown-menu" aria-labelledby="downloadAttachment">\
                <li>\
                    <a id="deleteAttachment">\
                        <span class="glyphicon glyphicon-remove-circle"></span>\
                        Delete Resume\
                    </a>\
                </li>\
            </ul>\
        </div>';

    $.buttons.uploadButtonFullSet = '\
        <div class="btn-group pull-left dropup" id="uploadAttachmentFullSet">\
            <button type="button" class="btn btn-success" id="downloadAttachment" type="button" aria-haspopup="true" aria-expanded="false">\
                <span class="glyphicon glyphicon-download"></span>\
                Download Resume\
            </button>\
            <button class="btn btn-success dropdown-toggle" data-toggle="dropdown" type="button" aria-haspopup="true" aria-expanded="false">\
                <span class="caret"></span>\
            </button>\
            <ul class="dropdown-menu" aria-labelledby="downloadAttachment">\
                <li>\
                    <a id="deleteAttachment">\
                        <span class="glyphicon glyphicon-remove-circle"></span>\
                        Delete Resume\
                    </a>\
                    <a id="uploadAttachment">\
                        <span class="glyphicon glyphicon-upload"></span>\
                        Upload New Resume\
                    </a>\
                </li>\
            </ul>\
        </div>';

    $.buttons.uploadButtonReadOnly = '\
        <div class="btn-group pull-left dropup" id="uploadAttachmentFullSet">\
            <button type="button" class="btn btn-success" id="downloadAttachment" type="button" aria-haspopup="true" aria-expanded="false">\
                <span class="glyphicon glyphicon-download"></span>\
                Download Resume\
            </button>\
        </div>';

    $.buttons.uploadButton = '\
        <button type="button" id="uploadAttachment" class="btn btn-success">\
            <span class="glyphicon glyphicon-upload"></span>\
            Upload Resume\
        </button>';


    // find out whether there is already uploaded file
    if (READ_ONLY_MODE) {
        $('#attachment_doc').after($.buttons.uploadButtonReadOnly);
        $('#upload-cv-btn').hide();
    } else if ($('#download-attachment').length  > 0) {
       $('#attachment_doc').after($.buttons.uploadButtonFullSet);
       $('#upload-cv-btn').hide();
    } else {
        $('#attachment_doc').after($.buttons.uploadButton);
    }

    // waiting for select a file to upload
    $("#applicant_manage_table").delegate("#uploadAttachment", "click", function() {
        $('#attachment_doc').trigger('click');
    });


    // select upload file
    $('#attachment_doc').change(function(e){
       $('#upload-cv-btn').show();

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
    $("#applicant_manage_table").delegate("#deleteAttachment", "click", function(e) {
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
        $('#upload-cv-btn').hide();
    });

    // waiting action for delet attachment file
    $("#applicant_manage_table").delegate("#downloadAttachment", "click", function() {
        if($('#download-attachment').length > 0){
            $('#download-attachment').trigger('click');
        } else {

        }
    });

    $('#upload-cv-btn').click(function (){
        var form_data   = new FormData();
        var applicantId = $('#applicant_id').val();
        var attachment  = $('#attachment_doc')[0].files[0];

        form_data.append('id', applicantId);
        form_data.append('cv', attachment);

        var btn = $('#upload-cv-btn');
        btn.button('loading');

        $.ajax({
            type: "POST",
            url: GLOBAL_UPLOAD_CV,
            dataType: "json",
            cache: false,
            contentType: false,
            processData: false,
            data: form_data,
            success: function(data) {
                if (data.status == 'success') {
                    window.location.hash = '#applicant';
                    location.reload();
                } else {
                    notification(data);
                    btn.button('reset');
                }
            }
        });
    });
});
