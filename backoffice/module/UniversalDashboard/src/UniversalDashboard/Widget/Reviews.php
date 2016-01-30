<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

final class Reviews extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'product', 'title' => 'Apartment', 'sortable' => true, 'class' => 'reviewWidgetProductColumn', 'width' => '14%'],
            ['name' => 'score', 'title' => '<span class="glyphicon glyphicon-star-empty"></span>', 'sortable' => true, 'width' => '3%','class'=>'hidden-xs'],
            ['name' => 'total_score', 'title' => 'Total', 'sortable' => true, 'width' => '5%'],
            ['name' => 'review', 'title' => 'Review', 'sortable' => false, 'width' => '18%'],
            ['name' => 'comment', 'title' => 'Comment', 'sortable' => false, 'width' => '18%', 'class'=>'hidden-xs'],
            ['name' => 'category', 'title' => 'Codification', 'sortable' => false, 'width' => '19%','class'=>'hidden-xs'],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'width' => '12%']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-reviews';

        $this->afterDrawCallbackJsFunctionAddition = '
            $("select.review-category-list").each(function(index) {
                if(!$(this).hasClass("selectized")) {
                    $(this).selectize({
                                        plugins: ["remove_button"],
                                        selectOnTab: true
                                     });
                }

            });


            if(typeof window.bindReviewCategoriesChangeScriptUD == "undefined") {
                window.bindReviewCategoriesChangeScriptUD = true;
                $(document).on("change", ".review-category-list", function (e) {
                    var selectData = $(this).val();
                    var sendData = JSON.stringify(selectData);
                    var apartmentId = $(this).attr("data-apartment-id");
                    var reviewId = $(this).attr("data-review-id");
                    $.ajax({
                        type: "POST",
                        url: "/apartment/" + apartmentId + "/review/ajax-save-review-category",
                        data: {
                        reviewId: reviewId,
                        selectData: sendData
                        },
                        dataType: "json",
                        success: function (data) {
                            notification(data);
                        }
                    });
                });
            }
        ';

    }
}