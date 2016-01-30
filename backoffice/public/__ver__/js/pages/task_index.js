$(function() {
    var $responsibleId = $("#responsible_id");
    var $creatorId     = $('#creator_id');
    var $followerId    = $('#follower_id');
    var $verifierId    = $('#verifier_id');
    var $helperId      = $('#helper_id');
    var $tags          = $('#tags');
    var $teamId        = $('#team_id');
    var $status        = $('#status');
    var $searchTask    = $('#search-task');

    $('#search-task').keydown(function(event){
        if (event.which == 13) {
            $('#btn_search_task').trigger('click');
        }
    });
    $tags.selectize({
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
        options: GLOBAL_ALL_TAGS,
        render: {
            item: function(option, escape) {
                return '<span class="label ' + option.style + '" style="margin-right: 2px"' +
                    '><span class="glyphicon glyphicon-tag"></span> ' + option.name + '</span>';
            }
        }
    });

    $('#creator_id, #responsible_id, #verifier_id, #helper_id, #follower_id').selectize({
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

    $responsibleId[0].selectize.clear();
    $responsibleId[0].selectize.addOption(anyTeamMember);
    $creatorId[0].selectize.clear();
    $followerId[0].selectize.clear();
    $helperId[0].selectize.clear();
    $tags[0].selectize.clear();


    $("#btn_search_task").click(function() {
        if (window.gTable) {
            gTable.fnDraw();
        } else {
            /** Datatable configuration */
            gTable = $('#datatable_task_info').dataTable({
                bAutoWidth: false,
                bFilter: false,
                bInfo: true,
                bPaginate: true,
                bProcessing: true,
                bServerSide: true,
                bStateSave: false,
                iDisplayLength: 10,
                sPaginationType: "bootstrap",
                sAjaxSource: DATATABLE_AJAX_SOURCE,
                aaSorting: [[3, "asc"]],
                aoColumns:[
                    {
                        name: "priority",
                        sortable: true,
                        width : "1",
                        class : "nowrap hidden-xs"
                    }, {
                        name: "status",
                        sortable: true,
                        width : "3%",
                        class : "hidden-xs"
                    }, {
                        name: "start_date",
                        sortable: true,
                        visible: false,
                        width : "100",
                        class : "nowrap hidden-xs"
                    }, {
                        name: "end_date",
                        sortable: true,
                        width : "80",
                        class : "nowrap hidden-xs"
                    }, {
                        name: "title",
                        sortable: true,
                        class: 'task-info'
                    }, {
                        name: "apartment",
                        sortable: true
                    }, {
                        name: "responsible",
                        sortable: true,
                        width : "185px",
                        class: "responsible"

                    },  {
                        name: "verifier",
                        sortable: true,
                        width : "185px",
                        class : "nowrap hidden-xs"
                    },  {
                        name: "type",
                        sortable: true,
                        width : "10%",
                        class : "hidden-xs"
                    }, {
                        name: "event",
                        sortable: false,
                        searchable: false,
                        width : "1"
                    }, {
                        name: "staffManager",
                        sortable: false,
                        searchable: false,
                        visible: false
                    }, {
                        name: "teamId",
                        sortable: false,
                        searchable: false,
                        visible: false
                    }
                ],
                aoColumnDefs:
                    [
                        {
                            aTargets: [6],
                            fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {
                                var canManageStaff = Number(oData[10]);
                                var $cell = $(nTd);
                                if (canManageStaff == 1) {
                                    var ginosikSelect = '<select name="responsible_id" data-task-id="' + oData[9] + '"' + (oData[iCol] ? ' data-selected="' + ALL_USERS[oData[iCol]].id + '"' : '') + (parseInt(oData[11]) > 0 ? ' data-any-team-assignable="1"' : '') + ' class="dt-ginosik-selectize">' +
                                        '</select>';
                                    $cell.html(ginosikSelect);
                                } else {
                                    if (oData[iCol]) {
                                        $cell.html(
                                            '<img src="' + ALL_USERS[oData[iCol]].avatar + '" title="' + ALL_USERS[oData[iCol]].name + '" class="ginosik-avatar-selectize"> ' +
                                            ALL_USERS[oData[iCol]].name
                                        );
                                    }
                                }
                            }
                        },
                        {
                            aTargets: [7],
                            fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {
                                var canManageStaff = Number(oData[10]);
                                var $cell = $(nTd);
                                if (canManageStaff == 1) {
                                    var ginosikSelect = '<select name="verifier_id" data-task-id="' + oData[9] + '" data-selected="' + (oData[iCol] ? ALL_USERS[oData[iCol]].id : '') + '" class="dt-ginosik-selectize">' +
                                        '</select>';
                                    $cell.html(ginosikSelect);
                                } else {
                                    $cell.html(
                                    '<img src="' + ALL_USERS[oData[iCol]].avatar + '" title="' + ALL_USERS[oData[iCol]].name + '" class="ginosik-avatar-selectize"> ' +
                                        ALL_USERS[oData[iCol]].name
                                    );
                                }
                            }
                        },
                        {
                            aTargets: [1],
                            fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {
                                var $cell = $(nTd);
                                var value = $cell.text();
                                var firstCharacter = value;
                                var labelClass = '';
                                switch (firstCharacter) {
                                    case 'New':
                                        labelClass = 'label-info';
                                        break;
                                    case 'Started':
                                        labelClass = 'label-primary';
                                        break;
                                    case 'Viewed':
                                        labelClass = 'label-warning';
                                        break;
                                    case 'Verified':
                                        labelClass = 'label-light-green';
                                        break;
                                    case 'Done':
                                        labelClass = 'label-success';
                                        break;
                                    case 'Canceled':
                                        labelClass = 'label-danger';
                                        break;
                                    case 'Blocked':
                                        labelClass = 'label-danger';
                                        break;
                                }
                                $cell.html('<label class="task-label label '+ labelClass +'" title="'+value+'">'+firstCharacter.charAt(0)+'</label>');
                            }
                        },
                        {
                            aTargets: [9],
                            fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {
                                var $cell = $(nTd);
                                var value = $cell.text();
                                $cell.html('<a href="/task/edit/' + value + '" target="_blank" class="btn btn-xs btn-primary"><span class="glyphicon glyphicon-chevron-right"></span></a>');
                            }
                        }
                    ],
                fnServerParams: function ( aoData ) {
                    additionalParams = $("#search-task").serializeObject();
                    jQuery.each(additionalParams, function(index, val) {
                        var myObject = {
                            name:  index,
                            value: val
                        };
                        aoData.push( myObject );
                    });
                },
                fnDrawCallback: function ( oSettings ) {
                    var $dtGinosikSelectize = $('.dt-ginosik-selectize');
                    if ($dtGinosikSelectize.length) {
                        $.each($dtGinosikSelectize, function(index, select) {
                            var selectedOption = $(select).attr('data-selected');
                            $(select).selectize({
                                create: false,
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
                                },
                                onInitialize: function() {
                                    if ($(select).attr('data-any-team-assignable') && $(select).attr('data-any-team-assignable') == '1') {
                                        $(select)[0].selectize.addOption(anyTeamMember);
                                    }

                                    if (ALL_USERS[selectedOption]) {
                                        $(select)[0].selectize.addOption(ALL_USERS[selectedOption]);
                                        $(select)[0].selectize.setValue(selectedOption);
                                    }
                                }
                            });
                        });

                        $dtGinosikSelectize.on('change', function() {
                            var data = {
                                no_flush_message: 1,
                                edit_id: $(this).attr('data-task-id')
                            };
                            data[$(this).attr('name')] = $(this).val();
                            $.ajax({
                                url: GLOBAL_SAVE,
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
                            })
                        });
                    }

                    $('[data-toggle="popover"]').popover({trigger: "hover", html: true});
                }
            });

            if ($('#datatable_task_container').hasClass('hidden')) {
                $('#datatable_task_container').removeClass('hidden');
            }
        }
    });

    if (GLOBAL_TAG_ID) {
        $status.val('0');
        $tags[0].selectize.addItem(GLOBAL_TAG_ID);
        $("#btn_search_task").trigger('click');
    }

//    $('#clearTaskFilters').click(function(event){
//        event.preventDefault();
//        $('input[name="title"]').val('');
//        $('#property').val('');
//        $('#property_id').val('');
//        $('#building').val('');
//        $('#building_id').val('');
//        $('#status').val(10);
//        $('select[name="priority"]').val(0);
//        $('select[name="type"]').val(0);
//        $('#team_id').val(0);
//        $('#creator_id')[0].selectize.clear();
//        $('#responsible_id')[0].selectize.clear();
//        $('#helper_id')[0].selectize.clear();
//        $('#follower_id')[0].selectize.clear();
//        $tags[0].selectize.clear();
//        $('#creation_date').val('');
//        $('#end_date').val('');
//        $('#done_date').val('');
//
//    });

    $('#clearTaskFilters').click(function(event){
        event.preventDefault();

        var form = $('#search-task');

        clearSearchForm(form);
    });

	$('#search-translation').keypress(function(e) {
		if (e.which == 13) {
			$("#btn_search_task").trigger('click');

            return false;
		}
	});

    if (jQuery().daterangepicker) {
		var $dateRangePickeroptions = {
			ranges: {
				'Today': [moment(), moment()],
				'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
				'Last 7 Days': [moment().subtract(6, 'days'), moment()],
				'Last 30 Days': [moment().subtract(29, 'days'), moment()],
				'This Month': [moment().startOf('month'), moment().endOf('month')],
				'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
			},
			startDate: moment().subtract(29, 'days'),
			endDate: moment(),
			format: 'YYYY-MM-DD'
	    };

        $reportRange = $('#reportrange span');

		$('#end_date').daterangepicker($dateRangePickeroptions, function(start, end) {
            $reportRange.html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
	    });

		$('#creation_date').daterangepicker($dateRangePickeroptions, function(start, end) {
            $reportRange.html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
	    });

		$('#done_date').daterangepicker($dateRangePickeroptions, function(start, end) {
            $reportRange.html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
	    });
	}

	$('.quick-tasks .qt-item').click(function(e) {
		e.preventDefault();

		var qt = $('.quick-tasks'),
			id = qt.attr('data-user-id'),
			department = qt.attr('data-user-department');

		$('#quick_task_id').val($(this).attr('data-value'));

		switch ($(this).attr('data-value')) {
            case '0':
                $searchTask.find('input').each(function(index, item) {
                    $(item).val('');
                });

                $searchTask.find('select').each(function(index, item) {
                    $(item).val(
                        $(item).find('option:first').val()
                    );
                });

                $followerId[0].selectize.clear();
                $helperId[0].selectize.clear();
                $responsibleId[0].selectize.clear();
                $creatorId[0].selectize.clear();
                $verifierId[0].selectize.clear();

                $status.val(10);
                break;
            case '-1':
                $responsibleId[0].selectize.setValue(-1);
                break;
			case '1':
                $responsibleId[0].selectize.setValue(id);
                $status.val('10');

                $creatorId[0].selectize.clear();
                $helperId[0].selectize.clear();
                $followerId[0].selectize.clear();
                $verifierId[0].selectize.clear();
                $teamId.val('0');
				break;
			case '2':
                $creatorId[0].selectize.setValue(id);
                $status.val('10');

                $responsibleId[0].selectize.clear();
                $helperId[0].selectize.clear();
                $followerId[0].selectize.clear();
                $verifierId[0].selectize.clear();
                $teamId.val('0');
				break;
			case '3':
                $followerId[0].selectize.setValue(id);
                $status.val('10');

                $responsibleId[0].selectize.clear();
                $creatorId[0].selectize.clear();
                $helperId[0].selectize.clear();
                $verifierId[0].selectize.clear();
                $teamId.val('0');
				break;
			case '4':
                $teamId.val(department);
                $status.val('10');

                $followerId[0].selectize.clear();
                $helperId[0].selectize.clear();
                $responsibleId[0].selectize.clear();
                $creatorId[0].selectize.clear();
                $verifierId[0].selectize.clear();
				break;
			case '5':
                $status.val('5');

                $followerId[0].selectize.clear();
                $helperId[0].selectize.clear();
                $responsibleId[0].selectize.clear();
                $creatorId[0].selectize.clear();
                $verifierId[0].selectize.setValue(id);
                $teamId.val('0');
				break
		}

		$('#btn_search_task').trigger('click');
	});

    $(".apt-filter-btn").click(function() {
        var prod = $(this).parent().prev();
        var prod_id = $(this).parent().parent().next();
        var prod_type = prod_id.next();
        prod_id.val(0);
        prod.val('');
        $(this).children("span").toggleClass("hide");
        var span = $(this).children("span.hide");
        if (span.hasClass("glyphicon-filter")) {
            prod_type.attr("value", 1);
        } else {

            prod_type.attr("value", 0);
        }
    });

    $("#print-btn").click(function(e) {
        e.preventDefault();
        window.print();
    });



});

$("#property").keyup(function(){
    if ($(this).val().length >= 3) {
        $("#property").autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: GLOBAL_PROPERTY,
                    data: {txt: $("#property").val(), mode : $("#property_type").val()},
                    dataType: "json",
                    type: "POST",
                    success: function( data ) {
                        var obj = [];
                        if(data && data.rc == '00'){
                            for(var row in data.result){
                                var item = data.result[row];
                                var new_obj = {};
                                new_obj.value = item.name;
                                new_obj.id    = item.id;
                                obj.push(new_obj);
                            }
                        }
                        response(obj);
                    }
                })
            },
            max:10,
            minLength: 1,
            autoFocus: true,
            select: function( event, ui ) {
                if(ui.item)
                    $('#property_id').val(ui.item.id);
            },
            search: function( event, ui ) {
                $('#property_id').val('');
            },

            focus: function(event, ui) {
                event.preventDefault();
            }
        });
    }
});

$('#datatable_task_info').on('mouseover', '.task-info', function (event) {
    var obj = $(event.target);
    var taskId = obj.closest('tr').find('select').attr('data-task-id');
    if (taskId) {
        var taskInfo = '<label><span data-content="The ones that can see this task. <br>Have permissions to <ul> <li>View</li><li>Add comment</li></ul>" data-container="body" data-toggle="popover" data-placement="top" class="commented-text" ,="" data-animation="true" data-original-title="" title="">Followers</span></label>';
        // obj.append(taskInfo);
    }
});