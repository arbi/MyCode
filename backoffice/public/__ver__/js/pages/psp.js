var gTable;
$(function(){
    /** Datatable configuration */
    if (jQuery().dataTable) {
        gTable = $('#bank-accounts-table').dataTable({
            bAutoWidth: false,
            bFilter: true,
            bInfo: true,
            bPaginate: true,
            bProcessing: true,
            bServerSide: true,
            bStateSave: true,
            iDisplayLength: 25,
            sPaginationType: "bootstrap",
            sAjaxSource: "/finance/psp/ajax-psp-list",
            sDom: 'l<"enabled">frti<"bottom"p><"clear">',
            aaSorting: [[1, "asc"]],
            aoColumns:[
                {
                    name: "status",
                    width: "6%",
                    class: "hidden-xs"
                }, {
                    name: "shortName"
                }, {
                    name: "name",
                    class: "hidden-xs"
                }, {
                    name: "country",
                    width: "30%",
                    class: "hidden-xs"
                }, {
                    name: "batch",
                    width: "70px",
                    class: "text-center hidden-xs"
                }, {
                    name: "actions",
                    sortable: false,
                    searchable: false,
                    width : "1"
                }
            ],
	        aoColumnDefs: [{
		        aTargets: [4],
		        fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {
			        var $cell = $(nTd),
				        value = $cell.text();
			        if (value == '1') {
				        $cell.html('<span class="glyphicon glyphicon-ok"></span>');
			        } else {
				        $cell.html('');
			        }
		        }
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

            gTable.fnDraw();
        });
    }
});
