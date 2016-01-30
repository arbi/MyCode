/** Datatable configuration */
if (jQuery().dataTable) {
    gTable = $('.datatables').dataTable({
	    oLanguage: {
		    "sSearch": "Filter: "
	    },
        bAutoWidth: false,
        bFilter: true,
        bInfo: false,
        bPaginate: true,
        bProcessing: true,
        bServerSide: true,
        bStateSave: true,
	    iDisplayLength: 20,
	    sPaginationType: "bootstrap",
        sAjaxSource: DATATABLE_AJAX_SOURCE,
        sDom: 'l<"enabled">frti<"bottom"p><"clear">',
	    "aoColumns":[{
            'sWidth' : '1'
        }, {
		    'sWidth' : '55'
	    }, {
	    }, {
            'sClass': 'center',
            'sWidth' : '40'
	    }, {
            'sClass': 'center',
            'sWidth' : '40'
	    }, {
            'sClass': 'center',
            'sWidth' : '45'
	    }, {
            'sClass': 'center',
            'sWidth' : '50'
	    }, {
            'sClass': 'center',
            'sWidth' : '40'
	    }, {
            'sClass': 'center',
            'sWidth' : '45'
        }, {
            'sClass': 'center',
            'sWidth' : '45'
        }, {
            'sClass': 'center',
            'sWidth' : '55'
        }, {
            'sClass': 'center',
            'sWidth' : '62'
        }, {
		    "bSortable": false,
		    'sWidth' : '1'
	    }],
        aoColumnDefs: [
            {
                aTargets: [4],
                fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {
                    var $cell = $(nTd),
                        value = $cell.text();
                    if (value == 1) {
                        $cell.html('<span class="glyphicon glyphicon-ok"></span>');
                    } else {
                        $cell.html('');
                    }
                }
            }, {
                "aTargets": [5],
                "fnCreatedCell": function (nTd, sData, oData, iRow, iCol) {
                    var $cell = $(nTd),
                        value = $cell.text();
                    if (value == 1) {
                        $cell.html('<span class="glyphicon glyphicon-ok"></span>');
                    } else {
                        $cell.html('');
                    }
                }
            }, {
                "aTargets": [6],
                "fnCreatedCell": function (nTd, sData, oData, iRow, iCol) {
                    var $cell = $(nTd),
                        value = $cell.text();
                    if (value == 1) {
                        $cell.html('<span class="glyphicon glyphicon-ok"></span>');
                    } else {
                        $cell.html('');
                    }
                }
            }, {
                "aTargets": [7],
                "fnCreatedCell": function (nTd, sData, oData, iRow, iCol) {
                    var $cell = $(nTd),
                        value = $cell.text();
                    if (value == 1) {
                        $cell.html('<span class="glyphicon glyphicon-ok"></span>');
                    } else {
                        $cell.html('');
                    }
                }
            }, {
                "aTargets": [8],
                "fnCreatedCell": function (nTd, sData, oData, iRow, iCol) {
                    var $cell = $(nTd),
                        value = $cell.text();
                    if (value == 1) {
                        $cell.html('<span class="glyphicon glyphicon-ok"></span>');
                    } else {
                        $cell.html('');
                    }
                }
            }, {
                "aTargets": [9],
                "fnCreatedCell": function (nTd, sData, oData, iRow, iCol) {
                    var $cell = $(nTd),
                        value = $cell.text();
                    if (value == 1) {
                        $cell.html('<span class="glyphicon glyphicon-ok"></span>');
                    } else {
                        $cell.html('');
                    }
                }
            }, {
                "aTargets": [10],
                "fnCreatedCell": function (nTd, sData, oData, iRow, iCol) {
                    var $cell = $(nTd),
                        value = $cell.text();
                    if (value == 1) {
                        $cell.html('<span class="glyphicon glyphicon-ok"></span>');
                    } else {
                        $cell.html('');
                    }
                }
            }, {
                "aTargets": [11],
                "fnCreatedCell": function (nTd, sData, oData, iRow, iCol) {
                    var $cell = $(nTd),
                        value = $cell.text();
                    if (value == 1) {
                        $cell.html('<span class="glyphicon glyphicon-ok"></span>');
                    } else {
                        $cell.html('');
                    }
                }
            }
        ]
    });

    $('#team_manage_table').dataTable({
        bAutoWidth: false,
	    bFilter: true,
	    "oLanguage": {
		    "sSearch": "Filter: "
	    },
        bInfo:false,
        bLengthChange: false,
        bPaginate: false,
        bProcessing: false,
        bServerSide: false,
        bStateSave: true,
        iDisplayLength: 20,
	    aoColumns:[{
		    bSortable: true
	    }, {
		    bSortable: false
	    }]
    });

    $("div.enabled").html($('#status-switch').html());
    $('#status-switch').remove();

    $('.fn-buttons a').on('click', function(e) {
        e.preventDefault();

        $('.fn-buttons a').removeClass('active');
        $(this).addClass('active');

        switch ($(this).attr('data-status')) {
            case 'all':
                $("#show-status").attr('value', 0); break;
            case 'active':
                $("#show-status").attr('value', 1); break;
            case 'inactive':
                $("#show-status").attr('value', 2); break;
        }

        gTable.fnSettings().aoServerParams.push({
            "fn": function (aoData) {
                aoData.push({
                    "name": "all",
                    "value":  $("#show-status").attr('value')
                });
            }
        });

        gTable.fnGetData().length;
        gTable.fnDraw();
    });
}
