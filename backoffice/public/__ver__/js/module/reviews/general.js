$(function() {

    if (jQuery().daterangepicker) {
        var $dateRangePickeroptions = {
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
            format: 'YYYY-MM-DD'
        };

        $reportRange = $('#reportrange span');

        $('#arrival_date_range').daterangepicker($dateRangePickeroptions, function(start, end) {
            $reportRange.html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
        });

        $('#departure_date_range').daterangepicker($dateRangePickeroptions, function(start, end) {
            $reportRange.html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
        });
    }

    $('#clear-reviews-filters').click(function(event){
        event.preventDefault();
        $('#apartment_groups')[0].selectize.clear();
        $('#tags')[0].selectize.clear();
        $('.review-score-chooser').removeClass('review-score-chooser-disabled');
        $('#arrival_date_range').val('');
        $('#departure_date_range').val('');
        $('#stay_length_from').val('');
        $('#stay_length_to').val('');
    });

    $('.review-score-chooser').click(function(event){
       event.preventDefault();
       $(this).toggleClass('review-score-chooser-disabled');
    });

    $("#btn_search_review").click(function () {
        showChartAndCategoriesCount();
        if (typeof gTable != 'undefined') {
            gTable.fnDraw();
        } else {
            /** Datatable configuration */
            gTable = $('#datatable-reviews').dataTable({
                bAutoWidth: false,
                bFilter: false,
                bInfo: true,
                bPaginate: true,
                bProcessing: true,
                bServerSide: true,
                bStateSave: true,
                iDisplayLength: 25,
                sPaginationType: "bootstrap",
                sAjaxSource: DATATABLE_AJAX_SOURCE,
                aaSorting: [[2, "desc"]],
                aoColumns: [
                    {
                        name: "res_number",
                        sortable: false,
                        width: "6%"
                    }, {
                        name: "score",
                        width: "26",
                        class: "text-center"
                    }, {
                        name: "date",
                        width: "90"
                    }, {
                        name: "status",
                        class: "text-center status-td",
                    }, {
                        name: "review",
                        sortable: false,
                        class: "hidden-xs",
                        width: "25%"
                    }, {
                        name: "comment",
                        sortable: false,
                        class: "hidden-xs"
                    }, {
                        name: "category",
                        class: "hidden-xs",
                        sortable: false,
                        width: "20%"
                    }, {
                        name: "actions",
                        sortable: false,
                        width: "90"
                    }
                ],
                "fnServerParams": function (aoData) {
                    additionalParams = $("#search-form").serializeObject();
                    jQuery.each(additionalParams, function (index, val) {
                        var myObject = {
                            name: index,
                            value: val
                        };
                        aoData.push(myObject);
                    });
                    var scoreFilter = {
                        name: 'score_filter',
                        value: []
                    };
                    $('.review-score-chooser:not(.review-score-chooser-disabled)').each(function(){
                        var score = $(this).text();
                        scoreFilter.value.push(score);
                    });
                    aoData.push(scoreFilter);
                },
                fnDrawCallback: function () {
                    $('select.review-category-list').each(function (index) {
                        if (!$(this).hasClass('selectized')) {
                            $(this).selectize({
                                plugins: ['remove_button'],
                                selectOnTab: true
                            });
                        }
                    });
                    var $modal = $('#deleteModal');
                    $modal.modal('hide');
                    paintTheSelectedTags();
                }
            });
            if ($('#datatable-reviews').hasClass('hidden')) {
                $('#datatable-reviews').removeClass('hidden');
            }
            gTable.fnDraw();
        }
    });

    $('#delete_review_submit').click(function(event){
        event.preventDefault();
        var $self = $(this);
        var reviewId = $self.attr('data-id');
        $.ajax({
            url: DELETE_REVIEW,
            data: {review_id: reviewId},
            dataType: "json",
            type: "POST",
            success: function( data ) {
                notification(data);
                if (data.status == 'success') {
                    $("#btn_search_review").trigger('click');
                }
            }
        });
    });

    $('#stay_length_from, #stay_length_to').bind("keyup change", function(){
        validateAndShowOrDoNotShowSearchButton();
    });

    $('#tags').change(function(){
        paintTheSelectedTags();
    })

});

