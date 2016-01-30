$(function() {
    var $subtasks = $('#subtasks');
    var $responsibleId = $('#responsible_id');
    var $verifierId = $('#verifier_id');
    var $helperIds = $('#helper_ids');
    var $followerIds = $('#follower_ids');
    var $propertyId = $('#property_id');
    var $propertyName = $('#property_name');
    var $buildingId = $('#building_id');
    var $buildingName = $('#building_name');
    var $resId = $('#res_id');
    var $resNumber = $('#res_number');
    var $file = $('#file');
    var $attachmentNames = $('#attachment_names');
    var $teamId = $('#team_id');
    var $taskType = $('#task_type');
    var $attachmentList = $('#attachments-list');
    var $panelHeading = $('#panelAdvanced .panel-heading');
    var $tags = $('#tags');
    var $titleTag = $('.main-title a.glyphicon-tags');

    $('.datetimepicker').datetimepicker({
        format: 'M j, Y H:i',
        step: 30
    });

    batchAutocomplete('building_name', 'building_id', GLOBAL_BUILDING);
    getApartmentAutocomplete();

    /** Datatable configuration */
    if (historyAaData.length > 0) {
            if (jQuery().dataTable) {
                $.fn.dataTableExt.afnFiltering.push(
                    function (oSettings, aData, iDataIndex) {
                        var myRowClass = oSettings.aoData[iDataIndex].nTr.className;
                        var checkSelectedButton = $('.history-switch a.active');

                        if (checkSelectedButton.hasClass('all') || myRowClass.indexOf('warning') == -1) {
                            return true;
                        }

                        return false;
                    }
                );

                gTable = $('#datatable_history').DataTable({
                    bFilter: true,
                    bInfo: true,
                    bServerSide: false,
                    bProcessing: false,
                    bPaginate: true,
                    bAutoWidth: false,
                    bStateSave: false,
                    iDisplayLength: 10,
                    sAjaxSource: null,
                    sPaginationType: "bootstrap",
                    aaSorting: [[0, 'desc']],
                    aaData: historyAaData,
                    sDom: 'l<"enabled">frti<"bottom"p><"clear">',
                    aoColumns: [
                        {
                            "name": "date",
                            "width": "150px"
                        }, {
                            "name": "user",
                            "width": "200px"
                        }, {
                            "name": "message",
                            "sortable": false
                        }
                    ]
                });

                $('.fn-buttons a').on('click', function (e) {
                    e.preventDefault();

                    $(this).closest('.history-switch').find('.fn-buttons a').removeClass('active');
                    $(this).addClass('active');
                    gTable.draw();
                });
            }
    } else {
         $('#history-legend').hide();
         $('#datatable_history').hide();
         $('.history-switch').hide();
    }

    setInterval(function() {
        if ($('#progress .progress-bar').text() == '100%') {
            setTimeout(function(){
                $('#progress').hide();
            }, 1000)
        }
    }, 2000);

    if ($resNumber.val()) {
        $propertyName.prop("readonly", true);
        $buildingName.prop("readonly", true);
    }

    if($propertyId.val() != 0) {
        $buildingName.prop("readonly", true);
    }

    $propertyName.change(function() {
        if (!$(this).val()) {
            $propertyId.val('');
            $buildingName.prop("readonly", false);
        }
    });

    $tags.selectize({
        create: GLOBAL_CAN_ADD_TAGS,
        plugins: ['remove_button'],
        valueField: 'id',
        labelField: 'name',
        searchField: ['name'],
        sortField: [
            {
                field: 'name'
            }
        ],
        options: GLOBAL_ALL_TAGS,
        render: {
            item: function(option, escape) {
                if (!option.style) {
                    option.style = 'label-grey';
                }
                return '<span class="label ' + option.style + '" style="margin-right: 2px"' +
                    '><span class="glyphicon glyphicon-tag"></span> ' + option.name + '</span>';
            }
        }
    });

    $('#responsible_id, #verifier_id, #helper_ids, #follower_ids').each( function(index) {
        $(this).html('');
        $(this).selectize({
            create: false,
            plugins: ['remove_button'],
            valueField: 'id',
            labelField: 'name',
            searchField: ['name'],
            sortField: [
                {
                    field: 'name'
                }
            ],
            options: USER_OPTIONS,
            render: {
                option: function(option, escape) {
                    return '<div>'
                        + '<img src="' + option.avatar + '" title="' + option.name + '" class="ginosik-avatar-selectize">'
                        + '<span class="selectize-ginosik-name"> ' + escape(option.name) + ' </span>'
                        + '</div>';
                },
                item: function(option, escape) {
                    return '<div>'
                        + '<img src="' + option.avatar + '" title="' + option.name + '" class="ginosik-avatar-selectize">'
                        + '<span class="selectize-ginosik-name"> ' + escape(option.name) + ' </span>'
                        + '</div>';
                }
            }
        });
    });

    $responsibleId[0].selectize.clear();
    $verifierId[0].selectize.clear();
    $verifierId[0].selectize.addOption(allUsers[autoVerifyUserId]);

    if (EDIT) {
        if (responsibleId) {
            $responsibleId[0].selectize.addOption(allUsers[responsibleId]);
            $responsibleId[0].selectize.addItem(responsibleId);
        }

        if (verifierId) {
            $verifierId[0].selectize.addOption(allUsers[verifierId]);
            $verifierId[0].selectize.addItem(verifierId);
        }

        $.each(helperIds, function(index, value) {
            $helperIds[0].selectize.addOption(allUsers[value]);
            $helperIds[0].selectize.addItem(value);
        });
        $.each(followerIds, function(index, value) {
            $followerIds[0].selectize.addOption(allUsers[value]);
            $followerIds[0].selectize.addItem(value);
        });

        function findTagById(id) {
            for (var v in GLOBAL_ALL_TAGS) {
                if (GLOBAL_ALL_TAGS[v].id == id) {
                    return GLOBAL_ALL_TAGS[v];
                }
            }
        }

        $.each(GLOBAL_SELECTED_TAGS, function(index, value) {
            $tags[0].selectize.addOption(findTagById(value));
            $tags[0].selectize.addItem(value);
        });

    } else {
        $verifierId[0].selectize.addItem(autoVerifyUserId);
    }

    $subtasks.delegate(".subtask-description", "keydown", function(e) {
        var itemNumber = $subtasks.children().length;
        var currRow = $(this).closest(".row");

        switch (e.which) {
            case 13:
                currRow.after(generateSubtask(itemNumber));
                currRow.next().find(".subtask-description").focus();
                break;
            case 40:
                currRow.next().find(".subtask-description").focus();
                break;
            case 38:
                currRow.prev().find(".subtask-description").focus();
                break;
            case 8:
                if ($(this).val() == '' && (currRow.prev().find(".subtask-description").length || currRow.next().find(".subtask-description").length)) {
                    if (currRow.prev().find(".subtask-description").length) {
                        currRow.prev().find(".subtask-description").focus();
                    } else {
                        currRow.next().find(".subtask-description").focus();
                    }

                    currRow.slideUp("slow").remove();
                    e.preventDefault();
                }
        }
    });

    $subtasks.delegate('.remove-subtask:not(.disabled)', "click", function(e) {
        var currRow = $(this).closest(".row");

        if(currRow.prev().find(".subtask-description").length || currRow.next().find(".subtask-description").length) {
            if (currRow.prev().find(".subtask-description").length) {
                currRow.prev().find(".subtask-description").focus();
            } else {
                currRow.next().find(".subtask-description").focus();
            }

            currRow.slideUp("slow").remove();
            e.preventDefault();
        }
    });

    $subtasks.delegate("input:checkbox", "click", function(e) {
        $(this).closest(".row").toggleClass("subtask-done");
    });

    $resNumber.on('change keyup', function () {
        var resNumber = $(this).val();

        if (resNumber) {
            $.ajax({
                url: GLOBAL_RES_DATA,
                data: {
                    resNumber: resNumber
                },
                dataType: 'json',
                type: 'post',
                success: function ( data ) {
                    if (data.status == "success") {
                        $resId.val(data.id);
                        $propertyId.val(data.apartmentId);
                        $propertyName.val(data.apartmentName);
                        $propertyName.attr("readonly", "readonly");
                        $buildingName.attr("readonly", "readonly");

                        if (data.buildingId) {
                            $buildingId.val(data.buildingId);
                            $buildingName.val(data.buildingName);
                        }

                        $propertyId.trigger('change');
                    }
                }
            });
        } else {
            $propertyName.removeAttr("readonly");
            $resId.val('');
        }

        $propertyId.trigger('change');
    });

    $(".print-btn").click(function(e) {
        e.preventDefault();
        var $history = $("#history-block");
        var $formControls = $(".form-control");
        var $assigedTeams  = $('#team_id');

        $formControls.css("border", "none");
        $formControls.css("box-shadow", "none");

        if ($(this).attr('data-comments') != '1') {
            $history.hide();
        }

        if ($assigedTeams.val() == 0) {
            $assigedTeams.parent().parent().addClass('hidden-print');
        } else {
            $assigedTeams.parent().parent().removeClass('hidden-print');
        }

        var $description = $('#description');

        if ($description.val() == '') {
            $description.closest('.form-group').parent().parent().addClass('hidden-print');
        } else {
            $description.closest('.form-group').parent().parent().removeClass('hidden-print');
        }

        $('#subtasks .subtask-description').each(function(index){
            if ($(this).val() == '' || $(this).val() == 'SUBTASK_TEXT') {
                $(this).closest('.row').addClass('hidden-print');
            } else {
                $(this).closest('.row').removeClass('hidden-print');
            }
        });

        window.print();
        $formControls.css("border", "");
        $formControls.css("box-shadow", "");
        $history.show();
    });

    $("#upload-button").click(function() {
        $('html, body').animate({
            scrollTop: $("#description").offset().top
        }, 800);
        $file.trigger("click");
    });

    // If in creation mode
    if (!EDIT) {
        //Link to team based on type and target changes
        $('#task_type, #property_id, #building_id').change(function() {
            assignTeamByTypeAndTarget();
        });

        // Put reasonable subtasks based on task type
        $taskType.change(function() {
            var type = $(this).val();

            $subtasks.find('.auto-generated').remove();
            $.ajax({
                url: GLOBAL_GET_SUBTASKS_BASED_ON_TYPE,
                data: {
                    type: type
                },
                dataType: 'json',
                type: 'post',
                success: function ( data ) {
                    if (data.subtasks) {
                        var subtaskHTML = '';
                        var $legend = $subtasks.find("legend");
                        $.each(data.subtasks, function(index, text) {
                            if (text) {
                                subtaskHTML = generateSubtask(index, text, 'auto-generated');
                                $legend.after(subtaskHTML);
                            }
                        });
                    }
                }
            });

            //Set Auto Verify as task verifier based on task type
            $.ajax({
                url: GLOBAL_CHECK_VERIFIABLE,
                data: {
                    type: type
                },
                dataType: 'json',
                type: 'post',
                success: function ( data ) {
                    if (data.status == 'success') {
                        if (data.is_verifiable == 1) {
                            if (!$verifierId.val()) {
                                $verifierId[0].selectize.addItem(autoVerifyUserId);
                            }
                        } else {
                            if ($verifierId.val() == autoVerifyUserId) {
                                $verifierId[0].selectize.clear();
                            }
                        }
                    }
                }
            });
        });

        // Link default team members, officers if any
        // $teamId.change(function () {
        //     var $self = $(this);
        //     var teamId = $self.val();
        //
        //     if (teamId && teamId != '0') {
        //         $.ajax({
        //             url: GLOBAL_GET_DEFAULT_MEMBER,
        //             data: {
        //                 team_id: teamId
        //             },
        //             dataType: 'json',
        //             type: 'post',
        //             success: function (data) {
        //
        //                 if (typeof data.defaultMemberId != 'undefined' && parseInt(data.defaultMemberId) != 0) {
        //                     $responsibleId[0].selectize.clear();
        //                     $responsibleId[0].selectize.addOption(allUsers[data.defaultMemberId]);
        //                     $responsibleId[0].selectize.addItem(data.defaultMemberId);
        //                 }
        //                 if ($verifierId.val() != autoVerifyUserId && typeof data.defaultOfficerId != 'undefined' && parseInt(data.defaultOfficerId) != 0) {
        //                     $verifierId[0].selectize.clear();
        //                     $verifierId[0].selectize.addOption(allUsers[data.defaultOfficerId]);
        //                     $verifierId[0].selectize.addItem(data.defaultOfficerId);
        //                 }
        //
        //                 if ($verifierId.val() != autoVerifyUserId && typeof data.defaultOfficerId != 'undefined' && parseInt(data.defaultOfficerId) == 0) {
        //                     $verifierId[0].selectize.clear();
        //                 }
        //
        //                 if (typeof data.defaultMemberId != 'undefined' && parseInt(data.defaultMemberId) == 0) {
        //                     $responsibleId[0].selectize.clear();
        //                 }
        //             }
        //         });
        //     }
        // });
    } else {
        var teamId = $teamId.val();
        if (teamId && teamId != '0') {
            $responsibleId[0].selectize.addOption(allUsers[anyTeamMemberId]);
        }
    }

    $teamId.change(function () {
        var teamId = $(this).val();

        if (teamId && teamId != '0') {
            $responsibleId[0].selectize.addOption(allUsers[anyTeamMemberId]);
        } else {
            $responsibleId[0].selectize.removeOption(anyTeamMemberId, true);
        }
    });

    $file.on('change', function(e) {
        e.preventDefault();
        $('.btn').addClass('disabled');
        hideErrors();
        if ($file.val() == '') {
            showErrors('No file(s) selected');
            return;
        }
        //$.fn.ajaxSubmit.debug = true;
        $(this).closest("form").ajaxSubmit({
            url: '/task/upload-attachments',
            type: 'post',
            target: '#output',
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                //Upload progress
                xhr.upload.addEventListener("progress", function(evt){
                    if (evt.lengthComputable) {
                        var percentComplete = Math.round(evt.loaded / evt.total * 100);
                        //Do something with upload progress
                        showProgress(percentComplete, percentComplete + '%');
                    }
                }, false);
                return xhr;
            },
            success: function (response, statusText, xhr, $form) {
                $('#upload-button').removeProp('disabled');
                if (response.status == 'success') {
                    var attachmentNames = $attachmentNames.val().split('###');
                    $.each(response.attachments, function(key, attachment) {
                        attachmentNames.push(attachment);
                        $attachmentList.append(
                            '<li class="attachment-item attachment-temp">' +
                                '<div class="btn-group">' +
                                    '<a href="#" class="btn btn-sm btn-default dropdown-toggle attachment-btn" data-toggle="dropdown" aria-expanded="false">' +
                                        '<span class="glyphicon glyphicon-paperclip"></span> ' + attachment + ' <span class="caret"></span>' +
                                    '</a>' +
                                    '<ul class="dropdown-menu" role="menu">' +
                                        '<li>' +
                                            '<a href="#" class="delete-attachment-btn">' +
                                                '<span class="glyphicon glyphicon-remove-circle text-danger"></span> Delete' +
                                            '</a>' +
                                        '</li>' +
                                    '</ul>' +
                                '</div>' +
                            '</li>'
                        );
                        $('#legend-for-appartments').show();
                    });
                    $attachmentNames.val(attachmentNames.join('###'));
                } else {
                    notification(response);
                }

                $file.val('');
                $('.btn').removeClass('disabled');
            },
            error: function(a, b, c) {
                $('.btn').removeClass('disabled');
                $file.val('');
            }
        });
    });

    $attachmentList.delegate('.download-attachment-btn', 'click', function(e) {
        e.preventDefault();
        var attachmentId = $(this).closest('.attachment-item').attr('data-attachment-id');
        $downloadBtn = $('#download_button');
        $downloadBtn.val(GLOBAL_DOWNLOAD_ATTACHMENT + '/' + attachmentId);
        $downloadBtn.click();
    });

    $attachmentList.delegate('.delete-attachment-btn', 'click', function(e) {
        e.preventDefault();
        var attachmentId = $(this).closest('.attachment-item').attr('data-attachment-id');
        if (attachmentId) {
            $('#delete-attachment-button').attr('data-attachment-id', attachmentId);
            $('#delete-modal').modal('show');
        } else {
            var $item = $(this).closest('.attachment-item');
            attachmentNames = $attachmentNames.val().split('###');
            attachmentNames.splice( $.inArray($item.find('.attachment-btn').text(), attachmentNames), 1 );
            $attachmentNames.val(attachmentNames.join('###'));
            $item.fadeOut().remove();

	        var currentAttachmentCount = $('#attachments-list li.attachment-item').length;

	        if (currentAttachmentCount == 0) {
                $('#legend-for-appartments').hide();
            }
        }
    });

    $('#delete-attachment-button').click(function() {
        var attachmentId = $(this).attr('data-attachment-id');
        $.ajax({
            url: GLOBAL_DELETE_ATTACHMENT,
            data: {
                attachmentId: attachmentId
            },
            dataType: 'json',
            type: 'post',
            success: function ( data ) {
                if (data.status == "success") {
                    notification(data);
                    $('#delete-modal').modal('hide');
                    $('#attachment-' + attachmentId).fadeOut().remove();

	                var currentAttachmentCount = $('#attachments-list li.attachment-item').length;

	                if (currentAttachmentCount == 0) {
                        $('#legend-for-appartments').hide();
                    }
                }
            }
        });
    });

    if ($('#task_status').val() == STATUS_STARTED && $responsibleId[0].selectize.getValue() == anyTeamMemberId) {
        notification({
            status: 'warning',
            msg: 'This task has been started.'
        })
    }

    $panelHeading.click(function(){
        var isCollapsed = $(this).find('.my-chevron').hasClass('glyphicon-chevron-down');
        var $chevron = $(this).find('.my-chevron');
        if(!isCollapsed){
            $chevron
                .removeClass('glyphicon-chevron-up')
                .addClass('glyphicon-chevron-down');
        }
        else{
            $chevron
                .removeClass('glyphicon-chevron-down')
                .addClass('glyphicon-chevron-up');
        }
    });

    /**
     * Show tag input, when click in main title tag icon
     */
    $titleTag.click(function() {
        $(this).hide();
        var $input = $tags.next().find('.selectize-input');

        $input.trigger('click');
        $input.focusout(function(){
            $titleTag.show();
        });
    });

    /**
     * Hide tag, when focus in input
     */
    $tags.next().find('.selectize-input').on('click blur focus focusout', function(e){
        if (e.type == 'focusout') {
            $titleTag.show();
        } else {
            $titleTag.hide();
        }
    })
});

