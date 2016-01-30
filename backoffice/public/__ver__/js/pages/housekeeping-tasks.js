var defaultSelectizeSettings = {
    create: false,
    valueField: 'id',
    labelField: 'name',
    searchField: ['name'],
    sortField: [
        {
            field: 'name'
        }
    ]
};
var verifierSelectizeSettings = jQuery.extend(true, {
    render: {
        option: function (option, escape) {
            return '<div>'
                + '<span class="label label-primary">V</span> '
                + '<img src="' + option.avatar + '" title="' + option.name + '" class="ginosik-avatar-selectize">'
                + '<span class="selectize-ginosik-name"> ' + escape(option.name) + ' </span>'
                + '</div>';
        },
        item: function (option, escape) {
            return '<div>'
                + '<span class="label label-primary">V</span> '
                + '<img src="' + option.avatar + '" title="' + option.name + '" class="ginosik-avatar-selectize">'
                + '<span class="selectize-ginosik-name"> ' + escape(option.name) + ' </span>'
                + '<small class="selectize-remove-item glyphicon glyphicon-remove"></small>'
                + '</div>';
        }
    },
    options: officersList
}, defaultSelectizeSettings);

var responsibleSelectizeSettings = jQuery.extend(true, {
    render: {
        option: function (option, escape) {
            return '<div>'
                + '<span class="label label-success">R</span> '
                + '<img src="' + option.avatar + '" title="' + option.name + '" class="ginosik-avatar-selectize">'
                + '<span class="selectize-ginosik-name"> ' + escape(option.name) + ' </span>'
                + '</div>';
        },
        item: function (option, escape) {
            return '<div>'
                + '<span class="label label-success">R</span> '
                + '<img src="' + option.avatar + '" title="' + option.name + '" class="ginosik-avatar-selectize">'
                + '<span class="selectize-ginosik-name"> ' + escape(option.name) + ' </span>'
                + '<small class="selectize-remove-item glyphicon glyphicon-remove"></small>'
                + '</div>';
        }
    }
}, defaultSelectizeSettings);