$(document).on('change','select.review-category-list',function(){
    paintTheSelectedTags()
});

function paintTheSelectedTags()
{

    $('.selectize-control.review-category-list .item').removeClass('label-success-important');
    var selectedTags = $('#tags').val();
    if (selectedTags != null) {
        $.each(selectedTags, function(index, value){
            $('.selectize-control.review-category-list .item[data-value="' + value + '"]').addClass('label-success-important');
        });
    }


}

function validateAndShowOrDoNotShowSearchButton()
{
    var fromValue = $('#stay_length_from').val();
    var toValue   = $('#stay_length_to').val();
    var $btnSearchReview = $('#btn_search_review');

    if (fromValue == '-' ||  toValue == '-') {
        $btnSearchReview.addClass('disabled');
        return false;
    }

    if (fromValue != '' && fromValue < 1 ) {
        $btnSearchReview.addClass('disabled');
        return false;
    }

    if (toValue != '' && toValue < 1 ) {
        $btnSearchReview.addClass('disabled');
        return false;
    }

    if (fromValue != '' && toValue != '') {
        if (fromValue > toValue) {
            $btnSearchReview.addClass('disabled');
            return false;
        }
    }
    $btnSearchReview.removeClass('disabled');
    return true;
}

$(document).on('change', '.review-category-list', function(){
    var reviewId = $(this).attr('data-review-id');
    var categories = $(this).val();
    $.ajax({
        url: CHANGE_REVIEW_CATEGORIES,
        data: {review_id: reviewId, categories: categories},
        dataType: "json",
        type: "POST",
        success: function( data ) {
            notification(data);
            if (data.status == 'success') {
                showChartAndCategoriesCount();
            }
        }
    });
});

$(document).on('click', 'ul.change-review-status li a:not(.delete-review)', function(event){
    event.preventDefault();
    var $self = $(this);
    var $tr = $self.closest('tr');
    var $statusTd = $tr.find('td.status-td');
    var $select = $tr.find('select.review-category-list');
    var reviewId = $select.attr('data-review-id');
    var status = $self.attr('data-status');
    $.ajax({
        url: CHANGE_STATUS,
        data: {review_id: reviewId, status: status},
        dataType: "json",
        type: "POST",
        success: function( data ) {
            notification(data);
            if (data.status == 'success') {
                $statusTd.html(data.glyphicon);
            }
        }
    });
});

$(document).on('click', '.delete-review', function(event){
    event.preventDefault();
    var $self = $(this);
    var $tr = $self.closest('tr');
    var $select = $tr.find('select.review-category-list');
    var reviewId = $select.attr('data-review-id');
    var $modal = $('#deleteModal');
    var $submitDelete = $('#delete_review_submit');
    $submitDelete.attr('data-id', reviewId);
    $modal.modal('show');
});


function showChartAndCategoriesCount()
{
    var formParams = $("#search-form").serializeObject();
    formParams.score_filter=[];
    $('.review-score-chooser:not(.review-score-chooser-disabled)').each(function(){
        var score = $(this).text();
        formParams.score_filter.push(score);
    });
    $.ajax({
        url: CATEGORIES_INFO_URL,
        data: formParams,
        dataType: "json",
        type: "POST",
        async: false,
        success: function( data ) {
            if (data.status == 'error') {
                notification(data);
            } else {
                var $reviewCategoriesCounterPartTable = $('.review-categories-counter-part table');
                $reviewCategoriesCounterPartTable.html('');
                $.each(data.data, function(index, value) {
                    var html =
                        '<tr>' +
                            '<td class="text-right"><span class="review-category-count" >' + value.num + '</span>' +
                            '<td><span class="review-category-description">' + value.category_name + '</span></td>'  +
                        '</tr>';
                    $reviewCategoriesCounterPartTable.append(html)
                });
                $('.hide-on-success').addClass('hidden');
                $('.chart-and-review-categories-counter-part').removeClass('hidden');
            }
        }
    });

    $.ajax({
        url: CHART_INFO_URL,
        data: formParams,
        dataType: "json",
        type: "POST",
        async: false,
        success: function( data ) {
            if (data.status == 'error') {
                notification(data);
            } else {
                if (data.data.categories.length){
                    $('.chart-and-review-categories-counter-part').removeClass('hidden');
                    drawChart(data.data);
                } else {
                    $('.chart-and-review-categories-counter-part').addClass('hidden');
                }
            }
        }
    });
}