state_loading('save_button', function() {
    var weHaveNotClosedSubTask = false;
    var $btn = $('#save_button');
    var taskStatus = $('#task_status').val();

    $('#subtasks .row .checkbox input[type="checkbox"]').each(function(index, value) {
        var subtaskDescription = $(this).closest('.row').find('.subtask-description').val();
        var isChecked = $(this).is(':checked');

	    if (!isChecked && subtaskDescription.length) {
            weHaveNotClosedSubTask = true;
            return false;
        }
    });

    if (weHaveNotClosedSubTask && (
            taskStatus == STATUS_DONE ||
            taskStatus == STATUS_VERIFIED
        )
    ) {
        $btn.button('reset');
        $('.status-change-button').button('reset');
        $('#open-subtasks-modal').modal('show');
    } else {
        var $taskForm = $('#task-form');
        var validate = $taskForm.validate();

        if ($taskForm.valid()) {
            var obj = $taskForm.serializeArray();

            $.ajax({
                type: "POST",
                url: GLOBAL_SAVE,
                data: obj,
                dataType: "json",
                success: function(data) {
                    if(data.status == 'success'){
                        window.location.href = GLOBAL_BASE_PATH + 'task/edit/' + data.id;
                    } else {
                        notification(data);
                        $btn.button('reset');
                        $('.status-change-button').button('reset');
                    }
                }
            });
        } else {
            validate.focusInvalid();
            $btn.button('reset');
        }
    }
});