$(function() {
    var $taskContainers = $('.housekeeping-tasks-day');
    var $viewToggleButtons = $('.tasks-view-toggles .btn');
    var $sortToggleButtons = $('.tasks-sort-toggles .btn');

    // Date: default sorting
    $('.tasks-sort-toggles .btn[data-value=0]').addClass('active');

    $taskContainers.delegate('.hk-container .accordion-toggle', 'click', function(event) {
        $(this).closest('.panel').collapse();
    });

    $taskContainers.delegate('.panel-title-container', 'click', function (event) {
        var target = $(event.target);
        if (!target.closest(".selectize-control").length && !target.hasClass('btn-assign-to-me')) {
            $(this).closest('.panel-heading').next().collapse('toggle');
        }
    });

    $taskContainers.delegate('.panel-collapse', 'show.bs.collapse', function () {
        var $panel = $(this).closest('.panel');
        $panel.toggleClass('in', true);
        getHousekeepingTask($panel.attr('data-task-id'));
        $(this)
            .closest('.panel')
                .find('.glyphicon-triangle-bottom')
                    .switchClass('glyphicon-triangle-bottom', 'glyphicon-triangle-top');
    });

    $taskContainers.delegate('.panel-collapse', 'hide.bs.collapse', function () {
        var $panel = $(this).closest('.panel');
        $panel.toggleClass('in', false);
        $panel
            .find('.glyphicon-triangle-top')
                .switchClass('glyphicon-triangle-top', 'glyphicon-triangle-bottom');
    });

    $taskContainers.delegate('.task-body .btn-status-change', 'click', function(e) {
        e.preventDefault();
        var $self  = $(this);
        var $panel = $self.closest('.panel');
        var status = $(this).attr('data-status');
        var taskId = $panel.attr('data-task-id');

        $.ajax({
            type: "POST",
            url: CHANGE_STATUS,
            data: {
                task_id: taskId,
                status: status
            },
            dataType: "json",
            success: function (data) {
                notification(data);
                if (data.status == 'success' && typeof data.task_status != 'undefined') {
                    $panel.find('.status-label').text(data.task_status);
                    if (typeof data.task_status_id != 'undefined') {

                        if (parseInt(data.task_status_id) == STATUS_VERIFIED || parseInt(data.task_status_id) == STATUS_DONE) {
                            $self.closest('.hk-container').find('.subtasks li').each(function(index, value) {
                                $(this).addClass('text-muted');
                                $(this).find('.subtask-checkbox').removeClass('glyphicon-unchecked').addClass('glyphicon-check');
                            });
                        }
                        $panel.find('[data-status="' + data.task_status_id +'"]').remove();
                        if (parseInt(data.task_status_id) == STATUS_VERIFIED) { //Verified
                            $panel.find('[data-status="5"]').remove(); //remove Done
                        }

                        if (parseInt(data.task_status_id) == STATUS_VERIFIED || parseInt(data.task_status_id) == STATUS_CANCELED) {
                            $panel.toggleClass('panel-faded', true);
                        }
                    }
                    $self.remove();
                }
            }
        });
    });

    $taskContainers.delegate('.incident-report-buttons a', 'click', function(e) {
        e.preventDefault();
        var btn = $(this);
        if (btn.hasClass('report-btn-other')) {
            $('#other-incident-description').val('');
            $('#incident-report-other-modal').modal('show');
        } else {
            reportIncident(btn);
        }
    });

    $taskContainers.delegate('#incident-report-other-button', 'click', function(event) {
        event.preventDefault();
        var $modal = $(this).closest('.modal');
        reportIncident($('.incident-report-buttons a.report-btn-other'));
        $modal.modal('hide');
    });

    $taskContainers.delegate('.subtask-checkbox:not(.disabled)', 'click', function (e) {
        var subTaskId = $(this).attr('data-subtask-id');
        var subTaskDescription = $(this).attr('data-subtask-description');
        var taskId = $(this).closest('.task-panel').attr('data-task-id');
        var status = $(this).prop('checked');
        var $self = $(this);

        if ($self.hasClass('glyphicon-check')) {
            status = 0;
        } else {
            status = 1;
        }

        $.ajax({
            type: "POST",
            url: CHANGE_SUBTASK_STATUS,
            data: {
                subtask_id: subTaskId,
                subtask_description: subTaskDescription,
                task_id: taskId,
                status: status
            },
            dataType: "json",
            success: function (data) {
                notification(data);
                if (data.status == 'success') {
                    if ($self.hasClass('glyphicon-check')) {
                        $self
                            .switchClass('glyphicon-check', 'glyphicon-unchecked')
                            .parent()
                            .removeClass('text-muted');
                    } else {
                        $self
                            .switchClass('glyphicon-unchecked', 'glyphicon-check')
                            .parent()
                            .addClass('text-muted');
                    }
                }
            }
        });
    });

    $taskContainers.delegate('.comment-input-field', 'keypress', function (e) {
        if (e.which == 13) {
            var $panel = $(this).closest('.panel');
            saveComment($panel);
        }
    });

    $taskContainers.delegate('.comment-submit-btn', 'click', function() {
        var $panel = $(this).closest('.panel');
        saveComment($panel);
    });

    $taskContainers.delegate('.btn-assign-to-me', 'click', function() {
        var $self = $(this);
        var $panel = $self.closest('.task-panel');

        saveStaff(
            userId,
            $panel.attr('data-task-id'),
            STAFF_VERIFIER
        );

        $self.parent().append(
            '<div class="selectize-control form-control">' +
                '<div class="selectize-input disabled">' +
                    '<div>' +
                        '<span class="label label-primary"> V </span>' +
                        '<img src="' + allUsers[userId]['avatar'] + '" title="' + allUsers[userId]['name'] + '" class="ginosik-avatar-selectize">' +
                        allUsers[userId]['name'] +
                    '</div>' +
                '</div>' +
            '</div>'
        );
        $self.remove();
    });

    $taskContainers.delegate('.btn-responsible-assign-to-me', 'click', function() {
        $self = $(this);

        $panel = $self.closest('.task-panel');
        saveStaff(
            userId,
            $panel.attr('data-task-id'),
            STAFF_RESPONSIBLE
        );

        $self.parent().append(
            '<div class="selectize-control form-control">' +
                '<div class="selectize-input disabled">' +
                    '<div>' +
                        '<span class="label label-primary"> V </span>' +
                        '<img src="' + allUsers[userId]['avatar'] + '" title="' + allUsers[userId]['name'] + '" class="ginosik-avatar-selectize">' +
                        allUsers[userId]['name'] +
                    '</div>' +
                '</div>' +
            '</div>'
        );
        $self.remove();
    });
    $taskContainers.delegate('.selectize-remove-item', 'click', function(e) {
        var $select = $(this).closest('.selectize-control').prev();
        setTimeout(function() {
            $select[0].selectize.clear();
        }, 10);
    });

    var viewRecent   = localStorage.getItem('housekeeping-view-recent');
    var viewToday    = localStorage.getItem('housekeeping-view-today');
    var viewUpcoming = localStorage.getItem('housekeeping-view-upcoming');
    var openedCategories = [];

    if (viewRecent == 'show') {
        $('.housekeeping-tasks-day.recent').slideDown();
        $('.tasks-view-toggles .btn[data-value="recent"]').toggleClass('active', true);
        openedCategories.push('recent');
    }

    //By default only today should be drawn
    if (viewToday != 'hide') {
        $('.housekeeping-tasks-day.today').slideDown();
        $('.tasks-view-toggles .btn[data-value="today"]').toggleClass('active', true);
        openedCategories.push('today');
    }

    if (viewUpcoming == 'show') {
        $('.housekeeping-tasks-day.upcoming').slideDown();
        $('.tasks-view-toggles .btn[data-value="upcoming"]').toggleClass('active', true);
        openedCategories.push('upcoming');
    }

    // Fetch and show data for opened categories
    showDataForCategories(openedCategories, false);

    // Enable the switch buttons only when the page is ready.
    // Otherwise it's first run will be quite slow and annoying
    $viewToggleButtons.removeClass('disabled');

    $viewToggleButtons.click(function(e) {
        e.preventDefault();
        var dayCategory = $(this).attr('data-value');
        var $container = $('.housekeeping-tasks-day.' + dayCategory);

        $(this).toggleClass('active');

        if ($(this).hasClass('active')) {
            $container.slideDown();
            scrollTo($container);

            localStorage.setItem('housekeeping-view-' + dayCategory, 'show');

            var sortId = $('.tasks-sort-toggles .btn.active').attr('data-value');
            if (!$container.html() || sortId != $('.housekeeping-tasks-day.' + dayCategory).attr('data-sort')) {
                showDataForCategories([dayCategory], true, sortId);
            }
        } else {
            localStorage.setItem('housekeeping-view-' + dayCategory, 'hide');
            $container.slideUp();
        }
    });

    $sortToggleButtons.removeClass('disabled');

    $sortToggleButtons.click(function(e) {
        e.preventDefault();
        var $categories = [];

        $sortToggleButtons.removeClass('active');
        $(this).toggleClass('active');

        $viewToggleButtons.each(function(index, obj) {
            if ($(obj).hasClass('active')) {
                $categories.push($(obj).attr('data-value'));
            }
        });

        e.preventDefault();
        $.ajax({
            type: "POST",
            url: GET_TASKS,
            data: {teamId: TEAM_ID, categories: $categories, sortId: $(this).attr('data-value')},
            dataType: "json",
            success: function (data) {
                if (data.status == 'success') {
                    $.each($categories, function(index, category) {
                        var $container = $('.housekeeping-tasks-day.' + category);
                        $container.html(data[category]);

                        (function drawSelectizes() {
                            var $field = $('.custom-selectize-' + category).first();
                            if ($field.length) {
                                $field.removeClass('custom-selectize-' + category);
                                drawSelectize.call(null, $field);
                                setTimeout(drawSelectizes, 0);
                            }
                        })();
                    });
                } else if (data.status) {
                    notification({
                        msg: data.msg,
                        status: data.status
                    });
                }
            }
        });
    });


});