function drawChart(data)
{
    $('#chart-container').highcharts({
        alignTicks: true,
        title: {
            text: 'Review Statistics',
            align: 'left',
            x: 20
        },
        subtitle: {
            useHTML: true,
            text: '<span class="review-category-count in-highcharts">' + data.totalAverageRanking + '</span> AVERAGE RANKING    ' + '<span class="review-category-count in-highcharts">' + data.totalRanksCount + '</span>Reviews',
            align: 'left',
            x: 10
        },
        xAxis: {
            categories:data.categories
        },
        yAxis: [
            {
                title: {
                    text: 'Review Score',
                    style: {
                        color: Highcharts.getOptions().colors[0]
                    }
                },
                labels: {
                    style: {
                        color: Highcharts.getOptions().colors[0]
                    },
                    format: '{value} stars'
                },
                tickPositions: [0, 1, 2, 3, 4, 5]
            },
            {
                title: {
                    text: null,
                    style: {
                        color: Highcharts.getOptions().colors[5]
                    }
                },
                labels: {
                    format: '{value} reviews',
                    style: {
                        color: Highcharts.getOptions().colors[5]
                    }
                },
                gridLineWidth: 0,
                opposite: true
            }
        ],
        tooltip: {
            backgroundColor: "rgba(255, 255, 255, 1)",
            useHTML: true,
            formatter: function() {return ' ' +
                '<h5 class="text-center">' + this.point.explanation + '</h5>' +
                '<table class="table table-no-border table-tooltip">' +
                '<tr><td class="mw-100"><i class="star_good"></i><i class="star_good"></i><i class="star_good"></i><i class="star_good"></i><i class="star_good"></i></td><td>' + this.point.score5stars + '</td><td><span class="label-success" style="width:' + this.point.score5stars/this.point.reviewCount*100 + 'px; height:5px; display:inline-block;"></span></td></tr>' +
                '<tr><td class="mw-100"><i class="star_good"></i><i class="star_good"></i><i class="star_good"></i><i class="star_good"></i></td><td>' + this.point.score4stars + '</td><td><span class="label-success" style="width:' + this.point.score4stars/this.point.reviewCount*100 + 'px; height:5px; display:inline-block;"></span></td></tr>' +
                '<tr><td class="mw-100"><i class="star_good"></i><i class="star_good"></i><i class="star_good"></i></td><td>' + this.point.score3stars + '</td><td><span class="label-success" style="width:' + this.point.score3stars/this.point.reviewCount*100 + 'px; height:5px; display:inline-block;"></span></td></tr>' +
                '<tr><td class="mw-100"><i class="star_good"></i><i class="star_good"></i></td><td>' + this.point.score2stars + '</td><td><span class="label-success" style="width:' + this.point.score2stars/this.point.reviewCount*100 + 'px; height:5px; display:inline-block;"></span></td></tr>' +
                '<tr><td class="mw-100"><i class="star_good"></i></td><td>' + this.point.score1stars + '</td><td><span class="label-success" style="width:' + this.point.score1stars/this.point.reviewCount*100 + 'px; height:5px; display:inline-block;"></span></td></tr>' +
                '</table>' + '<hr>' +
                '<table class="table table-no-border table-tooltip">' +
                '<tr><td class="mw-100">Review Count: </td><td>' + this.point.reviewCount + '</td></tr>' +
                '<tr><td class="mw-100">Average Rank: </td><td>' + this.point.averageScore + '</td></tr>' +
                '</table>'
            }
        },
        legend: {
            layout: 'vertical',
            align: 'right',
            verticalAlign: 'middle',
            borderWidth: 0
        },
        series: [
            {
                type: 'column',
                name: 'Count',
                yAxis: 1,
                data: data.series.reviewCounts,
                color: Highcharts.getOptions().colors[5]
            },
            {
                name: 'Avg Scores',
                data: data.series.avgScores,
                color: Highcharts.getOptions().colors[0]
            }
        ]
    });
}
