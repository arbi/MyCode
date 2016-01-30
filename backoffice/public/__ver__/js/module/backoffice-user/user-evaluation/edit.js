$(function () {
    /**
     * Save Planned Evaluation
     */
    $('#save-planned-evaluation').click(function (e) {
        e.preventDefault();
        if ($('#edit-planned-evaluation-form').valid()) {
            var btn = $('#save-planned-evaluation'),
                data = new FormData(),
                evaluationItem,
                evaluationItemValue;

            btn.button('loading...');
            tinymce.triggerSave();

            data.append('creator_id', $('#creator_id').attr('value'));
            data.append('user_id', $('#user_id').attr('value'));
            data.append('evaluation_id', $('#evaluation_id').attr('value'));
            data.append('description', $('#evaluation_description').val());

            for (var i = 1; $('#evaluation_item_' + i).length > 0; i++) {
                evaluationItem = $('#evaluation_item_' + i);
                evaluationItemValue = evaluationItem.val();

                if (!parseFloat(evaluationItemValue)) {
                    evaluationItemValue = -1;
                }

                data.append('evaluation_item_' + i, evaluationItemValue);
            }

            $.ajax({
                url: SAVE_PLANNED_EVALUATION_URL,
                type: 'POST',
                data: data,
                contentType: false,
                processData: false,
                cache: false,
                success: function (response) {
                    if (response.status == 'success') {
                        window.location.href = window.location.origin + '/user/edit/' + $('#user_id').attr('value') + '#evaluations';
                    } else {
                        notification(response);
                    }
                }
            });
            btn.button('reset');
        }
    });

    $('.evaluation-input-items').on('keyup change', function() {
        scoreAvg();
    });

    $('.skip-button').click(function (e) {
        e.preventDefault();

        var status = $(this).attr('data-status');

        if (parseInt(status)) {
            $(this)
                .attr('data-status', 0)
                .text('Activate');

            $(this).closest('.input-group').find('input')
                .val(0)
                .prop('disabled', true);
        } else {
            $(this)
                .attr('data-status', 1)
                .text('Skip');

            $(this).closest('.input-group').find('input').prop('disabled', false);
        }
        scoreAvg();

    });

    /*
     * Apply TinyMCE on Add Evaluation Form - Description Element
     */
    if ($('.tinymce').length > 0) {
        tinymce.init({
            selector: ".tinymce",
            skin: "clean",
            plugins: [
                "code", "autoresize", "link"
            ],
            menu: {},
            toolbar: "undo redo | styleselect | bold italic underline |  aligncenter alignjustify alignleft alignright | bullist numlist outdent indent | link | print | fontsizeselect | code | removeformat",
            autoresize_min_height: 280,
            browser_spellcheck : true,
            extended_valid_elements : "i[*]",
            verify_html : false
        });
    }

});

// Sum Avg Score
function scoreAvg() {
    var sum = 0;
    var count = 0;

    for (var i = 1; $('#evaluation_item_' + i).length > 0; i++) {
        sum += parseFloat($('#evaluation_item_' + i).val());
        if (parseFloat($('#evaluation_item_' + i).val()) > 0) {
            count += 1;
        }
    }
    if (count > 0) {
        var avg = sum / count;
        $('#score_sum span.badge').text(avg.toFixed(2));
    } else {
        $('#score_sum span.badge').text(0);
    }
}