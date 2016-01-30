$(function() {
    var $switchStatus = $('#status-switch');
    var $showStatus = $('#show-status');
    /** Datatable configuration */
    if (jQuery().dataTable) {
        parkingLotsTable = $('#datatable-parking-lots').dataTable({
            bAutoWidth: false,
            bFilter: true,
            bInfo: true,
            bPaginate: true,
            bProcessing: true,
            bServerSide: true,
            bStateSave: true,
            iDisplayLength: 25,
            sPaginationType: "bootstrap",
            sDom: 'l<"enabled">frti<"bottom"p><"clear">',
            aaSorting: [[2, "asc"]],
            aoColumns:[
                {
                    name: "status",
                    width: "55"
                }, {
                    name: "name"
                }, {
                    name: "city"
                }, {
                    name: "address"
                }, {
                    name: "virtual",
                    width: "75",
                    class: "text-center"
                }, {
                    name: "edit",
                    sortable: false,
                    searchable: false,
                    width: "1"
                }
            ],
            ajax: {
                url: DATATABLE_AJAX_SOURCE,
                data: function (data) {
                    data.all = $("#show-status").attr('value');
                }
            }
        });

        $("div.enabled").html($switchStatus.html());
        $switchStatus.remove();

        $('.fn-buttons a').on('click', function(e) {
            e.preventDefault();
            $('.fn-buttons a').removeClass('active');
            $(this).addClass('active');

            $("#show-status").val($(this).attr('data-status'));

            parkingLotsTable.fnDraw();
        });
    }
});

