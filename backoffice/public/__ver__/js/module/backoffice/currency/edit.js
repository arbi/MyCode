$(function() {
    var hash = window.location.hash;
    hash && $('ul.nav a[href="' + hash + '"]').tab('show');

    $('.nav-tabs a').click(function (e) {
        window.location.hash = this.hash;
    });

    $('#date-range').daterangepicker({
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
        format: 'MMMM D, YYYY'
    });

    /** Datatable configuration */
    if (jQuery().dataTable) {
        gTable = $('#currency-values-table').dataTable({
            bAutoWidth: false,
            bFilter: false,
            bInfo: true,
            bPaginate: true,
            bProcessing: true,
            bServerSide: true,
            bStateSave: false,
            iDisplayLength: 25,
            sPaginationType: "bootstrap",
            ajax: {
                url: dataJsonUrl,
                data: function (d) {
                    d.range = $("#date-range").val();
                }
            }
        });
    }

    $('#get-currency-values').click(function() {
        gTable.fnDraw();
        $('.currecy-values-table-container').show();
    });
});