$(function() {
    $("#attach-btn").click(function() {
        $("#input-cv").trigger("click");
    });

    $("#input-cv").change( function() {
        var filename = $(this).val().split('\\').pop();
        $(this).next().text(filename);
        $(this).parent().removeClass("has-error");
    });

    $("#submit-application").click(function() {
        $(this).text("Processing...");
        $(this).prop('disabled', true);
        $("html, body").animate({ scrollTop: 0 }, "slow");
        $("#job-form-dialog form").submit();
    });

    $("#job-form-dialog form").submit(function() {
        var possibleExtensions = ['pdf', 'doc', 'docx', 'odt', 'rtf'];
        var fileExt = $("#input-cv").val().split(".");
        var btn = $("#submit-application");
        fileExt = fileExt[fileExt.length - 1];

        if($("#input-cv").val() && possibleExtensions.indexOf(fileExt) == -1) {
            $("#job-form-dialog .modal-body").append(
                $("#failure-msg-file").html()
            );

            btn.prop('disabled', false);
            btn.text(btn.attr("data-text"));
            return;
        }

        if($(this).valid()) {
            showLoader();
            $.ajax({
                type: "POST",
                url: "/jobs/apply",
                data: new FormData( this ),
                processData: false,
                contentType: false,
                success: function(data) {
                    if (data.status && data.status == 'success') {
                        $("#notifications").html(
                            $("#success-msg").html()
                        );
                    } else {
                        $("#notifications").html(
                            $("#failure-msg").html()
                        );
                    }
                    $("#job-form-dialog form")[0].reset();
                    hideLoader();
                },
                error: function() {
                    $("#notifications").html(
                        $("#failure-msg").html()
                    );
                    hideLoader();
                }
            });
            $("#job-form-dialog").modal("hide");
        }
        btn.prop('disabled', false);
        btn.text(btn.attr("data-text"));
        return false;
    });

    function showLoader() {
        $("#loader-dialog").modal({
            show: true,
            keyboard: false,
            backdrop: 'static'
        });
    }

    function hideLoader() {
        $("#loader-dialog").modal('hide');
    }
});
