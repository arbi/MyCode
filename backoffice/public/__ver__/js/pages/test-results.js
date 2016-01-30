$(function() {
    $('#search-test-results').submit(function(event) {
        event.preventDefault();

        var categories = [];
        var statuses = [];

        $('#category option').each(function() {
            var category = $(this).val();
            categories.push(category);
        });

        $('#status option').each(function() {
            var status = $(this).val();
            statuses.push(status);
        });

        $.ajax({
            url: '/test-results/ajax-get-test-results',
            type: "POST",
            data: {
                categories : categories,
                statuses : statuses,
                test_name : $('#test_name').val()
            },
            cache: false,
            success: function (data) {
                if (data.status == 'success') {
                    $('#total-total').text(data.total.totalCount).removeClass('hidden');
                    $('#total-pass').text(data.total.passCount);
                    if (data.total.passCount) {
                        $('#total-pass').parent().removeClass('hidden');
                    } else {
                        $('#total-pass').parent().addClass('hidden');
                    }
                    $('#total-fail').text(data.total.failCount);
                    if (data.total.failCount) {
                        $('#total-fail').parent().removeClass('hidden');
                    } else {
                        $('#total-fail').parent().addClass('hidden');
                    }
                    $('#total-error').text(data.total.errorCount);
                    if (data.total.errorCount) {
                        $('#total-error').parent().removeClass('hidden');
                    } else {
                        $('#total-error').parent().addClass('hidden');
                    }
                    $('#total-warning').text(data.total.warningCount);
                    if (data.total.warningCount) {
                        $('#total-warning').parent().removeClass('hidden');
                    } else {
                        $('#total-warning').parent().addClass('hidden');
                    }
                    $('#tests-part').html('');
                    $.each(data.partials, function(index, value) {
                        $('#tests-part').append(value);
                    });


                    var chartOptions = {
                        chart: {
                            plotBackgroundColor: null,
                            plotBorderWidth: null,
                            plotShadow: false,
                            type: 'pie'
                        },
                        tooltip: {
                            pointFormat: '{series.name}: <b>{point.y:.1f}</b>'
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
                            name: "Results",
                            colorByPoint: true
                        }],
                        title : 'Test Results'
                    };


                    chartOptions.series[0].data =
                        [
                            {name: 'Passed', y: data.total.passCount, color: '#5cb85c'},
                            {name: 'Failed', y: data.total.failCount, color: '#d9534f'},
                            {name: 'Error', y: data.total.errorCount, color: '#f0ad4e'},
                            {name: 'Warning', y: data.total.warningCount, color: '#f0ad4e'}
                        ];

                    $('#chart').highcharts(chartOptions);

                } else {
                    notification(data);
                }
            }
        });
    });

    $('#btn_search').trigger('click');

});

$(document).on( 'click', '.panel-title-container', function(event) {
    $(this).closest('.panel-heading').next().collapse('toggle');
});

$(document).on('click', '.link-status', function(event) {
    event.preventDefault();
    var $content = $(this).parent().find('.results-description-container');
    $content.slideToggle();
});