$('#save-task-also-close-subtasks').click(function() {
    $('#subtasks .row .checkbox input[type="checkbox"]').each(function(index, value) {
        var subtaskDescription = $(this).closest('.row').find('.subtask-description').val();

        if (subtaskDescription.length) {
            $(this).attr('checked','checked');
        }
    });

    $('#save_button').click();
});

state_loading('checkout-btn', function() {
    var $btn = $('#checkout-btn');
    $.ajax({
        type: "POST",
        url: GLOBAL_CHECKOUT,
        data: {
            reservationId: reservationId
        },
        dataType: "json",
        success: function(data) {
            if(data.status == 'success'){
                $btn.button('reset').remove();
            } else {
                $btn.button('reset');
            }
            notification(data);
        }
    });

});

state_loading('.status-change-button', function(e) {
    e.preventDefault();
    $('#task_status').val($(e.target).attr('data-value'));
    $('#save_button').click();
});

function getApartmentAutocomplete(){
    var $propertyId = $('#property_id');
    var $propertyName = $('#property_name');
    var $buildingId = $('#building_id');
    var $buildingName = $('#building_name');

    $propertyName.autocomplete({
		source: function(request, response) {
			$.ajax({
				url: GLOBAL_PROPERTY,
				data: {txt: $propertyName.val()},
				dataType: "json",
				type: "POST",
				success: function( data ) {
					if(data && data.rc == '00'){
                        var resultAutocomplete = data.result;
                        response(
                            $.map(resultAutocomplete, function(item) {
                                return {
                                    id: item.id,
                                    label: item.name,
                                    apartmentGroup: item.apartmentGroup,
                                    buildingId: item.buildingId
                                }
                            })
                        );
					}
				}
			})
		},
		max:10,
		minLength: 1,
		autoFocus: true,
		select: function(event, ui) {
            if (ui.item) {
                $propertyId.val(ui.item.id);
                $buildingName.val(ui.item.apartmentGroup).prop("readonly", true);
                $buildingId.val(ui.item.buildingId);

	            if (!EDIT) {
                    assignTeamByTypeAndTarget();
                }
            }
		},
		search: function(event, ui) {
            $propertyId.val('');
		},
        focus: function(event, ui) {
            event.preventDefault();
        }
	});
}

