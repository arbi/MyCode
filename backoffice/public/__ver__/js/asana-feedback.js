$(function () {

    $('#asana-feedback-base-types').change(function() {
        var feedbackType = $(this).val();
        var $dropzoneContainer = $('.dropzone-container');
        $('#feedback-account-management-operation-type').html('');
        $.ajax({
            url: '/feedback/render-template',
            type: "POST",
            data: {
                feedback_type : feedbackType
            },
            cache: false,
            success: function (data) {
                if (data.status == 'success' && typeof data.partial_html != 'undefined') {
                    $('#asana-feedback-dynamic-part').html(data.partial_html);
                    //additional js for every case
                    switch (parseInt(feedbackType)) {
                        case GLOBAL_FEEDBACK_TYPE_SOFTWARE_FEEDBACK:
                            $dropzoneContainer.show();
                            $('#feedback-submit').parent().removeClass('col-sm-12').addClass('col-sm-4');
                            $('#feedback-submit-with-screenshot').show();
                            $('#feedback-title').rules('add', {
                                required: true
                            });
                            $('#feedback-description').rules('add', {
                                required: true
                            });
                            break;
                        case GLOBAL_FEEDBACK_TYPE_ACCOUNT_MANAGEMENT:
                            $dropzoneContainer.hide();
                            $('#feedback-submit').parent().removeClass('col-sm-4').addClass('col-sm-12');
                            $('#feedback-submit-with-screenshot').hide();
                            $('#feedback-account-management-type').trigger('change');
                            break;
                        case GLOBAL_FEEDBACK_TYPE_TRAINING_REQUEST:
                            $('#feedback-submit').parent().removeClass('col-sm-4').addClass('col-sm-12');
                            $('#feedback-submit-with-screenshot').hide();
                            $dropzoneContainer.hide();
                            $('#feedback-title').rules('add', {
                                required: true
                            });
                            $('#feedback-description').rules('add', {
                                required: true
                            });
                            break;
                        case GLOBAL_FEEDBACK_TYPE_ELECTRONICS_REQUEST:
                            $dropzoneContainer.hide();
                            $('#feedback-submit').parent().removeClass('col-sm-4').addClass('col-sm-12');
                            $('#feedback-submit-with-screenshot').hide();
                            $('#feedback-location').rules('add', {
                                required: true
                            });
                            $('#feedback-reason').rules('add', {
                                required: true
                            });
                            break;
                        case GLOBAL_FEEDBACK_TYPE_MARKETING_IDEA:
                            $dropzoneContainer.show();
                            $('#feedback-submit').parent().removeClass('col-sm-4').addClass('col-sm-12');
                            $('#feedback-submit-with-screenshot').hide();
                            $('#feedback-title').rules('add', {
                                required: true
                            });
                            $('#feedback-description').rules('add', {
                                required: true
                            });
                            break;
                        case GLOBAL_FEEDBACK_TYPE_CONTENT_IDEA:
                            $dropzoneContainer.show();
                            $('#feedback-submit').parent().removeClass('col-sm-4').addClass('col-sm-12');
                            $('#feedback-submit-with-screenshot').hide();
                            $('#feedback-title').rules('add', {
                                required: true
                            });
                            $('#feedback-description').rules('add', {
                                required: true
                            });
                            break;

                    }
                } else {
                    notification(data);
                }
            }
        });
    });

    $(document).on('change', '#feedback-application-types', function() {
        var selectedValue = $(this).val();
        if (selectedValue == GLOBAL_FEEDBACK_APPLICATION_TYPE_BACKOFFICE) {
            $('#feedback-submit').parent().removeClass('col-sm-12').addClass('col-sm-4');
            $('#feedback-submit-with-screenshot').show();
        } else {
            $('#feedback-submit').parent().removeClass('col-sm-4').addClass('col-sm-12');
            $('#feedback-submit-with-screenshot').hide();
        }

        // for mobile application type
        if (selectedValue == GLOBAL_FEEDBACK_APPLICATION_TYPE_MOBILE_APPLICATION) {
            var applicationTypeContainer = $(this).parent();
            $.ajax({
                url: '/feedback/render-mobile-application-types-template',
                type: "POST",
                data: {},
                cache: false,
                success: function (data) { console.log(applicationTypeContainer.next().attr('class'));
                    if (applicationTypeContainer.next().hasClass('mobile-application-sub-container')) {
                        applicationTypeContainer.next().remove();
                    }
                    if (data.status == 'success' && typeof data.partial_html != 'undefined') {
                        $(data.partial_html).insertAfter(applicationTypeContainer);
                    }
                }
            });
        } else {
            if ($(this).parent().next().hasClass('mobile-application-sub-container')) {
                $(this).parent().next().remove();
            }
        }

    });

        $(document).on('change', '#feedback-account-management-type', function() {
        var accountManagementOperation = $(this).val();
        $.ajax({
            url: '/feedback/render-account-management-template',
            type: "POST",
            data: {
                account_management_operation : accountManagementOperation
            },
            cache: false,
            success: function (data) {
                if (data.status == 'success' && typeof data.partial_html != 'undefined') {
                    $('#feedback-account-management-operation-type').html(data.partial_html);
                    //additional js for every case
                    switch (parseInt(accountManagementOperation)) {
                        case GLOBAL_FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_CREATE_ACCOUNT:

                            $('#feedback-firstname').rules('add', {
                                required: true
                            });

                            $('#feedback-lastname').rules('add', {
                                required: true
                            });

                            $('#feedback-existing-email-address').rules('add', {
                                required: true,
                                email: true
                            });

                            $('#feedback-position-and-title').rules('add', {
                                required: true
                            });

                            $('#feedback-location').rules('add', {
                                required: true
                            });

                            $('#feedback-personal-info').rules('add', {
                                required: false
                            });

                            $('#feedback-duedate').rules('add', {
                                required: true
                            });

                            $('#feedback-duedate').daterangepicker({
                                'singleDatePicker': true,
                                'format': globalDateFormat
                            });



                            break;

                        case GLOBAL_FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_REMOVE_ACCOUNT:

                            $('#feedback-account').rules('add', {
                                required: true
                            });

                            $('#feedback-account-username').rules('add', {
                                required: true
                            });

                            $('#feedback-duedate').rules('add', {
                                required: true
                            });

                            $('#feedback-google-drive-transfer-email').rules('add', {
                                required: false,
                                email: true
                            });

                            $('#feedback-duedate').daterangepicker({
                                'singleDatePicker': true,
                                'format': globalDateFormat
                            });

                            break;

                        case GLOBAL_FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_SUSPEND_ACCOUNT:

                            $('#feedback-account').rules('add', {
                                required: true
                            });

                            $('#feedback-account-username').rules('add', {
                                required: true
                            });

                            $('#feedback-duedate').rules('add', {
                                required: true
                            });

                            $('#feedback-duedate').daterangepicker({
                                'singleDatePicker': true,
                                'format': globalDateFormat
                            });

                            break;

                        case GLOBAL_FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_UNSUSPEND_ACCOUNT:

                            $('#feedback-account').rules('add', {
                                required: true
                            });

                            $('#feedback-account-username').rules('add', {
                                required: true
                            });

                            $('#feedback-duedate').rules('add', {
                                required: true
                            });

                            $('#feedback-duedate').daterangepicker({
                                'singleDatePicker': true,
                                'format': globalDateFormat
                            });

                            break;

                        case GLOBAL_FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_UNSUSPEND_ACCOUNT:

                            $('#feedback-mailing-list').rules('add', {
                                required: true
                            });

                            $('#feedback-account-username').rules('add', {
                                required: true
                            });
                            break;

                        case GLOBAL_FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_CALL_CENTER:
                            $('#feedback-full-name').rules('add', {
                                required: true
                            });

                            $('#feedback-department').rules('add', {
                                required: true
                            });



                            break;

                    }
                } else {
                    notification(data);
                }
            }
        });
    });

    Dropzone.options.feedbackDropzone = {
        url: '/feedback/upload?key=' + $('#feedback-form').attr('data-key'),
        parallelUploads: 1,
        maxFilesize: 5, // MB
        accept: function (file, done) {
            if ((new RegExp('image/')).test(file.type) || ['pdf', 'doc', 'docx', 'zip', 'xsl', 'xslx'].indexOf(file.name.split('.').pop()) != -1) {
                done();
            }
        },
        init: function () {
            this.on("addedfile", function (file) {
                if (!((new RegExp('image/')).test(file.type) || ['pdf', 'doc', 'docx', 'zip', 'xsl', 'xslx'].indexOf(file.name.split('.').pop()) != -1)) {
                    this.removeFile(file);
                }
            });

            var _this = this;

            $('#feedback-form').on("renew", function () {
                _this.removeAllFiles();
            });
        }
    };

    $('#submit-idea').on('click', function (e) {
        e.preventDefault();

        $('.feedback-bug').show();
        $('.feedback-content').show();
        $('#feedback-submit-with-screenshot').show();
        $('#feedback-submit').show();
        $('#asana-feedback-base-types').val(GLOBAL_FEEDBACK_TYPE_SOFTWARE_FEEDBACK).trigger('change');
        $(this).closest('.feedback-widget').toggleClass('open');
    });

    $('.feedback-bug').on('click', function (e) {
        e.preventDefault();

        $('.feedback-bug').hide();
        $('.feedback-content').hide();

        $(this).closest('.feedback-widget').removeClass('open');
    });

    $('#feedback-submit').click(function (e) {
        e.preventDefault();

        if ($('#feedback-form').valid()) {
            var self = '',
                fb_submit_scr = $('#feedback-submit-with-screenshot');

            if (fb_submit_scr.prop('disabled')) {
                self = fb_submit_scr;
            } else {
                self = $(this);
                fb_submit_scr.hide();
            }

            self.button('loading');
            var selectedType = parseInt($('#asana-feedback-base-types').val());
            var sendingData = {
                'key': $('#feedback-form').attr('data-key'),
                'selected-type': selectedType,
                'prop': {
                    'user_agent': navigator.userAgent,
                    'screen_size': $(document).width() + 'x' + $(document).height() + ' (' + window.screen.width + 'x' + window.screen.height + ')',
                    'lang': navigator.language,
                    'url': window.location.href
                }
            };
            switch(selectedType) {
                case GLOBAL_FEEDBACK_TYPE_SOFTWARE_FEEDBACK:

                    sendingData['feedback-title']              = $('#feedback-title').val();
                    sendingData['feedback-application-types']  = $('#feedback-application-types').val();
                    sendingData['feedback-description']        = $('#feedback-description').val();
                    sendingData['feedback-is-bug']             = $('#feedback-is-bug').is(':checked') ? 1 : 0;
                    if ($('#mobile-application-sub-type option:selected') != undefined) {
                        sendingData['mobile-application-sub-type'] = $('#mobile-application-sub-type option:selected').val();
                    }

                    break;
                case GLOBAL_FEEDBACK_TYPE_ACCOUNT_MANAGEMENT:
                    sendingData['feedback-account-management-type'] = parseInt($('#feedback-account-management-type').val());
                    switch (sendingData['feedback-account-management-type']) {
                        case GLOBAL_FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_CREATE_ACCOUNT:
                            sendingData['feedback-firstname'] = $('#feedback-firstname').val();
                            sendingData['feedback-lastname']  = $('#feedback-lastname').val();
                            sendingData['feedback-duedate']   = $('#feedback-duedate').val();
                            sendingData['feedback-existing-email-address'] = $('#feedback-existing-email-address').val();
                            sendingData['feedback-bo-account'] = ($('#feedback-bo-account').is(':checked')) ? 1 : 0;
                            sendingData['feedback-google-account'] = ($('#feedback-google-account').is(':checked')) ? 1 : 0;
                            sendingData['feedback-lastpass-account'] = ($('#feedback-lastpass-account').is(':checked')) ? 1 : 0;
                            sendingData['feedback-other-account'] = $('#feedback-other-account').val();
                            sendingData['computer-setup'] = ($('#computer-setup').is(':checked')) ? 1 : 0;
                            sendingData['feedback-department'] = $('#feedback-department').val();
                            sendingData['feedback-position-and-title'] = $('#feedback-position-and-title').val();
                            sendingData['feedback-location'] = $('#feedback-location').val();
                            sendingData['feedback-personal-info'] = $('#feedback-personal-info').val();
                            sendingData['feedback-subscribe-google-groups'] = ($('#feedback-subscribe-google-groups').is(':checked')) ? 1 : 0;

                            break;

                        case GLOBAL_FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_REMOVE_ACCOUNT:

                            sendingData['feedback-account'] = $('#feedback-account').val();
                            sendingData['feedback-account-username']   = $('#feedback-account-username').val();
                            sendingData['feedback-duedate']  = $('#feedback-duedate').val();
                            sendingData['feedback-google-drive-transfer-email']   = $('#feedback-google-drive-transfer-email').val();

                            break;

                        case GLOBAL_FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_SUSPEND_ACCOUNT:

                            sendingData['feedback-account'] = $('#feedback-account').val();
                            sendingData['feedback-duedate']  = $('#feedback-duedate').val();
                            sendingData['feedback-account-username']   = $('#feedback-account-username').val();

                            break;

                        case GLOBAL_FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_UNSUSPEND_ACCOUNT:

                            sendingData['feedback-account'] = $('#feedback-account').val();
                            sendingData['feedback-duedate']  = $('#feedback-duedate').val();
                            sendingData['feedback-account-username']   = $('#feedback-account-username').val();

                            break;

                        case GLOBAL_FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_MAILING_LISTS:

                            sendingData['feedback-mailing-list-action'] = $('#feedback-mailing-list-action').val();
                            sendingData['feedback-mailing-list'] = $('#feedback-mailing-list').val();
                            sendingData['feedback-account-username']   = $('#feedback-account-username').val();

                            break;

                        case GLOBAL_FEEDBACK_SUBTYPE_ACCOUNT_MANAGEMENT_CALL_CENTER:

                            sendingData['feedback-department'] = $('#feedback-department').val();
                            sendingData['feedback-full-name'] = $('#feedback-full-name').val();
                            sendingData['feedback-reason']   = $('#feedback-reason').val();

                            break;

                    }
                    break;
                case GLOBAL_FEEDBACK_TYPE_TRAINING_REQUEST:

                    sendingData['feedback-title'] = $('#feedback-title').val();
                    sendingData['feedback-description'] = $('#feedback-description').val();

                    break;

                case GLOBAL_FEEDBACK_TYPE_ELECTRONICS_REQUEST:

                    sendingData['feedback-location'] = $('#feedback-location').val();
                    sendingData['feedback-reason'] = $('#feedback-reason').val();

                    break;

                case GLOBAL_FEEDBACK_TYPE_MARKETING_IDEA:

                    sendingData['feedback-title'] = $('#feedback-title').val();
                    sendingData['feedback-description'] = $('#feedback-description').val();

                    break;
                case GLOBAL_FEEDBACK_TYPE_CONTENT_IDEA:

                    sendingData['feedback-title'] = $('#feedback-title').val();
                    sendingData['feedback-description'] = $('#feedback-description').val();

                    break;

            }
            $.ajax({
                url: '/feedback/save',
                type: "POST",
                data: sendingData,
                cache: false,
                success: function (data) {
                    if (data.status == 'success') {
                        $('#feedback-form').trigger('renew');
                        $('#submit-idea').trigger('click');

                        var trackMessage = '';
                        if (data.data.asana_user) {
                            trackMessage = ', you can track your ticket <a href="' + data.data.url + '" target="_blank">using this Asana link</a>';
                        }

                        var notice = new PNotify({
                            title: 'Thank you for your feedback.',
                            text: 'We\'ll get back to you shortly' + trackMessage,
                            type: data.status,
                            shadow: false,
                            hide: false,
                            buttons: {
                                closer: true,
                                sticker: true
                            }
                        });

                        $('.feedback-content').hide();
                        $('.feedback-bug').hide();

                        notice.get().click(function () {
                            notice.remove();
                        });

                    } else {
                        var notice = new PNotify({
                            title: 'Error',
                            text: data.msg,
                            type: data.status,
                            shadow: false,
                            hide: false,
                            buttons: {
                                closer: true,
                                sticker: true
                            }
                        });
                    }

                    self.button('reset');
                }
            });
        }
    });

    $('#feedback-submit-with-screenshot').click(function (e) {
        e.preventDefault();

        var self = $(this);

        if ($('#feedback-form').valid()) {
            self.button('loading');
            $('#feedback-submit').hide();

            html2canvas(document.body, {
                onrendered: function (canvas) {
                    var dataURL = canvas.toDataURL("image/png");

                    $.ajax({
                        url: '/feedback/upload?base64=1&key=' + $('#feedback-form').attr('data-key'),
                        type: "POST",
                        data: {
                            'file': dataURL
                        },
                        cache: false,
                        success: function (data) {
                            if (data.status == 'success') {
                                $('#feedback-submit').trigger('click');
                            } else {
                                self.button('reset');
                            }
                        }
                    });
                }
            });
        }
    });


    $('#feedback-form').validate({
        ignore: [],
        highlight: function (element, errorClass, validClass) {
            $(element).closest('.form-group').removeClass('has-success').addClass('has-error');
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).closest('.form-group').removeClass('has-error').addClass('has-success');
        },
        success: function (label) {
            $(label).closest('form').find('.valid').removeClass("invalid");
        },
        errorPlacement: function (error, element) {
        }
    });
});
