$(function() {
    /** Datatable configuration */
    gTable = $('#datatable_users').dataTable({
        "bAutoWidth": false,
        "bFilter": true,
        "oLanguage": {
            "sSearch": "Filter: "
        },
        "bInfo": false,
        "bPaginate": true,
        "bProcessing": true,
        "bServerSide": true,
        "bStateSave": true,
        "iDisplayLength": 25,
        "sPaginationType": "bootstrap",
        "sAjaxSource": DATATABLE_AJAX_SOURCE,
        "aoColumns":[
            {
                "name": "status",
                "bSortable": true,
                "sWidth": "1%"
            },{
                "name": "firstname",
                "bSortable": true,
                "sWidth": "16%"
            }, {
                "name": "city",
                "bSortable": true,
                "sWidth": "12%"
            }, {
                "name": "position",
                "bSortable": true,
                "sWidth" : "16%",
                "sClass" : "hidden-xs"
            }, {
                "name": "department",
                "bSortable": true,
                "sWidth" : "10%",
                "sClass" : "hidden-xs"
            }, {
                "name": "evaluation_date",
                "bSortable": true,
                "sWidth" : "10%",
                "sClass" : "hidden-xs",
                "visible": 0
            }, {
                "name": "vacation_days_left",
                "bSortable": true,
                "sWidth" : "8%",
                "sClass" : "hidden-xs",
                "visible": 0
            }, {
                "name": "vacation_days_allotted",
                "bSortable": true,
                "sWidth" : "8%",
                "sClass" : "hidden-xs",
                "visible": 0
            }, {
                "name": "start_date",
                "bSortable": true,
                "sWidth" : "12%",
                "sClass" : "hidden-xs",
                "visible": 0
            }, {
                "name": "end_date",
                "bSortable": true,
                "sWidth" : "11%",
                "sClass" : "hidden-xs",
                "visible": 0
            }, {
                "name": "view",
                "bSortable": false,
                "bSearchable": false,
                "visible": HAS_PROFILE_MODULE,
                "sWidth" : "1%",
                "sClass" : "text-center"
            }, {
                "name": "edit",
                "bSortable": false,
                "bSearchable": false,
                "sWidth" : "1%",
                "sClass" : "text-center"
            }
        ],
        "aoColumnDefs":
        [
            {
                "aTargets": [10],
                "fnCreatedCell": function (nTd, sData, oData, iRow, iCol) {
                    var $cell = $(nTd);
                    var value = $cell.text();
                    $cell.html('<a href="/profile/' + value + '" class="btn btn-xs btn-primary" data-html-content="View"></a>');
                }
            },
            {
                "aTargets": [11],
                "fnCreatedCell": function (nTd, sData, oData, iRow, iCol) {
                    var $cell = $(nTd);
                    var value = $cell.text();
                    if (value !== "0")
                        $cell.html('<a href="/user/edit/' + value + '" class="btn btn-xs btn-primary">Manage</a>');
                    else
                        $cell.html('');
                }
            }
        ],
        "fnServerParams": function ( aoData ) {
            additionalParams = $("#search-ginosik").serializeObject();
            jQuery.each(additionalParams, function(index, val) {
                var myObject = {
                    name:  index,
                    value: val
                };

                aoData.push( myObject );
            });
        }
    });

    $("#btn_filter_ginosiks").click(function() {
        if (window.gTable) {
            gTable.fnDraw();
        }
    });

    $('.fn-buttons a').on('click', function(e) {
        e.preventDefault();

        $(this).closest('.user-switch').find('.fn-buttons a').removeClass('active');
        $(this).addClass('active');
        var userSwitchStatus = $(this).closest('.user-switch').find('.user-switch-status');
        switch ($(this).attr('data-status')) {
            case 'all':
                userSwitchStatus.val(0);
                break;
            case 'active':
                userSwitchStatus.val(1);
                break;
            case 'inactive':
                userSwitchStatus.val(2);
                break;
        }
        gTable.fnGetData().length;
        gTable.fnDraw();
    });

    if (HAS_PEOPLE_HR_ROLE) {
        var table = $('#datatable_users').DataTable();

        // show|hide column buttons
        var bntEvaluationDate = $('.btn-evaluation-date'),
            bntVacationDaysLeft = $('.btn-vacation-days-left'),
            bntVacationDaysAllotted = $('.btn-vacation-days-allotted'),
            bntStartDate = $('.btn-start-date'),
            bntEndDate = $('.btn-end-date');

        // get buttons current status
        if (table.column(bntEvaluationDate.attr('data-column-name')).visible()) {
            bntEvaluationDate.addClass('active');
        }
        if (table.column(bntVacationDaysLeft.attr('data-column-name')).visible()) {
            bntVacationDaysLeft.addClass('active');
        }
        if (table.column(bntVacationDaysAllotted.attr('data-column-name')).visible()) {
            bntVacationDaysAllotted.addClass('active');
        }
        if (table.column(bntStartDate.attr('data-column-name')).visible()) {
            bntStartDate.addClass('active');
        }
        if (table.column(bntEndDate.attr('data-column-name')).visible()) {
            bntEndDate.addClass('active');
        }

        $('.fn-columns a').on('click', function(e) {
            e.preventDefault();

            var newStatus = 1;

            if ($(this).hasClass('active')) {
                $(this).removeClass('active');
                newStatus = 0;
            } else {
                $(this).addClass('active');
            }

            var table = $('#datatable_users').DataTable();

            var column = table.column($(this).attr('data-column-name'));
            column.visible(newStatus);

            gTable.fnGetData().length;
            gTable.fnDraw();
        });
    }
});