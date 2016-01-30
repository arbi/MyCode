$(function () {
    $('.exact-remove-evaluation-button').click(function (e) {
        e.preventDefault();

        var elem = $(this),
            url = $('#evaluationRemoveModal').attr('data-src');

        elem.button('loading');

        $.ajax({
            url: url,
            type: "POST",
            contentType: false,
            processData: false,
            cache: false,
            success: function (data) {
                if (data.status == 'success') {
                    notification(data);
                    
                    $('#evaluationRemoveModal').modal('hide');
                    elem.button('reset');
                    
                    $(".evaluation-table").dataTable().fnDraw();
                } else {
                    elem.button('reset');
                    notification(data);
                }
            }
        });
    });

    $('.btn-modal-cancel-evaluation').click(function (e) {
        e.preventDefault();

        var elem = $(this),
            url = $('#cancelPlannedEvaluationModal').attr('data-src');

        elem.button('loading');

        $.ajax({
            url: url,
            type: "POST",
            contentType: false,
            processData: false,
            cache: false,
            success: function (data) {
                if (data.status == 'success') {
                    notification(data);
                    
                    $('#cancelPlannedEvaluationModal').modal('hide');
                    elem.button('reset');
                    
                    $(".evaluation-table").dataTable().fnDraw();
                } else {
                    elem.button('reset');
                    notification(data);
                }
            }
        });
    });
});

function removeEvaluation(elem) {
    $('#evaluationRemoveModal').attr('data-src', $(elem).attr("data-url")).modal('show');
    return false;
}

function informEmployee(elem) {
    var evalId = $(elem).data('eval_id');
    var informType = $(elem).data('type');
    var message = $(elem).data('message');
    var ownerId = $(elem).data('owner_id');

    var data = new FormData();
    data.append('evalId', evalId);
    data.append('type', informType);
    data.append('message', message);
    data.append('ownerId', ownerId);

    $.ajax({
        url: EVALUATION_INFORM,
        data: data,
        type: "POST",
        contentType: false,
        processData: false,
        cache: false,
        success: function (data) {
            if (data.status == 'success') {
                notification(data);
            } else {
                notification(data);
            }
        }
    });
}

function cancelPlanEvaluation(elem) {
    $('#cancelPlannedEvaluationModal').attr('data-src', $(elem).attr("data-url")).modal('show');
    
    return false;
}