function getHousekeepingTask(taskId) {
	if ($('#task-detail-' + taskId).find('div').length != 0) {
		return;
	}

	$.ajax({
		type: "POST",
		url: GET_THE_TASK,
		data: {task_id: taskId, team_id: TEAM_ID},
		dataType: "json",
		success: function (data) {
			if (data.status == 'success') {
				$('#task-detail-' + taskId).html('<div class="col-sm-12">' + data.html + '</div>')
			} else if (data.status) {
				notification(data);
			}
		}
	});
}

function saveStaff(userId, taskId, staffType) {
    var data = {
        edit_id: taskId
    };

    if (parseInt(staffType) == parseInt(STAFF_RESPONSIBLE)) {
        data['responsible_id'] = userId;
    } else {
        data['verifier_id'] = userId;
    }
    data['no_flush_message'] = 1;

    $.ajax({
        url: SAVE_STAFF,
        data: data,
        dataType: "json",
        type: "POST",
        success: function( data ) {
            if (data.status) {
                notification(data);
            }
        },
        error: function( data ) {
            notification({
                status: 'error',
                msg: 'Save failed'
            })
        }
    });
}

function reportIncident(btn) {
    var parent = btn.closest('.btn-group'),
        taskId = btn.closest('.panel').attr('data-task-id'),
        incidentType = btn.attr('data-type'),
        description = $("#other-incident-description").val();

    if (taskId > 0) {
        parent.find('.state').button('loading...');
        $.ajax({
            type: "POST",
            url: REPORT_INCIDENT,
            data: {
                incident_type: incidentType,
                task_id: taskId,
                description: description
            },
            dataType: "json",
            success: function(data) {
                if (data.status != 'success') {
                    btn.button('reset');
                } else {
                    var incidentIcon = '<span class="material-icons text-danger icon-incident">warning</span>';
                    btn.button('reset');
                    btn.closest('.task-panel').find('.icon-incident').toggleClass('hide', false);
                    var incidentReport = '<hr><h4> Incident Reports</h4>' +
                    '<div class="incident-reports list-group">' +
                    '<a href="/task/edit/'+ data.taskId +'" target="_blank" class="list-group-item">' + incidentIcon + data.title + '</a></div>';

                    if (btn.closest('.task-panel').find('div.incident-reports').length) {
                        incidentReport = '<a href="/task/edit/'+ data.taskId +'" target="_blank" class="list-group-item">' + incidentIcon + ' ' + data.title + '</a>';
                        btn.closest('.task-panel').find('div.incident-reports').append(incidentReport);
                    } else {
                        btn.closest('.task-panel').find('ul.subtasks').after(incidentReport);
                    }

                }
                notification(data);
            }
        });
    }
}

