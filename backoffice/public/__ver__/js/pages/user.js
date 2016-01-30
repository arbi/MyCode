HAS_PENDING_EVALUATION = false;
HAS_PENDING_DOCUMENT_TAB = false;

$(function () {

    /************TAB*******************/

    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        tab_name = $(e.target).attr("data-tab-name");
        if (tab_name) {
            $(".page-actions .btn").hide();
            $(".page-actions .btn." + tab_name + "-tab-btn").show();
        }
    });

    var hash = window.location.hash;
    hash && $('ul.nav a[href="' + hash + '"]').tab('show');

    $('.nav-tabs a').click(function (e) {
        window.location.hash = this.hash;
    });

    if ($('#personal-tab').length > 0) {
        $('#personal-tab').click(function () {
            showFooterDefaultButtons();
        });
    }

    if ($('#administration-tab').length > 0) {
        $('#administration-tab').click(function () {
            showFooterDefaultButtons();
        });
    }

    if ($('#permission-tab').length > 0) {
        $('#permission-tab').click(function () {
            showFooterDefaultButtons();
        });
    }

    // Add New Evaluation
    if ($('#add-evaluation-tab').length > 0) {
        $('#add_evaluation').click(function () {
            HAS_PENDING_EVALUATION = true;
            activateAddNewEvaluationTab();
            $('#add-evaluation-tab').click();

        });

        $(document).delegate('#cancel_evaluation', 'click', function (e) {
            HAS_PENDING_EVALUATION = false;
            e.preventDefault();
            $('#evaluations-tab').click();
            $('#add-evaluation-tab').parent().hide();
            cleanAddEvaluationForm();
        });
    }

    // Plan Evaluation
    if ($('#plan-evaluation-tab').length > 0) {
        $('#plan_evaluation').click(function () {
            HAS_PENDING_EVALUATION = true;
            activatePlanEvaluationTab();
            $('#plan-evaluation-tab').click();

        });

        $(document).delegate('#cancel_plan_evaluation', 'click', function (e) {
            HAS_PENDING_EVALUATION = false;
            e.preventDefault();
            $('#plan-evaluation-tab').parent().hide();
            $('#evaluations-tab').click();
            cleanPlanEvaluationForm();
        });
    }

    // History Tab
    if ($('#history-tab').length > 0) {
        $('#history-tab').click(function () {
            hideFooterAllButtons();
            hideAllTabs();
        });
    }

    /*
     * Apply TinyMCE on Add Evaluation Form - Description Element
     */
    if ($('.tinymce').length > 0) {
        tinymce.init({
            selector: ".tinymce",
            skin: "clean",
            extended_valid_elements : "i[*]",
            verify_html : false,
            plugins: [
                "code", "autoresize", "link"
            ],
            menu: {},
            toolbar: "undo redo | styleselect | bold italic underline |  aligncenter alignjustify alignleft alignright | bullist numlist outdent indent | link | print | fontsizeselect | code | removeformat",
            autoresize_min_height: 280,
            browser_spellcheck : true
        });
    }

    for (var i = 1; $('#evaluation_item_' + i).length > 0; i++) {
        $('#evaluation_item_' + i).on('keyup change', function(){
            if (!$.isNumeric($(this).val()) || $(this).val() > 1 || $(this).val() < 0) {
                $(this).closest('.form-group').removeClass('has-success').addClass('has-error');
            } else {
                scoreAvg();
            }
        });

    }

    /**
     * Save New Evaluation
     */
    $('#save_evaluation').click(function (e) {
        e.preventDefault();

        var btn = $('#save_evaluation'),
                data = new FormData(),
                evalItem,
                evalItemValue;

        btn.button('loading');
        tinymce.triggerSave();

        data.append('user_id', $('#evaluation_user_id').attr('value'));
        data.append('creator_id', $('#evaluation_creator_id').attr('value'));
        data.append('type_id', $('#evaluation_type_id').val());
        data.append('description', $('#evaluation_description').val());

        if ($('#evaluation_type_id').val() == '3') {
            for (var i = 1; $('#evaluation_item_' + i).length > 0; i++) {
                evalItem = $('#evaluation_item_' + i);
                evalItemValue = evalItem.val();
                if (!$.isNumeric(evalItem.val()) ||evalItem.val() > 1 || evalItem.val() < 0) {
                    $(evalItem).closest('.form-group').removeClass('has-success').addClass('has-error');
                    btn.button('reset');
                    return false;
                }

                if (!parseFloat(evalItemValue)) {
                    evalItemValue = -1;
                }

                data.append('evaluation_item_' + i, evalItemValue);
            }
        }
        var labelField = $('#evaluation_description').closest('div.form-group').find('label');


        if (!$('#evaluation_description').val()) {
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

        $.ajax({
            url: GLOBAL_ADD_EVALUATION_URL,
            type: 'POST',
            data: data,
            contentType: false,
            processData: false,
            cache: false,
            success: function (data) {
                if (data.status == 'success') {
                    HAS_PENDING_EVALUATION = false;

                    notification(data);

                    $(".evaluation-table").dataTable().fnDraw();
                    $('#evaluations-tab').click();
                    $('#add-evaluation-tab').parent().hide();

                    cleanAddEvaluationForm();

                    btn.button('reset');
                } else {
                    notification(data);
                    btn.button('reset');
                }
            }
        });
    });

    /**
     * Plan Evaluation
     */
    $('#save_plan_evaluation').click(function (e) {
        e.preventDefault();

        var btn = $('#save_plan_evaluation'),
            data = new FormData();

        btn.button('loading');
        tinymce.triggerSave();

        data.append('user_id', $('#plan_user_id').val());
        data.append('creator_id', $('#plan_creator_id').val());
        data.append('evaluation_description', $('#plan_evaluation_description').val());
        data.append('date', $('#plan_date').val());

        var labelField = $('#plan_evaluation_description').closest('div.form-group').find('label');

        if (!$('#plan_evaluation_description').val()) {
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

        $.ajax({
            url: GLOBAL_PLAN_EVALUATION_URL,
            type: 'POST',
            data: data,
            contentType: false,
            processData: false,
            cache: false,
            success: function (data) {
                if (data.status == 'success') {
                    HAS_PENDING_EVALUATION = false;

                    notification(data);

                    $(".evaluation-table").dataTable().fnDraw();
                    $('#evaluations-tab').click();
                    $('#add-evaluation-tab').parent().hide();

                    cleanPlanEvaluationForm();

                    btn.button('reset');
                } else {
                    notification(data);
                    btn.button('reset');
                }
            }
        });
    });

    // Documents Tab
    if ($('#documents-tab').length > 0) {
        $('#documents-tab').click(function () {
            hideFooterAllButtons();
            hideAllTabs();
            $('#documents_datatable').DataTable().ajax.reload();
            $('#add_document').show();
        });
    }
    // Add New Document
    if ($('#add-document').length > 0) {

        $('#add_document').click(function () {
            HAS_PENDING_DOCUMENT_TAB = true;
            activateAddNewDocumentTab();
        });

        $(document).delegate('#cancel_document', 'click', function (e) {
            HAS_PENDING_DOCUMENT_TAB = false;
            e.preventDefault();
            $('#add-document-tab').parent().hide();
            $('#documents-tab').tab('show');
            cleanAddDocumentForm();
        });
    }


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
    $("#user-management").delegate("#uploadAttachment", "click", function(e) {
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
    $("#user-management").delegate("#deleteAttachment", "click", function(e) {
        e.preventDefault();
        // $("#validAttachment").rules("add", {number: false, min:0});

        if($('#remove-attachment').length > 0){
            $('#remove-attachment').click();
        } else {
            $('#document_attachment').val('');
            $('#attachmentFileName').remove();
            $('#uploadAttachmentSet').after($.buttons.uploadButton).remove();
        }
        $('div.file-name').hide();
    });

    // waiting action for download attachment file
    $("#user-management").delegate("#downloadAttachment", "click", function() {
        if($('#download-attachment').length > 0){
            $('#download-attachment').trigger('click');
        } else {

        }
    });
    /**
     * Save New Document
     */
    $('#save_document').click(function (e) {
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
            var btn = $('#save_document');
            btn.button('loading');

            var data = new FormData();
            data.append('user_id', $('#document_user_id').attr('value'));
            data.append('creator_id', $('#document_creator_id').attr('value'));
            data.append('type_id', $('#document_type_id').val());
            data.append('url', $('#document_url').val());
            data.append('description', $('#document_description').val());
            data.append('fileInfo', $('#document_attachment')[0].files[0]);

            $.ajax({
                url: GLOBAL_ADD_DOCUMENT_URL,
                type: 'POST',
                data: data,
                contentType: false,
                processData: false,
                cache: false,
                success: function (data) {
                    if (data.status == 'success') {
                        HAS_PENDING_DOCUMENT_TAB = false;
                        window.location.href = window.location.origin + window.location.pathname + window.location.search + '#documents-tab';
                        window.location.reload();
                    } else {
                        notification(data);
                        btn.button('reset');
                    }
                }
            });
        }

    });

    $('.delete-document').click(function (e) {
        e.preventDefault();

        $('#documentsRemoveModal').attr('data-src', $(this).attr('href')).modal('show');
    });

    $('.exact-remove-document-button').click(function (e) {
        e.preventDefault();

        var elem = $(this),
                url = $('#documentsRemoveModal').attr('data-src');

        elem.button('loading');

        $.ajax({
            url: url,
            type: "POST",
            contentType: false,
            processData: false,
            cache: false,
            success: function (data) {
                if (data.status == 'success') {
                    window.location.href = window.location.origin + window.location.pathname + window.location.search + '#documents-tab';
                    window.location.reload();
                } else {
                    elem.button('reset');
                    notification(data);
                }
            }
        });
    });

    $('#greenAttachmentButton').click(function (e) {
        e.preventDefault();

        $('#document_attachment').trigger('click');
    });

    $('.removeUploaded').click(function (e) {
        e.preventDefault();

        $('.document_file_name').val('');

        $('#greenAttachmentButton').show();
        $('.greenAttachmentButtonBigContainer').hide();
    });

    if ($(".evaluation-table").length) {
        $(".evaluation-table").dataTable({
            "bAutoWidth": false,
            "bFilter": true,
            "bInfo": false,
            "bLengthChange": false,
            "bPaginate": false,
            "bProcessing": false,
            "bServerSide": true,
            "bStateSave": false,
            "sAjaxSource": '/user-evaluation/get-user-evaluations',
            "oLanguage": {
                "sEmptyTable": "There are no evaluations yet."
            },
            "sPaginationType": "bootstrap",
            "aoColumnDefs": [{
                    'bSortable': false,
                    'aTargets': [3, 4]
                }],
            "fnServerParams": function (aoData) {
                var myObject = {
                    name: 'userId',
                    value: $('#evaluation_user_id').val()
                };

                aoData.push(myObject);
            }
        });
    }

    if ($(".documents-table").length) {
        $(".documents-table").dataTable({
            "bAutoWidth": false,
            "bFilter": true,
            "bInfo": false,
            "bLengthChange": true,
            "bPaginate": true,
            "bProcessing": false,
            "bServerSide": false,
            "bStateSave": false,
            "oLanguage": {
                "sEmptyTable": "No documents are assigned to this user"
            },
            "sPaginationType": "bootstrap",
            "aoColumnDefs": [{
                    'bSortable': false,
                    'aTargets': [2, 3, 4, 5, 6]
                }]
        });
    }

    if ($('#datatable_history').length) {
        $('#datatable_history').dataTable({
            bFilter: true,
            bInfo: true,
            bLengthChange: true,
            bServerSide: false,
            bProcessing: false,
            bPaginate: true,
            bAutoWidth: false,
            bStateSave: true,
            sAjaxSource: null,
            sPaginationType: "bootstrap",
            aaSorting: [[0, 'desc']],
            aaData: historyAaData,
            aoColumns: [{
                    name: "date",
                    width: "150px"
                }, {
                    name: "user",
                    width: "200px"
                }, {
                    name: "message",
                    sortable: false,
                    searchable: false
                }]
        });
    }

    // Check Asana Id
    $('.check-asana-id').click(function (e) {
        e.preventDefault();

        var self = $(this),
                emailValue = $('#email').val();

        if (emailValue == '') {
            notification({
                status: 'warning',
                msg: 'An email address is required for Asana, please fill it in first.'
            });
        } else {
            self.button('loading');

            // please check
            $.ajax({
                url: $(this).attr('data-url'),
                type: "POST",
                data: {
                    email: emailValue
                },
                cache: false,
                success: function (data) {
                    if (data.status == 'success') {
                        $('#asana_id').val(data.asana_id);
                    } else {
                        notification(data);
                    }

                    self.button('reset');
                }
            });
        }
    });

    // Skip evaluation button
    $('.skip-button').click(function (e) {
        e.preventDefault();

        var status = $(this).attr('data-status');

        if (parseInt(status)) {
            $(this)
                    .attr('data-status', 0)
                    .text('Activate');

            $(this).closest('.input-group').find('input')
                    .val(0)
                    .prop('disabled', true);
        } else {
            $(this)
                    .attr('data-status', 1)
                    .text('Skip');

            $(this).closest('.input-group').find('input').prop('disabled', false);
        }
        scoreAvg();

    });

    if ($('#vacationdays')) {
        var currentValue = $('#vacationdays').val() * 1;
        var roundedValue = currentValue.toFixed(2);

        $('#vacationdays').attr('data-current-value', currentValue);
        $('#vacationdays').attr('data-rounded-value', roundedValue);

        $('#vacationdays').val(roundedValue);
    };


    /**
     * Permissions Tree
     */

    function loladTree()
    {
        $('#jqxTree').jqxTree({ height: '500px', hasThreeStates: false, checkboxes: true, width: '100%'});
        $('.loading-tree').fadeOut(function(){
            $('#jqxTree').css('visibility', 'visible');
        });
    }

        loladTree();

    $('[href="#permission"]').click(function(){
        setTimeout(function(){
            $('#jqxTree').jqxTree('refresh');
        },100)

    });

    $('#search').keyup(function(){
        var needle = $(this).val().toLowerCase();
        $('#paneljqxTreeverticalScrollBar').jqxScrollBar('setPosition', 0);
        $('#jqxTree .jqx-tree-item-li').each(function(){
            var $self = $(this);

            if ($self.parents('.jqx-tree-item-li').length > 0) {
                return true;
            }

            var labelText = $self.find('.jqx-tree-item').first().text().toLowerCase();
            if (labelText.indexOf(needle) > -1) {
                $self.show();
            } else {
                $self.hide();
            }
            //search in children

            var childrenHasMatch = false;

            $self.find('.jqx-tree-item-li').each(function() {
                var $selfChildrenLevel = $(this);
                var labelText = $selfChildrenLevel.find('.jqx-tree-item').text().toLowerCase();
                if (labelText.indexOf(needle) > -1) {
                    childrenHasMatch = true;
                    //break;
                    return false;
                }
            });

            if (childrenHasMatch) {
                $self.show();
                //continue;
                return true;
            }
        });
    });

    $('#jqxTree').on('checkChange', function (event)
    {
        var args = event.args;
        var $element = $(args.element);
        var checked = args.checked;

        var isRootElement = $element.parents('.jqx-tree-item-li').length ? false : true;
        if (isRootElement && !checked) {
            $element.find('.jqx-tree-item-li').each(function() {
                var $self = $(this);
                $('#jqxTree').jqxTree('uncheckItem', $self[0]);
            });
        } else if (!isRootElement && checked) {
            var $parentElem = $element.parent().parent();
            $("#jqxTree").jqxTree('checkItem', $parentElem[0], true);
        }
    });

    $('#jqxTree').on('select',function (event)
    {
        var args = event.args;
        var $item =$(args.element);
        var description = $item.attr('data-description');
        $('#permission_discription').html(description);
    });

    /**
     * End Permissions Tree
     */

    $('#add-device').on('click', function (e) {
        e.preventDefault();

        $('#addDeviceModal').modal('show');
        $('#add-device-hash').focus();
    });

    $('#add-device-hash').on('keypress' , function (e) {
        if (e.which === 13) {
            e.preventDefault();

            $('#add-device-button').click();
        }
    });

    $('#add-device-button').on('click', function (e) {
        e.preventDefault();

        var btn = $('#add-device-button');
        btn.button('loading');

        $.ajax({
            type: "POST",
            url: GLOBAL_ADD_DEVICE_URI,
            data: {
                hash: $('#add-device-hash').val(),
                user_id: GLOBAL_EDITABLE_USER_ID
            },
            dataType: "json",
            cache: false,
            success: function (data) {
                if (data.status == 'success') {
                    location.reload();
                }

                notification(data);
            }
        });

        btn.button('reset');
    });

    $("#devices-table").delegate('.remove-device', 'click', function (e) {
        e.preventDefault();

        $('#unlink-device-id').val(this.id);
        $('#unlinkDeviceModal').modal('show');
    });

    $('#unlink-device-button').on('click', function (e) {
        var btn = $('#unlink-device-button');
        btn.button('loading');

        $.ajax({
            type: "POST",
            url: GLOBAL_UNLINK_DEVICE_URI,
            data: {
                device_id: $('#unlink-device-id').val()
            },
            dataType: "json",
            cache: false,
            success: function (data) {
                if (data.status == 'success') {
                    location.reload();
                }

                notification(data);
            }
        });

        btn.button('reset');
    });
});

$(window).on("beforeunload", function (e) {
    var confirmationMessage;
    if (HAS_PENDING_EVALUATION) {
        confirmationMessage = 'You have unsaved evaluations.';
        (e || window.event).returnValue = confirmationMessage;     //Gecko + IE
        return confirmationMessage;    //Webkit, Safari, Chrome etc.
    }
    if (HAS_PENDING_DOCUMENT_TAB) {
        confirmationMessage = 'You have unsaved documents.';
        (e || window.event).returnValue = confirmationMessage;     //Gecko + IE
        return confirmationMessage;    //Webkit, Safari, Chrome etc.
    }

});

function cleanAddEvaluationForm() {
    $('#add-evaluation-tab').parent().hide();
    $('#evaluations-tab').click();

    // reset form
    tinyMCE.activeEditor.setContent('');
    $('#evaluation_type_id').prop('selectedIndex', 0);
    $('#evaluation_description').val('');
    $('.evaluation-input-items').val('0');
    $('#evaluation-items').hide();
}

function cleanPlanEvaluationForm() {
    $('#plan-evaluation-tab').parent().hide();
    $('#evaluations-tab').click();

    // reset form
    tinyMCE.activeEditor.setContent('');
    $('#evaluation_description').val('');
    $('#plan_date').val('');
}

function cleanAddDocumentForm() {
    $('#add-document-tab').parent().hide();
    $('#documents-tab').tab('show');

    // reset form
    $('#document_type_id').prop('selectedIndex', 0);
    $('#document_attachment').val('');
    $('#document_file_name').val('');
    $('#document_url').val('');
    $('#document_description').val('');
}

function activateAddNewEvaluationTab() {
    $('#add-evaluation-tab').parent().show();
    $('#add-evaluation-tab').tab('show');


    if ($('#evaluation_type_id').val() == '3') {
        $('#evaluation-items').show();
        $('#score_sum').show();
    } else {
        $('#evaluation-items').hide();
        $('#score_sum').hide();
    }
}

function activatePlanEvaluationTab() {
    $('#plan-evaluation-tab').parent().show();
    $('#plan-evaluation-tab').tab('show');
}

function activateAddNewDocumentTab() {
    $('#add-document-tab').parent().show();
    $('#add-document-tab').tab('show');
}

function hideAllTabs() {
    $('.tab-content').find('div').removeClass('active');
    $('.tabs-general').find('li').removeClass('active');
}

function hideFooterAllButtons() {
    $('#add_evaluation').hide();
    $('#plan_evaluation').hide();
    $('#save_evaluation').hide();
    $('#cancel_evaluation').hide();

    $('#add_document').hide();
    $('#save_document').hide();
    $('#cancel_document').hide();

    $('#user_deactivate').hide();
    $('#user_button').hide();
    $('#user_welcome_send').hide();
    $('#user_view_profile').hide();
    $('#user_view_profile').hide();
    $('#activate_btn').hide();
}

function showFooterDefaultButtons() {
    // hide all buttons
    hideFooterAllButtons();

    // show only default buttons
    $('#user_deactivate').show();
    $('#user_button').show();
    $('#user_welcome_send').show();
    $('#user_view_profile').show();
    $('#activate_btn').show();
}



$("#user_button").click(function () {
    var validate = $('#user-management').validate();

    if ($('#user-management').valid()) {
        var btn = $('#user_button');
        btn.button('loading');

        var obj = $('#user-management').serializeArray();
        var memberGroups = new Array();

        $('.jqx-checkbox-check-checked').each(function(index) {
            var $self = $(this);
            $li = $self.closest('li.jqx-tree-item-li');
            var id = $li.attr('data-id');
            memberGroups.push(id);
        });

        obj.push({name: 'member_groups', value: memberGroups});
        obj.push({name: 'vacationdays_current_value', value: $('#vacationdays').attr('data-current-value')});
        obj.push({name: 'vacationdays_rounded_value', value: $('#vacationdays').attr('data-rounded-value')});

        $.ajax({
            type: "POST",
            url: GLOBAL_USER_SAVE,
            data: obj,
            dataType: "json",
            success: function (data) {
                if (data.status == 'success') {
                    if (parseInt(data.id) > 0) {
                        window.location.href = GLOBAL_BASE_PATH + 'user/edit/' + data.id;
                    } else {
                        location.reload();
                    }
                } else {
                    notification(data);
                    btn.button('reset');
                }
            }
        });
    } else {
        validate.focusInvalid();
        var personal_ids = ['firstname', 'lastname', 'email', 'password'];
        var schedule_ids = ['period-length'];
        var personalTab = false;
        var scheduleTab = false;
        for (var row in validate.errorList) {
            var item = validate.errorList[row];
            if ($.inArray(item.element.id, personal_ids) !== -1) {
                personalTab = true;
            } else if ($.inArray(item.element.id, schedule_ids) !== -1) {
                scheduleTab = true;
            }
        }

        if (personalTab) {
            $('.nav-tabs a[href="#personal"]').tab('show');
        } else if (scheduleTab) {
            $('.nav-tabs a[href="#schedule"]').tab('show');
        } else {
            $('.nav-tabs a[href="#administration"]').tab('show');
        }
    }
});

$("#user_delete_button").click(function () {
    var id = $('#user_hidden_id').val();

    if (parseInt(id) > 0) {
        $.ajax({
            type: "POST",
            url: GLOBAL_USER_DELETE,
            data: {id: id},
            dataType: "json",
            success: function (data) {
                if (data.rc == '00') {
                    window.location.href = GLOBAL_BASE_PATH + 'company-directory';
                }
            }
        });
    }
});

$('#activate_btn').click(function () {
    var id = $('#user_hidden_id').val();

    if (parseInt(id) > 0) {
        $.ajax({
            type: "POST",
            url: GLOBAL_USER_ACTIVATE_ALERT,
            data: {
                id: id
            },
            dataType: "json",
            success: function (data) {
                notification(data);
            }
        });
    }

});

$("#user_activate_button").click(function () {
    var id = $('#user_hidden_id').val();

    if (parseInt(id) > 0) {
        $.ajax({
            type: "POST",
            url: GLOBAL_USER_ACTIVATE,
            data: {
                id: id
            },
            dataType: "json",
            success: function (data) {
                if (data.rc == '00') {
                    window.location.href = GLOBAL_BASE_PATH + 'company-directory';
                }
            }
        });
    }
});

$("#user_send_button").click(function () {
    $('#sendModal').modal('hide');
    var btn = $('#user_welcome_send');
    btn.button('loading');
    var id = $('#user_hidden_id').val();
    $.ajax({
        type: "POST",
        url: GLOBAL_SEND_MAIL,
        data: {
            id: id
        },
        dataType: "json",
        success: function (data) {
            notification(data);
            btn.button('reset');
        }
    });
});


if ($('#clone_as').length) {
    $('#clone_as').autocomplete({
        source: function (request, response) {
            $.ajax({
                url: GLOBAL_CLONE_USER,
                data: {txt: $('#clone_as').val(), editable_user_id: GLOBAL_EDITABLE_USER_ID},
                dataType: "json",
                type: "POST",
                success: function (data) {
                    if (data && data.status == 'success') {
                        var resultAutocomplete = data.result;
                        response(
                                $.map(resultAutocomplete, function (item) {
                                    return {
                                        label: item.name,
                                        group: item.group
                                    }
                                })
                                );
                    }
                }
            })
        },
        max: 10,
        minLength: 1,
        autoFocus: true,
        select: function (event, ui) {
            if (ui.item) {

                $('#jqxTree').jqxTree('uncheckAll');
                $.each(ui.item.group, function (index, value) {
                    $li = $('li.jqx-tree-item-li[data-id="' + value + '"]');
                    $("#jqxTree").jqxTree('checkItem', $li[0], true);
                });

            }
        },
        search: function (event, ui) {

        },
        focus: function (event, ui) {
            event.preventDefault();
        }
    });
}


// Sum Avg Score
function scoreAvg() {
    var sum = 0;
    var count = 0;

    for (var i = 1; $('#evaluation_item_' + i).length > 0; i++) {
        sum += parseFloat($('#evaluation_item_' + i).val());
        if (parseFloat($('#evaluation_item_' + i).val()) > 0) {
            count += 1;
        }
    }
    if (count > 0) {
        var avg = sum / count;
        $('#score_sum span.badge').text(avg.toFixed(2));
    } else {
        $('#score_sum span.badge').text(0);
    }
}

/**************** User Account *********************/
$('#datatable_user_account_container').removeClass('hidden');
if (window.fTable) {
    fTable.fnReloadAjax();
} else {
    fTable = $('#datatable_user_account_info').dataTable({
        bAutoWidth: false,
        bFilter: true,
        bInfo: false,
        bPaginate: true,
        bProcessing: true,
        bServerSide: true,
        bStateSave: true,
        iDisplayLength: 25,
        sPaginationType: "bootstrap",
        sAjaxSource: DATATABLE_USER_ACCOUNTS_AJAX_SOURCE,
        sDom: 'l<"enabled">frti<"bottom"p><"clear">',
        aoColumns: [
            {
                name: "isDefault",
                "sClass" : "text-center",
                "bSortable": true
            }, {
                name: "name",
                "bSortable": true
            }, {
                name: "type",
                "bSortable": true
            }, {
                name: "fullLegalName",
                "bSortable": true
            }, {
                name: "addresses",
                "bSortable": false
            }, {
                name: "countryId",
                "bSortable": false
            }, {
                name: "iban",
                "bSortable": true
            }, {
                name: "swft",
                "bSortable": true
            }, {
                "name": "edit",
                "bSortable": false,
                "bSearchable": false,
                "sClass" : "text-center",
                "sWidth" : "15%"
            }
        ],
        "aoColumnDefs": [
            {
                "aTargets": [8],
                "fnCreatedCell": function (nTd, sData, oData, iRow, iCol) {
                    var $cell = $(nTd);
                    var value = $cell.text();
                    if (value !== "0")
                        $cell.html('<a href="javascript:void(0)" class="btn btn-xs btn-primary user_account_edit" data-id="' + value + '">Manage</a><a href="javascript:void(0)" class="btn btn-xs btn-danger user_account_archive" data-id="' + value + '">Archive</a>');
                    else
                        $cell.html('');
                }
            }
        ]
    });

    $("#datatable_user_account_info_wrapper div.enabled").html($('#status-switch-account').html());
    $('#status-switch-account').remove();

    $(document).on('click', '#datatable_user_account_info_wrapper .fn-buttons a', function(e) {
        e.preventDefault();

        var sentValue = null;

        $('.fn-buttons a').removeClass('active');
        $(this).addClass('active');

        switch ($(this).attr('data-status')) {
            case 'all':
                $("#show_status_account").attr('value', 1); break;
            case 'archived':
                $("#show_status_account").attr('value', 2); break;
        }

        fTable.fnSettings().aoServerParams.push({
            "fn": function (aoData) {
                aoData.push({
                    "name": "all",
                    "value":  $("#show_status_account").attr('value')
                });
            }
        });

        fTable.fnGetData().length;
        fTable.fnDraw();
    });
}

$(document).on('click', '#add_account, .user_account_edit', function(e) {
    var container = $('#edit-account');
    var tab       = $('#edit-account-tab');

    if ($(e.target).hasClass('user_account_edit')) {
        $('#edit-account-tab').text('Edit Account');
        var user_id = $('#add_scheme').data('user-id');
        var data = {
            'id': $(e.target).data('id'),
            'user_id' : user_id
        };
    } else {
        $('#edit-account-tab').text('Add Account');
        var data = {'user_id' : $(this).data('user-id')};
    }

    $.ajax({
        type: "POST",
        url: GLOBAL_USER_ACCOUNT_EDIT,
        data: data,
        dataType: "html",
        success: function (data) {
            container.html(data);
            tab.parent().css('display', 'block');
            tab.trigger('click');
            initUserAccountValidation();
        }
    });
});


$(document).on('click', '.user_account_archive', function() {
    var btn = $(this);
    var id  = btn.data('id');
    btn.button('loading');

    $.ajax({
        type: "POST",
        url: DATATABLE_USER_ACCOUNTS_ARCHIVE,
        data: {
            'id': id,
            'userId': $('#user_hidden_id').val()
        },
        dataType: "json",
        success: function (data) {
            if(data.url != '' && data.status == 'success'){
                window.location.href = data.url + '#salary';
                location.reload();
            } else {
                notification(data);
            }
            btn.button('reset');
        }
    });
});

function initUserAccountValidation()
{
    $('#user-account-form').validate({
        ignore: [],
        rules: {
            name: {
                required: true
            },
            type: {
                required: true,
                min: 1
            },
            fullLegalName: {
                required: true
            },
            mailingAddress: {
                required: true
            },
            countryId: {
                required: true,
                min: 1
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
        errorPlacement: function(error, element) {
        }
    });
}

$(document).on('click', '#save_user_account', function(e) {
    $(this).button('loading');

    if($('#user-account-form').valid()) {
        var data = $('#user-account-form').serializeArray();
        data.push({name: 'userId', value: $('#user_hidden_id').val()});
        saveUserAccountData(data);
    } else {
        $(this).button('reset');
    }
});

/**
 * Save user account data
 *
 * @param data
 */
function saveUserAccountData(data) {
    $.ajax({
        type: "POST",
        url: GLOBAL_USER_ACCOUNT_SAVE,
        data: data,
        dataType: "json",
        success: function(data) {
            if(data.url != '' && data.status == 'success'){
                window.location.href = data.url + '#salary';
                location.reload();
            } else {
                notification(data);
            }

            $("#user-account-form #save_user_account").button('reset');
        }
    });
}

/********************** Salary Scheme *************************/
$('#datatable_salary_scheme_container').removeClass('hidden');
if (window.gTable) {
    gTable.fnReloadAjax();
} else {
    gTable = $('#datatable_salary_scheme_info').dataTable({
        bAutoWidth: false,
        bFilter: true,
        bInfo: false,
        bPaginate: true,
        bProcessing: true,
        bServerSide: true,
        bStateSave: true,
        iDisplayLength: 25,
        sPaginationType: "bootstrap",
        sAjaxSource: DATATABLE_SALARY_SCHEME_AJAX_SOURCE,
        sDom: 'l<"enabled">frti<"bottom"p><"clear">',
        aoColumns: [
            {
                name: "status",
                "bSortable": true,
                "sClass" : "text-center"
            }, {
                name: "name",
                "bSortable": true
            }, {
                name: "type",
                "bSortable": true
            }, {
                name: "externalAccountId",
                "bSortable": true
            }, {
                name: "effectiveFrom",
                "bSortable": true
            }, {
                name: "payFrequencyType",
                "bSortable": true
            }, {
                name: "salary",
                "bSortable": true
            }, {
                "name": "edit",
                "bSortable": false,
                "bSearchable": false,
                "sClass" : "text-center",
                "sWidth" : "15%"
            }
        ],
        "aoColumnDefs": [
            {
                "aTargets": [7],
                "fnCreatedCell": function (nTd, sData, oData, iRow, iCol) {
                    var $cell = $(nTd);
                    var value = $cell.text();
                    if (value !== "0")
                        $cell.html('<a href="javascript:void(0)" class="btn btn-xs btn-primary salary_scheme_edit" data-id="' + value + '">Manage</a>' +
                            '       <a href="javascript:void(0)" class="btn btn-xs btn-danger salary_scheme_archive" data-id="' + value + '" data-status="' + SALARY_SCHEME_ARCHIVE_STATUS + '">Archive</a>');
                    else
                        $cell.html('');
                }
            }
        ]
    });

    $("#datatable_salary_scheme_info_wrapper div.enabled").html($('#status-switch-scheme').html());
    $('#status-switch-scheme').remove();

    $(document).on('click', '#datatable_salary_scheme_info_wrapper .fn-buttons a', function(e) {
        e.preventDefault();

        var sentValue = null;

        $('.fn-buttons a').removeClass('active');
        $(this).addClass('active');

        switch ($(this).attr('data-status')) {
            case 'all':
                $("#show_status_scheme").attr('value', 0); break;
            case 'active':
                $("#show_status_scheme").attr('value', 1); break;
            case 'inactive':
                $("#show_status_scheme").attr('value', 2); break;
            case 'archived':
                $("#show_status_scheme").attr('value', 3); break;
        }

        gTable.fnSettings().aoServerParams.push({
            "fn": function (aoData) {
                aoData.push({
                    "name": "all",
                    "value":  $("#show_status_scheme").attr('value')
                });
            }
        });

        gTable.fnGetData().length;
        gTable.fnDraw();
    });
}

$(document).on('click', '.salary_scheme_archive, .salary_scheme_inactivate', function() {
    var btn = $(this);
    var id  = btn.data('id');
    btn.button('loading');

    $.ajax({
        type: "POST",
        url: DATATABLE_SALARY_SCHEME_ARCHIVE,
        data: {
            'id': id,
            'userId': $('#user_hidden_id').val(),
            'status': $(this).data('status')
        },
        dataType: "json",
        success: function (data) {
            if(data.url != '' && data.status == 'success'){
                window.location.href = data.url + '#salary';
                location.reload();
            } else {
                notification(data);
            }
            btn.button('reset');
        }
    });
});

$(document).on('click', '#add_scheme, .salary_scheme_edit', function(e) {
    var container = $('#edit-scheme');
    var tab       = $('#edit-scheme-tab');

    if ($(e.target).hasClass('salary_scheme_edit')) {
        $('#edit-scheme-tab').text('Edit Scheme');
        var user_id = $('#add_scheme').data('user-id');
        var data = {
            'id': $(e.target).data('id'),
            'user_id': user_id
        };
    } else {
        $('#edit-scheme-tab').text('Add Scheme');
        var data = {'user_id': $(this).data('user-id')};
    }

    $.ajax({
        type: "POST",
        url: GLOBAL_USER_SALARY_SCHEME_EDIT,
        data: data,
        dataType: "html",
        success: function (data) {
            container.html(data);
            tab.parent().css('display', 'block');
            tab.trigger('click');
            initSalarySchemeValidation();

            container.find('.daterangepicker').daterangepicker({
                'singleDatePicker': true,
                'format': 'YYYY-MM-DD'
            });
        }
    });
});


function initSalarySchemeValidation()
{
    $('#salary-scheme-form').validate({
        ignore: [],
        rules: {
            name: {
                required: true
            },
            payFrequencyType: {
                required: true,
                min: 1
            },
            externalAccountId: {
                required: true,
                min: 1
            },
            type: {
                required: true,
                min: 1
            },
            salary: {
                required: true,
                number: true
            },
            currencyId: {
                required: true,
                min: 1
            },
            countryId: {
                required: true,
                min: 1
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
        errorPlacement: function(error, element) {
        }
    });
}

$(document).on('click', '#save_salary_scheme', function(e) {
    $(this).button('loading');

    if($('#salary-scheme-form').valid()) {
        var data = $('#salary-scheme-form').serializeArray();
        data.push({name: 'userId', value: $('#add_scheme').data('user-id')});
        saveSalarySchemeData(data);
    } else {
        $(this).button('reset');
    }
});

/**
 * Save salary scheme data
 *
 * @param data
 */
function saveSalarySchemeData(data) {
    $.ajax({
        type: "POST",
        url: GLOBAL_SALARY_SCHEME_SAVE,
        data: data,
        dataType: "json",
        success: function(data) {
            if(data.url != '' && data.status == 'success'){
                window.location.href = data.url + '#salary';
                location.reload();
            } else {
                notification(data);
            }

            $("#salary-scheme-form #save_salary_scheme").button('reset');
        }
    });
}

/**
 * Don`t display footer-buttons
 */
$(document).on('shown.bs.tab', '#user-management .nav-tabs a', function() {
    if ($('#edit-account-tab').parent().hasClass('active') || $('#edit-scheme-tab').parent().hasClass('active')) {
        $('#footer-buttons').hide();
    } else {
        $('#footer-buttons').show();
    }
});

$(document).on('click', '#cancel_salary_scheme, #cancel_user_account', function() {
    var url = $(this).attr('href');
    window.location.href = url;
    location.reload();
});
