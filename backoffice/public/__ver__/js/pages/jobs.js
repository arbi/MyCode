$(function() {
    /** Datatable configuration */
    if (jQuery().dataTable) {
        gTable = $('#datatable_jobs').dataTable({
            "bAutoWidth": false,
            "bFilter": true,
            "bInfo": false,
            "bPaginate": true,
            "bProcessing": true,
            "bServerSide": true,
            "bStateSave": true,
            "iDisplayLength": 25,
            "sPaginationType": "bootstrap",
            "sAjaxSource": DATATABLE_AJAX_SOURCE,
            "sDom": 'l<"enabled">frti<"bottom"p><"clear">',
            "aoColumns":[
                {
                    "name": "status",
                    "bSortable": true,
                    "width" : '6%'
                }, {
                    "name": "title",
                    "bSortable": true,
                    "width" : '15%'
                }, {
                    "name": "department",
                    "sortable": true,
                    "width" : '12%'
                }, {
                    "name": "city",
                    "sortable": true,
                    "class" : "hidden-xs",
                    "width" : '18%'
                }, {
                    "name": "start_date",
                    "sortable": true,
                    "class": "hidden-xs",
                    "type": "date", 
                    "width" : '90px'
                }, {
                    "name": "description",
                    "sortable": true
                }, {
                    "name": "edit",
                    "sortable": false,
                    "searchable": false,
                    "width" : '1%'
                }
            ]
        });

        $("div.enabled").html($('#status-switch').html());
        $('#status-switch').remove();

        $('.fn-buttons a').on('click', function(e) {
            e.preventDefault();
            $('.fn-buttons a').removeClass('active');
            $(this).addClass('active');

            switch ($(this).attr('data-status')) {
                case 'all':
                    $("#show-status").attr('value', 4); break;
                case 'draft':
                    $("#show-status").attr('value', 1); break;
                case 'live':
                    $("#show-status").attr('value', 2); break;
                case 'inactive':
                    $("#show-status").attr('value', 3); break;
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

