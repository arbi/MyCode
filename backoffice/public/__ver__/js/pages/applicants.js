$(function() {
    // Filter by specific statuses at start
    if(!$(".fn-buttons a.active").length) {
        $( ".fn-buttons a" ).each(function( index ) {
            if (index > 0 && index < 7) {
                $(this).addClass("active");
            }
        });
    }

    /** Datatable configuration */
    if (jQuery().dataTable) {
        gTable = $('#datatable_applicants').dataTable({
            bAutoWidth: false,
            bFilter: true,
            bInfo: false,
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
                    class : "hidden-xs",
                    width : '60'
                }, {
                    name: "name"
                }, {
                    name: "position"
                }, {
                    name: "city"
                }, {
                    name: "applied-date",
                    class : "hidden-xs",
                    width : '100'
                }, {
                    name: "phone",
                    class : "hidden-xs hidden-sm",
                    sortable: false
                }, {
                    name: "email",
                    class : "hidden-xs hidden-sm",
                    sortable: false
                }, {
                    name: "edit",
                    sortable: false,
                    searchable: false,
                    width: '1'
                }
            ],
            aaSorting : [[4, 'desc']]
        });

        $("div.enabled").html($('#status-switch').html());
        $('#status-switch').remove();

        $('.fn-buttons a').on('click', function(e) {
            e.preventDefault();
            if(($(this).text().toLowerCase()) == "all") {
                $('.fn-buttons a').removeClass("active");
            } else {
                $('.fn-buttons a:first-child').removeClass("active");
            }
            $(this).toggleClass('active');

            var actives = [];
            $.each($('.fn-buttons a'), function() {
                if ($(this).hasClass('active')) {
                    actives.push($(this).attr('data-status'));
                }
            });

            $("#show-status").attr('value', actives);
            gTable.fnSettings().aoServerParams.push({
                "fn": function (aoData) {
                    aoData.push({
                        "name": "status",
                        "value":  $("#show-status").attr('value')
                    });
                }
            });
            gTable.fnGetData().length;
            gTable.fnDraw();
        });
    }


});

