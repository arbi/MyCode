$(function () {
    $('#btn_draw').click(function(){
        getChartInfo();
    });
    getChartInfo();
});

function getChartInfo()
{
    $.ajax({
        url: '/finance/budget/draw-chart',
        type: 'POST',
        data: {year: $('#year').val()},
        error: function () {
            notification({
                status: 'error',
                msg: 'ERROR! Something went wrong'
            });
        },
        success: function (data) {
            if (data.status == 'success') {
                var BUDGETS = data.budgets;
                if (BUDGETS.length > 0) {
                    $.each(BUDGETS, function(index, data) {
                        data.y = parseFloat(data.y);
                    });

                    $('#chart-x').highcharts({
                        title: {
                            text: ''
                        },
                        chart: {
                            plotBackgroundColor: null,
                            plotBorderWidth: null,
                            plotShadow: false,
                            type: 'pie'
                        },
                        tooltip: {
                            useHTML: true,
                            formatter: function() {
                                return '<h5>' + this.point.name + '</h5>'
                                    + 'Amount: <b>' + this.point.yWithComma + ' euro</b>';
                            }
                        },
                        plotOptions: {
                            pie: {
                                allowPointSelect: true,
                                cursor: 'pointer',
                                dataLabels: {
                                    enabled: true,
                                    format: '<b>{point.name}</b>: {point.percentage:.1f} %',
                                    style: {
                                        color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                                    }
                                }
                            }
                        },
                        series: [{
                            name: "Amount",
                            colorByPoint: true,
                            data: BUDGETS
                        }]
                    });
                } else {
                    $('#chart-x').html('<div class="alert alert-danger margin-top-10">No Budgets For This Year</div>');
                }

            } else {
                notification(data);
            }
        }
    });
}