function saveComment($panel) {
    var $commentField = $panel.find('.comment-input-field');
    var msg = $commentField.val();
    var taskId = $panel.attr('data-task-id');

    $commentField.val('');

    $.ajax({
        type: "POST",
        url: SAVE_COMMENT,
        data: {
            message: msg,
            task_id: taskId
        },
        dataType: "json",
        success: function(data) {
            notification(data);

            if (data.status == 'success') {
                $panel.find('.task-comments').html(data.comments);
            }
        }
    });
}

function drawSelectize($select) {
    var $panel = $select.closest('.panel');
    var selectedOption = $select.attr('data-value');
    var $selectize;

    if ($select.hasClass('task-verifier')) {
        // Verifier Fields
        verifierSelectizeSettings.onInitialize = function () {
            if (allUsers[selectedOption]) {
                $select[0].selectize.addOption(allUsers[selectedOption]);
                $select[0].selectize.setValue(selectedOption);
            }
        };
        $selectize = $select.selectize(verifierSelectizeSettings);

        $selectize.on('change', function () {
            saveStaff(
                $select[0].selectize.getValue(),
                $panel.attr('data-task-id'),
                STAFF_VERIFIER
            )
        });
    } else {
        // Responsible Fields
        var taskType = $panel.data('task-type');
        var optionList = membersList;
        if (taskType == TASK_TYPE_APT_SERVICE) {
            optionList = officersWithoutAutoVerify;
        }

        responsibleSelectizeSettings.options = optionList;
        responsibleSelectizeSettings.onInitialize = function() {
            if (allUsers[selectedOption]) {
                $select[0].selectize.addOption(allUsers[selectedOption]);
                $select[0].selectize.setValue(selectedOption);
            }
        };

        $selectize = $select.selectize(responsibleSelectizeSettings);

        $selectize.on('change', function() {
            saveStaff(
                $select[0].selectize.getValue(),
                $panel.attr('data-task-id'),
                STAFF_RESPONSIBLE
            )
        });
    }
}

function showDataForCategories(categories, scroll, sortId)
{
    $.ajax({
        type: "POST",
        url: GET_TASKS,
        data: {teamId: TEAM_ID, categories: categories, sortId: sortId},
        dataType: "json",
        success: function (data) {
            if (data.status == 'success') {
                $.each(categories, function(index, category) {
                    var $container = $('.housekeeping-tasks-day.' + category);

                    $container.attr('data-sort', sortId);

                    if (scroll) {
                        scrollTo($container);
                    }
                    $container.html(data[category]);

                    (function drawSelectizes() {
                        var $field = $('.custom-selectize-' + category).first();
                        if ($field.length) {
                            $field.removeClass('custom-selectize-' + category);
                            drawSelectize.call(null, $field);
                            setTimeout(drawSelectizes, 0);
                        }
                    })();
                });
            } else if (data.status) {
                notification({
                    msg: data.msg,
                    status: data.status
                });
            }
        }
    });
}

function scrollTo($element) {
    $('html, body').animate({
        scrollTop: ($element.offset().top - 50)
    }, 1000);
}
