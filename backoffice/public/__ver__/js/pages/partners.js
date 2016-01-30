var gTable;
$(function() {
    /** Datatable configuration */
    if (jQuery().dataTable) {
        gTable = $('#datatable_partners').dataTable({
            bAutoWidth: false,
            bFilter: true,
            bInfo: true,
            bPaginate: true,
            bProcessing: true,
            bServerSide: true,
            bStateSave: true,
            iDisplayLength: 25,
            sPaginationType: "bootstrap",
            sAjaxSource: DATATABLE_AJAX_SOURCE,
            sDom: 'l<"enabled">frti<"bottom"p><"clear">',
            aoColumns:[
                {
                    name: "status",
                    width: "1%"
                }, {
                    name: "gid",
                    width: "5%",
                    class: "hidden-xs"
                }, {
                    name: "partner_name"
                }, {
                    name: "contact_name",
                    class: "hidden-xs"
                }, {
                    name: "email",
                    width: "23%",
                    class: "hidden-xs"
                }, {
                    name: "mobile",
                    width: "120",
                    sortable: false,
                    class: "hidden-xs hidden-sm"
                }, {
                    name: "phone",
                    width: "120",
                    sortable: false,
                    class: "hidden-xs hidden-sm"
                }, {
                    name: "open",
                    searchable: false,
                    sortable: false,
                    width: '1'
                }, {
                    name: "edit",
                    sortable: false,
                    searchable: false,
                    width: '1'
                }
            ],
            aaSorting: [[1, 'asc']]
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

            gTable.fnDraw();
        });
    }

	$("div.enabled").html($('#status-switch').html());
    $('#status-switch').remove();

	$('.fn-buttons a').on('click', function(e) {
		e.preventDefault();

		var sentValue = null;

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
});