function assignTeamByTypeAndTarget() {
    var $taskType = $('#task_type');
    var data = {} ;
    var type = $taskType.val();
    var apartmentId = $('#property_id').val();
    var buildingId = $('#building_id').val();

    if (type) {
        data.type = type;
    }

    if (apartmentId) {
        data.apartmentId = apartmentId;
    }

    if (buildingId) {
        data.buildingId = buildingId;
    }

    $.ajax({
        url: GLOBAL_ASSIGN_TEAM,
        data: data,
        dataType: "json",
        type: "POST",
        success: function(data) {
            if (data && data.team_id) {
                $("#team_id").val(data.team_id);
                $("#team_id").trigger('change');
            }
        }
    });
}

function generateSubtask(itemNumber, itemText, itemClass) {
    if (typeof itemText === 'undefined') {
        itemText = '';
    }

    if (typeof itemClass === 'undefined') {
        itemClass = '';
    }

    $generatedSubtask = $('#subtask-template').clone();
    $generatedSubtask.html(
            $generatedSubtask.html()
                .replace(/SUBTASK_NUMBER/g, itemNumber)
                .replace(/SUBTASK_TEXT/g, itemText)
        )
        .switchClass('SUBTASK_CLASS', itemClass)
        .removeClass('soft-hide')
        .removeAttr('id');

    return $generatedSubtask;
}

/**
 * Error Message functions
 */
function hideErrors() {
    $('#file-controls').removeClass('error');
    $('#file-errors').hide();
}
function showErrors(message) {
    $('#file-controls').addClass('error');
    $('#file-errors').show().html(message);
}
/**
 * Progress Bar functions
 */

function showProgress(amount, message) {
    $progress = $('#progress');
    $progress.show();
    $progress.find('.progress-bar').width(amount + '%').text(message);

    if (amount < 100) {
        $progress.find('.progress')
            .addClass('active')
            .addClass('progress-info')
            .removeClass('progress-success');
    } else {
        $progress.find('.progress')
            .removeClass('active')
            .removeClass('progress-info')
            .addClass('progress-success');
    }
}

$(document).on('keyup','.subtask-description', function() {
    var $elem = $(this);
    var $cross = $elem.parent().find('.glyphicon-remove');
    if ($elem.val() != '') {
        $cross
            .addClass('text-danger')
            .removeClass('text-muted')
            .parent()
                .removeClass('disabled');
    } else {
        $cross
            .removeClass('text-danger')
            .addClass('text-muted')
            .parent()
                .addClass('disabled');
    }
});
