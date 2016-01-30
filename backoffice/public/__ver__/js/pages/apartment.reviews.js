$(function() {
    /** Datatable configuration */
    if (jQuery().dataTable) {
        gTable = $('#review').dataTable({
            bFilter: false,
            bInfo: true,
            bServerSide: false,
            bProcessing: true,
            bPaginate: true,
            bAutoWidth: false,
            bStateSave: true,
            iDisplayLength: 25,
            sAjaxSource: null,
            sPaginationType: "bootstrap",
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
                    width: "6%"
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
            fnDrawCallback: function () {

                $('select.review-category-list').each(function (index) {
                    if (!$(this).hasClass('selectized')) {
                        $(this).selectize({
                            plugins: ['remove_button'],
                            selectOnTab: true
                        });
                    }

                });

            },
            aaSorting: [[2, "desc"]],
            aaData: aaData
        });
    }

    if (typeof window.bindReviewCategoriesChangeScriptRP == "undefined") {
        window.bindReviewCategoriesChangeScriptRP = true;
        $(document).on('change', '.review-category-list', function (e) {
            var selectData = $(this).val();
            var sendData = JSON.stringify(selectData);
            $.ajax({
                type: "POST",
                url: GLOBAL_SAVE_REVIEW_CATEGORY,
                data: {
                    reviewId: $(this).attr('data-review-id'),
                    selectData: sendData
                },
                dataType: "json",
                success: function (data) {
                    notification(data);
                }
            });

        });
   }

});



function deleteReview(id){
    $('#delete_review').attr("href", GLOBAL_DELETE+'/'+id);
}