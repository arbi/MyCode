var gTable;
$(function() {
    if (jQuery().dataTable) {
        var isManager = parseInt($('#table_apartment_groups').attr('data-is-manager'));
        var emptyMsg = 'No matching records found';
	    var tableConfig = [{
		    "bSortable": false,
		    "sWidth" : "54"
	    }, {
		    "bSortable": true
	    }, {
		    "bSortable": true,
		    "sWidth" : "54"
	    }, {
		    "bSortable": true
	    },{
		    "bSortable": true,
		    "sWidth" : "54",
		    "sClass" : "hidden-xs text-center"
	    }, {
		    "bSortable": true,
		    "sWidth" : "90",
		    "sClass" : "hidden-xs text-center"
	    }, {
		    "bSortable": true,
		    "sWidth" : '78',
		    "sClass" : "hidden-xs text-center"
	    }, {
		    "bSortable": true,
		    "sWidth" : "105",
		    "sClass" : "hidden-xs text-center"
	    },{
		    "bSortable": true,
		    "sWidth" : "70",
		    "sClass" : "hidden-xs text-center"
	    }];

        if (!isManager) {
            emptyMsg = 'You are not managing any apartment group or aren\'t global apartment group manager.';
        } else {
	        tableConfig.push({
		        "bSortable": false,
		        "sWidth" : "1"
	        });
        }

        gTable = $('#table_apartment_groups').dataTable({
            "bAutoWidth": false,
            "bFilter": true,
            "language": {
                "search": "Filter: ",
                "zeroRecords": emptyMsg
            },
            "bInfo": false,
            "bLengthChange": false,
            "bPaginate": false,
            "bProcessing": true,
            "bServerSide": true,
            "bStateSave": true,
            "iDisplayLength": 20,
            "sPaginationType": "bootstrap",
            "sAjaxSource": DATATABLE_AJAX_SOURCE,
            "sDom": 'l<"enabled">frti<"bottom"p><"clear">',
            "aaSorting": [[1, "desc"]],
            "aoColumns": tableConfig
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
});
