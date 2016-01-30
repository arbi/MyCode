$('#btn_remove_apartment_review_category').on('click', function() {
    var id = $('#review_category_id').val();
    if (parseInt(id) > 0) {
        $.ajax({
            type: "POST",
            url: URL_DELETE_REVIEW_CATEGORY,
            data: {id:id},
            dataType: "json",
            success: function(data) {
                if (data.status == 'success') {
                    window.location.href = GLOBAL_BASE_PATH + 'apartment-review-category';
                } else {
                    notification(data);
                }
            }
        });
    }
});