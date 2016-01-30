var transactionPendingText = 'There is a pending transaction.\n No other actions can be taken on this reservation until the transaction cancelled or completed.',
	chargePendingText = 'You have a pending charge',
	accCurrencyRate = $('#acc_currency_rate').val(),
	accCurrencySign = $('#acc_currency_sign').val(),
	taxTypePercent = 1,
	cc = $('.credit-card'),
	CHARGEBACK_ARRAY = [
		TRANSACTION_CHARGEBACK_FRAUD,
		TRANSACTION_CHARGEBACK_DISPUTE,
		TRANSACTION_CHARGEBACK_OTHER
	];

$(function() {
    /** Datatable configuration */
    if (jQuery().dataTable) {
        $.fn.dataTableExt.afnFiltering.push(

            function (oSettings, aData, iDataIndex) {
                var myRowClass = oSettings.aoData[iDataIndex].nTr.className;
                var checkSelectedButton = $('.history-switch a.active');

                if (checkSelectedButton.hasClass('all') || myRowClass.indexOf('warning') == -1) {
                    return true;
                }
                return false;
            }
        );

        var dataTabelHistory = $('#datatable_history').DataTable({
            bFilter: true,
            bInfo: true,
            bServerSide: false,
            bProcessing: false,
            bPaginate: true,
            bAutoWidth: false,
            bStateSave: true,
            iDisplayLength: 25,
            sAjaxSource: null,
            sPaginationType: "bootstrap",
            aaSorting: [[0, 'desc']],
            aaData: historyAaData,
            sDom: 'l<"enabled-history">frti<"bottom"p><"clear">',
            aoColumns:[
                {
                    "name": "date",
                    "width": "150px"
                }, {
                    "name": "user",
                    "width": "200px"
                }, {
                    "name": "message",
                    "sortable": false
                }, {
                    "name": "frontier",
                    "width": "20px",
                    "sortable": true
                }
            ]
        });

        $('.fn-buttons-history a').on('click', function(e) {
            e.preventDefault();

            $(this).closest('.history-switch').find('.fn-buttons-history a').removeClass('active');
            $(this).addClass('active');
            dataTabelHistory.draw();
        });


        if (bookingDocListAaData) {
            $('.attachment-tab-link').append('<span class="badge">' + bookingDocListAaData.length + '</span>');
            attachTable = $('#datatable_attachment').dataTable({
                bFilter: true,
                bInfo: true,
                bServerSide: false,
                bProcessing: false,
                bPaginate: true,
                bAutoWidth: false,
                bStateSave: true,
                iDisplayLength: 25,
                sAjaxSource: null,
                sPaginationType: "bootstrap",
                aaSorting: [[0, 'desc']],
                aaData: bookingDocListAaData,
                aoColumns:[
                    {
                        "name": "date",
                        "width": "150px"
                    }, {
                        "name": "attacher",
                        "width": "200px"
                    }, {
                        "name": "description"
                    },  {
                        "name": "download",
                        "sortable": false,
                        "width": "1"
                    },  {
                        "name": "action",
                        "sortable": false,
                        "width": "1"
                    }
                ]
            });
        } else {
            $('#datatable_attachment_wrapper').remove();
            $('#attachment').html(
                '<div class="alert alert-success" role="alert">' +
                '<span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>' +
                ' There are no items to display</div>'
            );
        }

        tasksTable = $('#datatable_tasks').dataTable({
            bAutoWidth: false,
            bFilter: true,
            bInfo: true,
            bPaginate: true,
            bProcessing: true,
            bServerSide: true,
            bStateSave: true,
            iDisplayLength: 25,
            sPaginationType: "bootstrap",
            sDom: 'l<"enabled">frti<"bottom"p><"clear">',
            aaSorting: [[2, "asc"]],
            aoColumns:[
                {
                    name: "priority",
                    width: "1"
                }, {
                    name: "status",
                    width: "25"
                }, {
                    name: "start_date",
                    width: "110"
                }, {
                    name: "end_date",
                    width: "110"
                }, {
                    name: "title"
                }, {
                    name: "type"
                },  {
                    name: "creator"
                }, {
                    name: "responsible"
                }, {
                    name: "view",
                    sortable: false,
                    searchable: false,
                    width: "1"
                }
            ],
            aoColumnDefs:
                [
                    {
                        aTargets: [1],
                        fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {
                            var $cell = $(nTd);
                            var value = $cell.text();
                            var firstCharacter = value.charAt(0);
                            var labelClass = '';
                            switch (firstCharacter) {
                                case 'N':
                                    labelClass = 'label-info';
                                    break;
                                case 'S':
                                    labelClass = 'label-primary';
                                    break;
                                case 'V':
                                    labelClass = 'label-warning';
                                    break;
                                case 'D':
                                    labelClass = 'label-success';
                                    break;
                                case 'C':
                                    labelClass = 'label-danger';
                                    break;
                                case 'B':
                                    labelClass = 'label-danger';
                                    break;
                            }
                            $cell.html('<label class="task-label label '+ labelClass +'" title="'+value+'">'+firstCharacter+'</label>');
                        }
                    }
                ],
            ajax: {
                url: TASKS_DATATABLE_AJAX_SOURCE,
                data: function (d) {
                    d.all = $("#task-show-status").attr('value');
                    d.reservationId = $("#booking_id").attr('value')
                }
            }
        });
    }

    $("div.enabled").html($('#task-status-switch').html());
    $('#task-status-switch').remove();

    $('.fn-buttons a').on('click', function(e) {
        e.preventDefault();

        $('.fn-buttons a').removeClass('active');
        $(this).addClass('active');

        $("#task-show-status").val($(this).attr('data-status'));

        tasksTable.fnDraw();
    });

	$(window).on("beforeunload", function (e) {
		if ((hasPendingCharge() && $('#chargeClick').val() != 'click') ||
			(hasPendingTransaction() && $('#transactionClick').val() != 'click')) {
			var confirmationMessage = '';

			if (hasPendingTransaction()) {
				confirmationMessage += transactionPendingText;
			}

			if (hasPendingCharge()) {
				confirmationMessage += chargePendingText;
			}

			(e || window.event).returnValue = confirmationMessage; //Gecko + IE

			return confirmationMessage;    //Webkit, Safari, Chrome etc.
		}
	});

    if ($("#apartel-label").length) {
        $("#apartel-label").html($("#apartel-label").text() + ': ' + $('#apartel_id option:selected').text());
    }

    $("#move-reservation-btn").click(function() {
        $.ajax({
            type: "POST",
            url: GLOBAL_GET_MOVE_DESTINATIONS_URL,
            data: {
                id: GLOBAL_APARTMENT_ID_ASSIGNED,
                rateOccupancy: RATE_OCCUPANCY,
                dateFrom: $("#dateFrom").val(),
                dateTo: $("#dateTo").val()
            },
            dataType: "json",
            success: function(data) {
                if (data.status == "success"){
                    $('#apartment-id-assigned').html('');

                    if (data.destinations.length != 0) {
                        $.each(data.destinations, function(apartel, apartments) {
                            $('#apartment-id-assigned').append($('<optgroup>', {
	                            label : apartel
                            }));

                            $.each(apartments, function(apartmentId, apartmentName) {
                                $('#apartment-id-assigned')
                                    .append($('<option>', {
		                                value : apartmentId
	                                })
		                            .text(apartmentName));
                            });
                        });

                        $('#move-alerts').html('');

                        if ($("#finance_key_instructions:checked").length) {
                            var alertType = 'warning';
                            if (GLOBAL_KEY_PAGE_TYPE == 1) {
                                alertType = 'danger';
                            }
                            $("#move-alerts").append('<div class="alert alert-' + alertType + '" role="alert"><button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button> Key Instruction Viewed.</div>')
                        }
                        $("#moveReservationModal").modal("show");
                    } else {
                        msg = {
                            status: "error",
                            msg: "No available apartments to move to."
                        }
                        notification(msg);

                    }
                } else {
                    notification(data);
                }
            }
        });
    });

    $("#confirm-move-btn").click(function() {
        var apartmentId = $("#apartment-id-assigned").val(),
            resId = $("#booking_id").val();

        $.ajax({
            type: "POST",
            url: GLOBAL_MOVE_RESERVATION_URL,
            data: {
                resId: resId,
                apartmentId: apartmentId
            },
            dataType: "json",
            success: function(data) {
                if (data.status == 'reload') {
                    location.reload();
                } else {
                    notification(data);
                }
            }
        });
    });

    $("#finance_booked_state").change(function() {
        if ($("#finance_booked_state").val() !== $("#finance_booked_state_changed").attr('data-current')) {
            $("#finance_booked_state_changed").val(1);
        } else {
            $("#finance_booked_state_changed").val(0);
        }
    });

	$('#booking_arrival_time').datetimepicker({
		datepicker: false,
		format: 'H:i',
		step: 30
	});

    // highlights dates
    /*if (jQuery().daterangepicker) {
        $('.change-datepicker-checkin').daterangepicker({
            'singleDatePicker': true,
            'format': 'YYYY-MM-DD'
        },
        function (start, end, label) {
            var date  = start._d;
            var month = parseInt(date.getUTCMonth()) + 1,
                day   = parseInt(date.getUTCDate()) + 1,
                dateFormat = date.getUTCFullYear() + '-' + (month < 10 ? '0' + month : month) + '-' + (day < 10 ? '0' + day : day);

            var datepickerClass = $(this)[0].container[0].getAttribute('class');
            datepickerClass     = datepickerClass.replace(" ", ".");;
            var datepicker      = $('.' + datepickerClass);

            console.log(datepicker);

            for (var v in CHARGE_DATES_CHECK_IN) {
                var item = CHARGE_DATES_CHECK_IN[v];

                if (item.date == dateFormat) {
                    return {
                        classes: item.class
                    };
                }
            }
        })
        .on('show.daterangepicker', function (e) {
            previousEndDate = $(this).val();
        })
        .on('hide.daterangepicker', function (e) {
            if ($(this).val() === '' || $(this).val() === null) {
                $(this).val(previousEndDate).daterangepicker('update');
            }
        })
        .on('apply.daterangepicker', function (e) {
            changeDateForInfo();
        });

        $('.change-datepicker-checkout').daterangepicker({
            'singleDatePicker': true,
            'format': 'YYYY-MM-DD'
        },
        function (start, end, label) {
            var date  = start._d;
            var month = parseInt(date.getUTCMonth()) + 1,
                day = parseInt(date.getUTCDate()) + 1,
                dateFormat = date.getUTCFullYear() + '-' + (month < 10 ? '0' + month : month) + '-' + (day < 10 ? '0' + day : day);

            for (var v in CHARGE_DATES_CHECK_OUT) {
                var item = CHARGE_DATES_CHECK_OUT[v];

                if (item.date == dateFormat) {
                    return {classes: item.class};
                }
            }
        })
        .on('show.daterangepicker', function (e) {
            previousEndDate = $(this).val();
        })
        .on('hide.daterangepicker', function (e) {
            if ($(this).val() === '' || $(this).val() === null) {
                $(this).val(previousEndDate).daterangepicker('update');
            }
        })
        .on('apply.daterangepicker', function (e) {
            changeDateForInfo();
        });
    }*/

    // highlights dates
    if (jQuery().datepicker) {
        $('.change-datepicker-checkin').datepicker({
            format: 'yyyy-mm-dd',
            beforeShowDay: function (date) {
                var month = parseInt(date.getUTCMonth()) + 1,
                    day = parseInt(date.getUTCDate()) + 1,
                    dateFormat = date.getUTCFullYear() + '-' + (month < 10 ? '0' + month : month) + '-' + (day < 10 ? '0' + day : day);

                for (var v in CHARGE_DATES_CHECK_IN) {
                    var item = CHARGE_DATES_CHECK_IN[v];

                    if (item.date == dateFormat) {
                        return {
                            classes: item.class
                        };
                    }
                }
            }
        })
            .on('show', function (e) {
                previousEndDate = $(this).val();
            })
            .on('hide', function (e) {
                if ($(this).val() === '' || $(this).val() === null) {
                    $(this).val(previousEndDate).datepicker('update');
                }
            })
            .on('changeDate', function (e) {
                $(this).datepicker('hide');
                changeDateForInfo();
            });

        $('.change-datepicker-checkout').datepicker({
            format: 'yyyy-mm-dd',
            beforeShowDay: function (date) {
                var month = parseInt(date.getUTCMonth()) + 1,
                    day = parseInt(date.getUTCDate()) + 1,
                    dateFormat = date.getUTCFullYear() + '-' + (month < 10 ? '0' + month : month) + '-' + (day < 10 ? '0' + day : day);

                for (var v in CHARGE_DATES_CHECK_OUT) {
                    var item = CHARGE_DATES_CHECK_OUT[v];

                    if (item.date == dateFormat) {
                        return {classes: item.class};
                    }
                }
            }
        })
            .on('show', function (e) {
                previousEndDate = $(this).val();
            })
            .on('hide', function (e) {
                if ($(this).val() === '' || $(this).val() === null) {
                    $(this).val(previousEndDate).datepicker('update');
                }
            })
            .on('changeDate', function (e) {
                $(this).datepicker('hide');
                changeDateForInfo();
            });
    }

    $('.btn-send-frontier').click(function(e) {
        e.preventDefault();
        var $self = $(this);
        var id = $self.attr('data-id');
        $('#chosen-comment-id').val(id);
        $('#makeVisibleToFrontierModal').modal('show');
    });
    $('.btn-send-frontier-confirm').click(function(e) {
        var id = $('#chosen-comment-id').val();
        var $btn = $('#btn-send-frontier-' + id);
        e.preventDefault();
        $.ajax({
            type: "POST",
            url: MAKE_COMMENT_VISIBLE_TO_FRONTIER,
            data: {
                id: id
            },
            dataType: "json",
            success: function(data) {
                notification(data);
                if (data['status'] == 'success') {
                    $btn
                        .removeClass('btn-send-frontier')
                        .removeClass('glyphicon-eye-close')
                        .removeClass('text-danger')
                        .addClass('glyphicon-eye-open')
                        .addClass('text-success')
                        .attr('data-content', 'This comment is visible to Frontier.')
                        .unbind('click');
                }
                $('#makeVisibleToFrontierModal').modal('hide');
            }
        });
    });
});

function hasPendingProccess(e) {
    if (hasPendingCharge() || hasPendingTransaction()) {
        var confirmationMessage = '';

        if (hasPendingTransaction()) {
	        confirmationMessage += transactionPendingText;
        }

        if (hasPendingCharge()) {
	        confirmationMessage += chargePendingText;
        }

        var message = {
            msg: confirmationMessage,
            status: "error"
        }
        notification(message);

        e.preventDefault();
        // don't fix this js error, it's meant to be so...
        e.stopPropagate();
        return false;
    }
}

$('select#email-options').on('change', function () {
	$('#selected-email').val($('#email-options option:selected').text());
});

$('select#key-email-options').on('change', function () {
	$('#selected-email').val($('#key-email-options option:selected').text());
});

$('select#ccca-email-options').on('change', function () {
	$('#selected-email').val($('#ccca-email-options option:selected').text());
});

$('.receipt-body').on('change', 'select#receipt-email-options', function () {
	$('#selected-email').val($('#receipt-email-options option:selected').text());
});

$('select#payment-email-options').on('change', function () {
	$('#selected-email').val($('#payment-email-options option:selected').text());
});

$('select#cancel-email-options').on('change', function () {
    $('#selected-email').val($('#cancel-email-options option:selected').text());
});

$('#mail-guest-res').click(function () {
	$('#send-email a.resend-mail').attr('onclick', 'resend_mail(2)');

	if (SECONDARY_EMAIL) {
		$('#send-email').modal();
	} else {
		resend_mail(2);
	}
});

$('#mail-guest-review').click(function () {
	$('#send-email a.resend-mail').attr('onclick', 'resend_mail(4)');

	if (SECONDARY_EMAIL) {
		$('#send-email').modal();
	} else {
		resend_mail(4);
	}
});

if ($("#guest_key_instructions").length > 0) {
	$( "#guest_key_instructions" ).click(function() {
		var viewText = '';

		if (GLOBAL_GUEST_BALANCE < 0) {
			viewText += 'This Guest has an unpaid balance. <br>';
		}

		if (GLOBAL_VALID_CARD == 2) {
			viewText += 'This Guest has an invalid credit card. <br>';
		}

		if (GLOBAL_FRAUD_DETECT >= 40) {
			viewText += 'This Guest has a fraud score >= 40. <br>';
		}

		if (viewText == '') {
			$('#send-email a.resend-mail').attr('onclick', 'resend_mail(3)');

			if (SECONDARY_EMAIL) {
				$('#send-email').modal();
			} else {
				resend_mail(3);
			}
		} else {
			$('#guest_key_instructionsModal .modal-body .key-warning').html(viewText);
			$('#guest_key_instructionsModal').modal();
		}
	});
}

function resend_mail(num) {
    var id       = $("#booking_id").val();
    var btn      = $('#sendMailSend');
    var modalBtn = $('#resend-mail-btn');

    var arrow = $('#sendMailarrow');

    btn.button('loading');
	modalBtn.button('loading');

    if(num == 3)
         $('#guest_key_instructionsModal').modal('hide');
    arrow.hide();

    $.post(
        GLOBAL_SEND_MAIL,
        {
            id: id,
            num: num,
            email: $('#selected-email').val()
        },
        function(data) {
            if (data.status == 'success') {
                $('#send-email').modal('hide');
            }
             notification(data);
             arrow.show();
             btn.button('reset');
             modalBtn.button('reset');
        }
    );
}

if ($("#reservation_action_request_payment_details").length > 0) {
    $( "#reservation_action_request_payment_details" ).click(function(e) {
        hasPendingCharge(e);
        $('#genereteNewLinkModal').modal();
    });
}

function generete_new_link(num) {
    $('.genereteNewLink').hide();

    var btn = $('#generete_nl'),
	    booking_id = $('#booking_id').val();

	btn.button('loading');

    $.ajax({
        type: "POST",
        url: GLOBAL_GENERATE_PAGE,
        data: {
	        id: booking_id,
	        num: num,
	        email: $('#selected-email').val()
        },
        dataType: "json",
        success: function(data) {
            $('#genereteNewLinkModal').modal('hide');

            if (data['success']) {
                location.reload();
            } else {
                btn.button('reset');
                $('.genereteNewLink').show();
                notification(data);
            }
        }
    });
}

if ($("#reservation_action_close_payment_request").length > 0) {
    $( "#reservation_action_close_payment_request" ).click(function(e) {
        hasPendingProccess(e);

        var btn = $('#reservation_action_close_payment_request'),
	        booking_id = $('#booking_id').val();

	    btn.button('loading');

        $.ajax({
            type: "POST",
            url: GLOBAL_GENERATE_RESET,
            data: {id: booking_id},
            dataType: "json",
            success: function(data) {
                location.reload();
            }
        });
    });
}

$("#save_data").click(function(e) {
    hasPendingProccess(e);

    if (GLOBAL_STATUS == 1 &&
        GLOBAL_STATUS != $('#booking_statuses').val() &&
        $('#booking_statuses').val() != BOOKING_STATUS_FRAUDULATION &&
        $('#booking_statuses').val() != BOOKING_STATUS_NOSHOW  &&
        $('#booking_statuses').val() != BOOKING_STATUS_UNWANTED) {
        $('#saveModal').modal('show');
	} else if ((GLOBAL_STATUS != 17) && ($('#booking_statuses').val() == BOOKING_STATUS_UNWANTED)) {
		// if booking status is not canceled (unwanted) but changed it to canceled (unwanted)
		$('#blacklistModal').modal('show');
		return 0;
	} else {
        save_data_process('no', 0);
    }
});

if ($("#change-date-process").length) {
    $('#change-date-process').click(function() {
        $('#beforeSaveDataModal').modal('hide');
        save_data_process('no', 0);
    });
}


function save_data_process(check, addToBlackList) {
    $('#saveModal').modal('hide');

    var validate = $('#booking-form').validate();

	if ($('#booking-form').valid()) {
		var btn = $('#save_data'),
			obj = $('#booking-form').serializeArray();
            obj.push({
	            name: 'check_mail',
	            value: check
			},{
				name: 'add_to_blacklist',
				value: addToBlackList
			});

		btn.button('loading');

		$.ajax({
			type: "POST",
			url: SAVE_DATA,
			data: obj,
			dataType: "json",
			success: function(data) {
				if (data.status == 'reload') {
					location.reload();
				} else {
					notification(data);
                    btn.button('reset');
				}
			}
		});
	} else {
		validate.focusInvalid();
	}
}

$(function() {
	$('#booking-form').validate({
		rules: {
			guest_email: {
				required: true,
				email: true
			},
			guest_name:{
				required: true
			},
			guest_last_name:{
				required: true
			},
			guest_address:{
				required: false
			},
			guest_city:{
				required: false
			},
			second_guest_email: {
				required: false,
				email: true
			}
		},
		highlight: function (element, errorClass, validClass) {
			$(element).closest('.form-group').removeClass('has-success').addClass('has-error');
		},
		unhighlight: function (element, errorClass, validClass) {
			$(element).closest('.form-group').removeClass('has-error').addClass('has-success');
		},
		success: function (label) {
			$(label).closest('form').find('.valid').removeClass("invalid");
		},
		errorPlacement: function (error, element) {}
	});

    $('#charge-form').validate({
        highlight: function (element, errorClass, validClass) {
                $(element).closest('.form-group').removeClass('has-success').addClass('has-error');
        },
        unhighlight: function (element, errorClass, validClass) {
                $(element).closest('.form-group').removeClass('has-error').addClass('has-success');
        },
        success: function (label) {
                $(label).closest('form').find('.valid').removeClass("invalid");
        },
        errorPlacement: function (error, element) {}
    });

    $("#transaction-form").validate({
        highlight: function (element, errorClass, validClass) {
                $(element).closest('.form-group').removeClass('has-success').addClass('has-error');
        },
        unhighlight: function (element, errorClass, validClass) {
                $(element).closest('.form-group').removeClass('has-error').addClass('has-success');
        },
        success: function (label) {
                $(label).closest('form').find('.valid').removeClass("invalid");
        },
        errorPlacement: function (error, element) {}
    });

    $.validator.addMethod("amount", function(value, element) {
		return this.optional(element) || /^[0-9]+(\.[0-9]{1,2})?$/i.test(value);
	}, "Amount is invalid");

    $.validator.addMethod("notZero", function(value, element) {
		return this.optional(element) || ((value == 0) ? false:true);
	}, "Invalid data");


    $.validator.addMethod("checkBeckoffiseUser", function(value, element) {
        var userId = parseInt($('#userCache_id').val());
		return this.optional(element) || ((userId > 0) ? true:false);
	}, "Not Backoffice User");

    $.validator.addMethod("percentValid", function(value, element) {
          return this.optional(element) ||  /(^100([.]0{1,2})?)$|(^\d{1,2}([.]\d{1,2})?)$/i.test(value);
	}, "Percent Field");

    jQuery.validator.addClassRules('charge_valid', {
        amount:true
    });

    jQuery.validator.addClassRules('percent_valid', {
        percentValid:true
    });

    jQuery.validator.addClassRules('charge_required', {
        required: true
    });

    jQuery.validator.addClassRules('notZero', {
        required: true,
        notZero:true
    });

     jQuery.validator.addClassRules('notBeckoffiseUser', {
        checkBeckoffiseUser:true,
        required: true
    });
    /************TAB*******************/
    var hash = window.location.hash;
    hash && $('ul.nav a[href="' + hash + '"]').tab('show');

    if (hash == '#financial_details') {
        $('.financeButtons').show();
    } else if(hash == '#booking_details') {
        $('#move-reservation-btn').show();
        $('#change-date-btn').show();
    } else if (hash == '#attachment') {
        allButtonHide();
        $('#attachBtn').show();
    } else if (hash == '#tasks') {
        allButtonHide();
        $('#add-task-btns').show();
    } else if (hash == '#identity') {
        allButtonHide();
    }

    $('.nav-tabs a').click(function (e) {
        //hide charge part
        $('#charge_part').hide();
        //hide transaction part
        $('#transaction_part').hide();
        $('#logs').hide();
        $('#change_reservation_tab_part').hide();

        window.location.hash = this.hash;
        allButtonHide();
        $('.buttonsTicket').show();

        if (this.hash == '#financial_details') {
            $('.financeButtons').show();
        } else {
            $('.financeButtons').hide();
        }

        if (this.hash == '#booking_details') {
            $('#move-reservation-btn').show();
            $('#change-date-btn').show();
        } else {
            $('#move-reservation-btn').hide();
            $('#change-date-btn').hide();
        }
    });

    /*********If click charg & Transec view modal ******/
    var checkCollection_cookie = getCookie('checkCollection');
    if (checkCollection_cookie != null) {
        collectionButton();
        deleteCookie('checkCollection');
    }
    /*************************************************/

    batchAutocomplete('userCache', 'userCache_id', GLOBAL_GET_USER);
    /**********VIEW ERROR ON PAGE *****************/
    var view_error_page   = '',
	    view_warning_page = '';

    if (1 == GLOBAL_OVERBOOKING_STATUS && GLOBAL_STATUS == 1) { // do not show overbooking warning message in case the ticket was cancelled
        view_error_page += "<p>" + GLOBAL_TXT_OVERBOOKING + "</p>";
    }

    if (1 == GLOBAL_NO_COLLECTION) {
        view_error_page += "<p>" + GLOBAL_TXT_NO_COLLECTION + "</p>";
    }

    if (GLOBAL_FRAUD_DETECT >= 100) {
        view_error_page += "<p>" + GLOBAL_TXT_FRAUD_DETECT + "</p>";
    }

    if (GLOBAL_HAS_DEBT_BALANCE) {
        view_error_page += "<p>" + GLOBAL_TXT_HAS_DEBT_BALANCE + "</p>";
    }

    if (GLOBAL_HAS_ISSUES) {
        view_error_page += GLOBAL_HAS_ISSUES_TEXT;
    }

    if (GLOBAL_CARD_PENDING) {
        view_warning_page = GLOBAL_CARD_PENDING_TEXT;
    }

	if (CHANGE_APARTMENT_OCCUPANCY) {
        view_warning_page += CHANGE_APARTMENT_OCCUPANCY;
    }

    if(view_error_page != '') {
        notification({status:'error', msg:view_error_page});
    }

    if (view_warning_page != '') {
        notification({status:'warning', msg:view_warning_page});
    }

    /************DISBALE RIGHT CLICK ON TRANSECTION SECTOR**************/
    $('#transaction_acc_amount').bind("contextmenu",function(e){
        return false;
    });

    /************Scroll to bottom*************/
    if ($("#ginosiComment").length > 0){
        $('#ginosiComment').scrollTop($('#ginosiComment')[0].scrollHeight);
    }

    if ($("#housekeepingComment").length > 0) {
        $('#housekeepingComment').scrollTop($('#housekeepingComment')[0].scrollHeight);
    }

	cc.on('null', function() {
        $('.credit-card-list li').removeClass('red-border');
        $('.credit-card').show();

		var selected_card = $('.credit-card-list li.active').eq(0),
            status = $(this).find('.status'),
            control = $(this).find('.status-control'),
			display = $(this).find('.card-display-name'),
			number = $(this).find('.card-number'),
			cvc = $(this).find('.card-cvc'),
			exp = $(this).find('.card-exp'),
			holder = $(this).find('.card-holder-name'),
			type = $(this).find('.card-type'),
            request = $(this).find('.request-details'),
            card_id = 0,
            cc_holder = holder.attr('data-default-holder'),
            cc_cvc = cvc.attr('data-default-cvc'),
            cc_exp = exp.attr('data-default-exp');

        // Email Vault
        if (selected_card.hasClass('email-vault-valid')) {
            exp.show();
            exp.find('span').text(selected_card.attr('data-exp'));
            request.hide();
            cc_holder = selected_card.attr('data-holder');
            cc_cvc = selected_card.attr('data-cvc') ? selected_card.attr('data-cvc') : 'XXX';
            cc_exp = selected_card.attr('data-exp');
        } else {
            request.show();
            $('.request-details').show();
            exp.hide();
        }

        // Status
        status.show();
        control.show();

        if (BUSINESS_MODEL == BUSINESS_MODEL_GINOSI_COLLECT_FROM_PARTNER) {
            $('.cc-partner-changer').show();
            $('.cc-partner-changer select').val(selected_card.attr('data-partner-id'));
        }

        var selected_status = selected_card.find('.status');

        control.trigger('change', [selected_card.find('.status').attr('data-status'), false]);
        status.attr({
            title: selected_status.attr('title') ? selected_status.attr('title') : selected_status.attr('data-original-title'),
            class: selected_status.attr('class')
        });

        if (selected_card.hasClass('fraud-cc')) {
            cc.find('.status-control').hide();
        } else {
            cc.find('.status-control').show();
        }

        status.tooltip('destroy');
        status.tooltip();

        // Set Card id
        if (selected_card.hasClass('fraud-cc')) {
            card_id = 0;
        } else {
            card_id = selected_card.attr('data-card-id');
        }

        $('#cc_id').val(card_id);

        // Bring Card to Hidden Mode
        $(this).attr('data-card-mode', 'hidden');

        // Change CC Id
        $(this).attr('data-card-id', selected_card.attr('data-card-id'));

        // CC Display Name
        display.text(selected_card.find('.card-display-name').text());

        // CC Number
        number.text(selected_card.attr('data-number'));

        // CC CVC
        cvc.find('span').text(cc_cvc);

        // CC Holder Name
        holder.text(cc_holder);

        // CC Expiration Date
        exp.find('span').text(cc_exp);

        // CC Type (icon)
        type.find('img').attr('src', selected_card.find('.card-type img').attr('src'));
	});

	$('.credit-card-list li').click(function(e) {
		e.preventDefault();

		$('.credit-card-list li.active').removeClass('active');
        $('.garbage-credit-card-list li.active').removeClass('active');

		$(this).addClass('active');
		cc.trigger('null');
	});

	cc.find('.status-control').on('change', function(e, val, update) {
		e.preventDefault();

		var selected_status = $(this).find('li[data-value="' + val + '"]'),
			cc_list = $('.credit-card-list'),
			element_status = cc_list.find('li.active').eq(0).find('.status'),
			cc_element_status = cc.find('.status'),
			change_status_url = cc.attr('data-url-status'),
			control = $(this).find('button').eq(0);

		$(this).find('.ellipsis').text(
			selected_status.find('a').text()
		);

		if (update) {
			control.prop('disabled', true);

			$.ajax({
				type: "POST",
				url: change_status_url,
				data: {
                    reservation_id: $('#booking_id').val(),
					cc_id: cc.attr('data-card-id'),
					status: val
				},
				dataType: "json",
				success: function(data) {
					control.prop('disabled', false);
					notification(data);

					if (data.status == 'success') {
						var status = selected_status.attr('data-status'),
							title = selected_status.find('a').text();

						// CC Status
						cc_element_status.attr({
							class: status,
							title: title
						});
						cc_element_status.tooltip('destroy');
						cc_element_status.tooltip();

						// Selected CC Status
						element_status.attr({
							class: status,
							"data-status": val,
							title: title
						});
						element_status.tooltip('destroy');
						element_status.tooltip();

						// Select default card
						if (data.default_card_id !== true) {
							cc_list.find('.default').removeClass('default');
						}

						if (data.default_card_id !== true && data.default_card_id !== false) {
							cc_list.find('li[data-card-id=' + data.default_card_id + ']').addClass('default');
						}
					}
				}
			});
		}
	});

	$('.status-control li').click(function(e) {
		e.preventDefault();

		$('.status-control').trigger('change', [$(this).attr('data-value'), true]);
	});

	$('.request-details').click(function(e) {
		e.preventDefault();

		var request_cc_details_url = cc.attr('data-url-request'),
			requestButton = $(this);

		requestButton.button('loading');

		$.ajax({
			type: "POST",
			url: request_cc_details_url,
			data: {
				cc_id: cc.attr('data-card-id'),
                reservation_id: $('#booking_id').val()
			},
			dataType: "json",
			success: function(data) {
				requestButton.button('reset');

				if (data.status == 'success') {
					cc.attr('data-card-mode', 'full');

					var card = data.data;

					// CC Number
					cc.find('.card-number').text(card.number);

					// CC CVC
					cc.find('.card-cvc span').text(card.cvc);

					// CC Holder Name
					cc.find('.card-holder-name').text(card.holder);

					// CC Expiration Date
					cc.find('.card-exp').show();
					cc.find('.card-exp span').text(card.exp);

					requestButton.hide();
				} else {
					notification(data);
				}
			}
		});
	});
});

function initialCardData() {
    if ($('.credit-card-list').length) {
	    var li = $('.credit-card-list li');

        if (li.length == 1) {
            var defaultCard = $('.credit-card-list').find('li.default');

            if (defaultCard.length) {
                $('.credit-card-list').find('li.default').addClass('active');
            } else {
                $('.credit-card-list').find('li').eq(0).addClass('active');
            }

            cc.trigger('null');
        } else {
            $('.cc-partner-changer').hide();
            $('#cc_id').val(0);
            $('.credit-card').hide();
	        li.removeClass('active').removeClass('red-border');
        }
    }
}

var allButtonHide = function () {
    $('.buttonsCharges').hide();
    $('.buttonsTransactions').hide();
    $('.buttonsTicket').hide();
    $('.attachBtn').hide();
    $('#add-task-btns').hide();
    $('#move-reservation-btn').hide();
    $('#change-date-btn').hide();
    $('#saveNewAttachBtn').hide();
    $('#cancelAttachBtn').hide();
    $('#uploadAttachment').hide();
    $('.change-date-buttons').hide();
    $('#deleteFiles').hide();
};

$('.cancelCharge').click(function(){
   $('#chargeClick').val('click');
   window.location.hash = '#financial_details';
   location.reload();
});

/***************** Charging part ********************/
function hasPendingCharge () {
	return (($('.chargeRemoveRow').length > 0 || $('#charge-form .deleted').length > 0) && $('.charge_tab').is(':visible'));
}

function hasPendingTransaction () {
    return ($('#transaction_type').val() > 0 && $('.transaction_tab').is(':visible'));
}

function addTextAboutPending(type) {
    if (type == 'charge') {
        if (hasPendingCharge()) {
            $('#chargePending').html('Pending...');
        } else {
            $('#chargePending').html('');
        }
    } else {
        if(hasPendingTransaction()) {
            $('#transactionPending').html('Pending...');
        } else {
            $('#transactionPending').html('');
        }
    }
}

//hide tab part
var alltabHide = function () {
    $('.tab-content').find('div').removeClass('active');
    $('.tabs-general').find('li').removeClass('active');
    $('#charge_part').hide();
    $('#change_reservation_tab_part').hide();
    $('#transaction_part').hide();
    $('#new_attachment_part').hide();
};

var chargePart = function () {
    alltabHide();
    $('#charge_part').show();
    $('.charge_tab').show();
    $('.charge_tab').addClass('active');
    allButtonHide();
    $('.buttonsCharges').show();
};

var newAttachPart = function () {
    alltabHide();
    allButtonHide();
    $('#new_attachment_part').show();
    $('.new_attachment_tab').show();
    $('#cancelAttachBtn').show();
    $('#uploadAttachment').show();

    if ($('.attachmentFileName').length) {
        $('#saveNewAttachBtn').show();
        $('#deleteFiles').show();
    }
};

if ($(".charge_tab").length > 0) {
    $(".charge_tab").click(function() {
        chargePart();
    });
}

if ($("#chargeButton").length > 0) {
    $("#chargeButton").click(function() {
        chargePart();
    });
}

if ($("#attachment_tab").length > 0) {
    $("#attachment_tab").click(function() {
        alltabHide();
        allButtonHide();
        $('.attachBtn').show();
    });
}

if ($("#tasks_tab").length > 0) {
    $("#tasks_tab").click(function() {
        alltabHide();
        allButtonHide();
        $('#add-task-btns').show();
    });
}

if ($("#identity_tab").length > 0) {
    $("#identity_tab").click(function() {
        alltabHide();
        allButtonHide();
    });
}

$('#attachBtn').click(function() {
    newAttachPart();

});

if ($(".new_attachment_tab").length > 0) {
    $(".new_attachment_tab").click(function() {
        newAttachPart();
    });
}

$('.acc_amount, #transaction_acc_amount').blur(function() {
    if ($(this).valid()) {
        var val = $(this).val();

        if (val != '') {
            val = parseFloat(val);
            val = precise_round(val);

            $(this).val(val);
        }
    }
});

if ($("#booking_statuses").length > 0) {
    $("#booking_statuses").change(function() {
    	if (this.value == 14) {
            var data = {};
            data.status = 'warning';
            data.msg ='Cancelation email wouldn\'t be sent to customer. <br>';
            notification(data);
        }
    });
}

function changeBookedStatus(checkStatus) {
	var accPrice,
		customerPrice;

    if (checkStatus == 1) {
        accPrice = $('#accPrice').val();
	    customerPrice = $('#customerPrice').val();
    } else {
        accPrice = $('#penaltyAccPrice').val();
	    customerPrice = $('#penaltyCustomerPrice').val();
    }

    $(".acc_amount").each(function() {
       var idn = this.id,
	       num = idn.replace("accommodation_amount_", '');

       if (parseInt(num) > 0) {
           var addonsValue = $('#addons_value_' + num).val();

           if ($(this).hasClass('charge_percent')) {
                accPrice = parseFloat(accPrice)*parseFloat(addonsValue)/100;
                accPrice = precise_round(parseFloat(accPrice));
                customerPrice = parseFloat(customerPrice)*parseFloat(addonsValue)/100;
                customerPrice = precise_round(parseFloat(customerPrice));
                $(this).val(accPrice);

                $('#customer_amount_span_' + num).html(customerPrice);
                $('#customer_amount_' + num).val(customerPrice);
           }
       } else {
            $(this).val(accPrice);
            $('#customer_amount_span_' + num).html(customerPrice);
            $('#customer_amount_' + num).val(customerPrice);
       }

    });

    calculateSum();
}

if ($("#transaction_acc_amount").length > 0) {
    $("#transaction_acc_amount").keyup(function() {
        var accAmaount = this.value,
	        chargeCurrencyRate = $('#transaction_money_account_currency_rate').val(),
	        chargeAmount = parseFloat(chargeCurrencyRate) / parseFloat(accCurrencyRate) * parseFloat(accAmaount),
	        NOT_REFUND = (parseInt($('#transaction_type').val()) != TRANSACTION_REFUND),
	        NOT_CHARGEBACK = ($.inArray(parseInt($('#transaction_type').val()), CHARGEBACK_ARRAY) === -1);

	    chargeAmount = parseFloat(chargeAmount);
	    chargeAmount = precise_round(parseFloat(chargeAmount));

	    if (isNaN(chargeAmount)) {
		    chargeAmount = 0.00;
	    }

        if (NOT_REFUND && NOT_CHARGEBACK) {
            $('#transaction_charge_amount_span').html(chargeAmount);
            $('#transaction_charge_amount').val(chargeAmount);
        }
    });
}

if ($("#personal_account_id").length > 0) {
	$("#personal_account_id").change(function() {
		var to_currency = $("#personal_account_id option:selected").attr('data-bank-currency');

		$('#transaction_charge_currency').text(to_currency);

		for (var v in GLOBAL_CURRENCY) {
			var item = GLOBAL_CURRENCY[v];

			if (item.code == to_currency) {
				$('#transaction_money_account_currency_rate').val(item.value);
				$('#transaction_money_account_currency').val(to_currency);

				break;
			}
		}

		$('#transaction_acc_amount').trigger('keyup');
	});
}

if ($("#transaction_charge_amount").length > 0) {
	$("#transaction_charge_amount").blur(function() {
		$(this).val(
			precise_round($(this).val())
		);
	});
}

if ($("#transaction_psp").length > 0) {
    $("#transaction_psp").change(function() {
        var val = $(this).val(),
	        isBatch = parseInt($(this).find(':selected').attr('data-is-batch')),
	        charge_amount = 0,
	        accAmaount = 0,
	        to_rate,
            money_account_name = '',
	        to_currency = '',
	        type = $('#transaction_type').val();

        $('#transaction_money_account_id').val(0);
	    $('#transaction_money_account_currency_rate').val(0);
        $('#transaction_charge_amount_span').html('');
        $('#transaction_charge_amount').val(0);

        if (type == TRANSACTION_BANK_DEPOSIT) {
            if (val > 0) {
                var bankFromPSP = $("#transaction_psp option:selected").attr('data-bank');
                $("#money_account_deposit_id").val(bankFromPSP).trigger('change');
                $("#money_account_deposit_id option").hide();
                $("#money_account_deposit_id option[value=" + bankFromPSP + "]").show();
            } else {
                $("#money_account_deposit_id").val(0).trigger('change');
                $("#money_account_deposit_id option").show();
            }

            rrnAuthRequired('transaction_psp');
            return;
        }

        $('#transaction_bank_name_div').show();

        if (val > 0) {
            var selectedOption = $("#transaction_psp option:selected"),
	            money_account_id = selectedOption.attr('data-bank');

            to_currency = selectedOption.attr('data-bank-currency');
            money_account_name = selectedOption.attr('data-bank-name');

            $('#transaction_bank').html(money_account_name);
            $('#transaction_money_account_id').val(money_account_id);

	        if (isBatch) {
		        $('.bank-part').show();
		        $('#transaction_bank').hide();
		        $('#transaction_charge_amount').prop('type', 'text');
		        $('#transaction_charge_amount').prop('readonly', true);
	        } else {
		        $('.bank-part').show();
		        $('#transaction_bank').show();
		        $('#transaction_charge_amount').prop('type', 'text');
		        $('#transaction_charge_amount').prop('readonly', false);
	        };

            for (var v in GLOBAL_CURRENCY) {
               var item = GLOBAL_CURRENCY[v];

               if (item.code == to_currency) {
                   to_rate = item.value;
                   $('#transaction_money_account_currency_rate').val(to_rate);
                   break;
               }
            }

            if (type == TRANSACTION_COLLECT || type == TRANSACTION_REFUND || type == TRANSACTION_VALIDATION) {
                var amount = parseFloat($('#transaction_acc_amount').val());

                amount = (amount > 0) ? amount : 0;
                charge_amount = parseFloat(to_rate) / parseFloat(accCurrencyRate) * amount;
                charge_amount = precise_round(parseFloat(charge_amount));
            } else {
                var chargeAmount = $('#transaction_charge_amount').val();

                accAmaount = parseFloat(accCurrencyRate) / parseFloat(to_rate) * parseFloat(chargeAmount);
                accAmaount = precise_round(parseFloat(accAmaount));
            }
        } else {
            $('.bank-part').hide();
            $('#transaction_bank').html('');
        }

        if (type == TRANSACTION_COLLECT || type == TRANSACTION_REFUND || type == TRANSACTION_VALIDATION) {
            if (!isNaN(charge_amount)) {
                $('#transaction_charge_amount_span').html(charge_amount);
                $('#transaction_charge_amount').val(charge_amount);
            }

        } else {
            $('#transaction_acc_amount_span').html(accAmaount);
            $('#transaction_acc_amount').val(accAmaount);
        }

        $('#transaction_charge_currency').html(to_currency);
        $('#transaction_money_account_currency').val(to_currency);

        rrnAuthRequired('transaction_psp');
	});
}

if ($("#transaction_chargeback_bank").length > 0) {
    $("#transaction_chargeback_bank").change(function() {

        var money_account_id = $(this).val(),
	        accAmaount = 0,
	        to_rate = 0,
	        to_currency = '';

        $('#transaction_money_account_currency_rate').val(0);

        if (money_account_id > 0) {
            $('.bank-part').show();
            to_currency =  $( "#transaction_chargeback_bank option:selected" ).attr('data-bank-currency');

            for (var v in GLOBAL_CURRENCY) {
               var item = GLOBAL_CURRENCY[v];

               if (item.code == to_currency) {
                   to_rate = item.value;
                   $('#transaction_money_account_currency_rate').val(to_rate);

                   break;
               }
            }

            var chargeAmunt = $('#transaction_charge_amount').val();
            accAmaount = parseFloat(accCurrencyRate)/parseFloat(to_rate) * parseFloat(chargeAmunt);
            accAmaount = precise_round(parseFloat(accAmaount));
        } else {
            $('.bank-part').hide();
        }

        $('#transaction_acc_amount_span').html(accAmaount);
        $('#transaction_acc_amount').val(accAmaount);

        $('#transaction_charge_currency').html(to_currency);
        $('#transaction_money_account_currency').val(to_currency);
    });
}

if ($("#transaction_status").length > 0) {
    $( "#transaction_status" ).change(function() {
       rrnAuthRequired('transaction_psp');
    });
}

if ($("#transaction_charge_amount").length > 0) {
    $("#transaction_charge_amount").keyup(function() {
        var chargeAmunt = this.value,
	        chargeCurrencyRate = $('#transaction_money_account_currency_rate').val(),
	        type = $('#transaction_type').val(),
	        accAmaount = 0;

        if (chargeCurrencyRate > 0) {
            accAmaount = parseFloat(accCurrencyRate)/parseFloat(chargeCurrencyRate)*parseFloat(chargeAmunt);
            accAmaount = precise_round(parseFloat(accAmaount));
        }

        if ($.inArray(parseInt(type), [TRANSACTION_COLLECT, TRANSACTION_BANK_DEPOSIT, TRANSACTION_CASH, TRANSACTION_CASH_REFUND]) === -1) {
            $('#transaction_acc_amount_span').html(accAmaount);
            $('#transaction_acc_amount').val(accAmaount);
        }
    });
}

function rrnAuthRequired(select_id) {
    var rrn         = 'transaction_rrn_div',
	    auth        = 'transaction_auth_div',
	    status      = 'transaction_status',
	    errorCode   = 'transaction_error_code_div',
	    selectedPSP = $('#' + select_id).val();

    if (selectedPSP != 0) {
        var selectedOption = $('#' + select_id + ' option:selected'),
	        rrn_data  = selectedOption.attr('data-rrn'),
	        auth_data = selectedOption.attr('data-auth'),
	        error_code_data = selectedOption.attr('data-error');

        if (parseInt($('#' + status).val()) == 2) {
            $('#' + rrn).hide();
            $('#' + auth).hide();

            if (parseInt(error_code_data) == 1) {
                $('#' + errorCode).show();
            } else {
                $('#' + errorCode).hide();
            }
        } else if (parseInt($('#' + status).val()) == 1) {
            $('#' + errorCode).hide();

            if (parseInt(rrn_data) == 1) {
                $('#' + rrn).show();
            } else {
                $('#' + rrn).hide();
            }

            if (parseInt(auth_data) == 1) {
                $('#' + auth).show();
            } else {
                $('#' + auth).hide();
            }
        } else {
            $('#' + rrn).hide();
            $('#' + auth).hide();
            $('#' + errorCode).hide();
        }

        $("#" + status).closest('.form-group').show();
    } else {
        $('#' + rrn).hide();
        $('#' + auth).hide();
        $('#' + errorCode).hide();
    }
}

if ($("#transaction_type").length > 0) {
    $("#transaction_type").change(function() {
        //call pending text
        setTimeout(function(){
            addTextAboutPending('transaction');
        }, 10);

        // initialization
        $('#transactionBank').hide();
        $('#transaction_auth_div').hide();
        $('#transaction_rrn_div').hide();
        $('#transaction_psp').val(0);
        $('#transaction_status').val(0);
        $('#transaction_error_code_div').hide();
        $('#transaction_charge_currency').html('');
        $('#transaction_bank').html('');
        $('#transaction_bank_name_div').hide();
        $('#transaction_acc_amount_span').html('');
        $('#transaction_acc_amount').val(0);
        $('#transaction_money_account_id').val(0);
        $('#transaction_chargeback_bank').val(0);
        $('#transaction_rrn').val('');
        $('#transaction_auth_code').val('');
        $('#transaction_money_account_currency_rate').val(0);
        $('#transaction_acc_amount').prop('type', 'text');
	    $('#transaction_charge_amount').val(0);
        $('#transaction_charge_amount').prop('type', 'hidden');
	    $('#transaction_charge_amount').prop('readonly', false);
	    $('#transaction_charge_amount_span').html('');
        $('#transaction_charge_amount_span').show();
        $('#transaction_acc_amount_span').hide();
        $('#transactionCache').hide();
        $('#personalAccount').hide();
        $('#transactionStatus').show();
        $("#money_account_deposit_id option").show();
        $('#money_direction_received_div').hide();
        $('.cards-part').hide();
        $('.bank-part').hide();
        $('#cc_id').val(0);


        var type = this.value,
	        IS_CACHE = (type == TRANSACTION_CASH),
	        IS_CACHE_REFUND = (type == TRANSACTION_CASH_REFUND),
	        IS_SALARY = (type == TRANSACTION_DEDUCTED_SALARY),
	        IS_VALIDATION = (type == TRANSACTION_VALIDATION),
	        IS_COLLECT = (type == TRANSACTION_COLLECT),
	        IS_REFUND = (type == TRANSACTION_REFUND),
	        IS_DEPOSIT = (type == TRANSACTION_BANK_DEPOSIT);

        if ($.inArray(parseInt(type), CHARGEBACK_ARRAY) !== -1) {
            $('#transactionBank').show();
        }

        if (IS_COLLECT || IS_REFUND ||  IS_VALIDATION) {
            $('#transactionPSP').show();
            $('.cards-part').show();
            initialCardData();
        } else {
            $('#transactionPSP').hide();
        }

        if (IS_COLLECT) {
	        $('#transaction_charge_amount').prop('type', 'text');
	        $('#transaction_charge_amount').addClass('bg-yellow');
	        $('#transaction_charge_amount_span').hide();

        	var ginosi_collect_debt_apartment_currency = parseFloat($('#ginosi_collect_debt_apartment_currency').val()),
		        partner_collect_debt_customer_currency = parseFloat($('#partner_collect_debt_customer_currency').val()),
		        partner_collect_debt_apartment_currency = parseFloat($('#partner_collect_debt_apartment_currency').val());

            if (ginosi_collect_debt_apartment_currency < 0) {
            	ginosi_collect_debt_apartment_currency = ginosi_collect_debt_apartment_currency * (-1);
                $('#transaction_acc_amount').val(ginosi_collect_debt_apartment_currency);
            }
        }

        if ($.inArray(parseInt(type), CHARGEBACK_ARRAY) !== -1 || IS_REFUND) {
            $('#transaction_charge_amount').prop('type', 'text');
            $('#transaction_charge_amount_span').hide();
            $('#transaction_acc_amount').addClass('bg-yellow');

            if ($.inArray(parseInt(type), CHARGEBACK_ARRAY) !== -1) {
                $('#transaction_status').val(TRANSACTION_STATUS_PENDING);
            }
        } else {
            $('#transaction_acc_amount').removeClass('bg-yellow');
        }

	    if (IS_CACHE || IS_CACHE_REFUND) {
		    $('#personalAccount').show();
		    $('#transactionCache').hide();
		    $('#transactionStatus').hide();

		    $('.bank-part').show();
		    $('#transaction_charge_amount').prop('type', 'text');
		    $('#transaction_charge_amount_span').hide();
		    $('#transaction_charge_amount').addClass('bg-yellow');
	    } else if (IS_SALARY) {
            $('#transactionCache').show();
		    $('#personalAccount').hide();
        } else {
            $('#transactionCache').hide();
            $('#personalAccount').hide();
        }

        if (IS_VALIDATION) {
            var accPriceValidate = parseFloat($('#accPriceValidate').val());
            $('#transaction_acc_amount').val(accPriceValidate);
            $('#transaction_acc_amount').prop('type', 'text');

            $('#transaction_acc_amount').val(accPriceValidate);
            $('#transaction_acc_amount_span').html(accPriceValidate);
        }

        if (IS_DEPOSIT) {
            $('#bankDepositList').show();
	        $('#transaction_charge_amount_span').hide();
            $('#transaction_charge_amount').prop('type', 'text').addClass('bg-yellow');
            $('#transaction_psp').removeClass('notZero');
            $('#money_account_deposit_id').val(0);
        } else {
            $('#transaction_psp').addClass('notZero');
	        $('#bankDepositList').hide();

	        if (!(IS_CACHE || IS_CACHE_REFUND || IS_COLLECT)) {
		        $('#transaction_charge_amount').removeClass('bg-yellow');
	        }
        }

        if (IS_DEPOSIT || IS_SALARY) {
            $('#transaction_status').val(TRANSACTION_STATUS_APPROVED);
        }

        if (IS_SALARY) {
            $('#transaction_deposit_div').hide();
        } else {
            $('#transaction_deposit_div').show();
        }

        // Change transaction button text
        var buttonText = 'Create Transaction';

        if ($(this).val() > 0) {
            buttonText = $('#transaction_type  option:selected').text();
        }

        $('#transactionProcess').text(buttonText);
	});
}

if ($("#money_account_deposit_id").length > 0) {
    $("#money_account_deposit_id").change(function() {
        $('#transaction_money_account_currency_rate').val(0);
        $('#transaction_charge_amount_span').html('');
        $('#transaction_charge_amount').val(0);

        var to_rate = 0,
            charge_amount,
	        to_currency =  $("#money_account_deposit_id option:selected").attr('data-bank-currency'),
	        amount = parseFloat($('#transaction_acc_amount').val());

        for (var v in GLOBAL_CURRENCY) {
            var item = GLOBAL_CURRENCY[v];

            if (item.code == to_currency) {
                to_rate = item.value;
                $('#transaction_money_account_currency_rate').val(to_rate);

                break;
            }
        }

        amount = (amount > 0) ? amount : 0;
        charge_amount = parseFloat(to_rate)/parseFloat(accCurrencyRate)*amount;
        charge_amount = precise_round(parseFloat(charge_amount));

        $('#transaction_charge_amount_span').html(charge_amount);
        $('#transaction_charge_amount').val(charge_amount);

        $('#transaction_charge_currency').html(to_currency);
        $('#transaction_money_account_currency').val(to_currency);

        if (this.value > 0) {
            $('.bank-part').show();
        } else {
            $('.bank-part').hide();
        }
    });
}

$('.cancelTransaction').click(function(){
   $('#transactionClick').val('click');
   window.location.hash = '#financial_details';
   location.reload();
});

$('.cancelAttachBtn').click(function(){
   window.location.hash = '#attachment';
   location.reload();
});

if ($(".exist-transaction-status").length) {
    $('.exist-transaction-status').click(function() {
        var transactionStatus = $(this).attr('data-status'),
            transactionId = $(this).attr('data-id'),
            transactionType = $(this).closest('td').find('.exist-transaction-type-button').attr('data-type'),
            reservationId = $('#booking_id').val();

        $(this).closest('.btn-group').find('button').button('loading');

        $.ajax({
            type: "POST",
            url: GLOBAL_CHANGE_TRANSACTION_STATUS,
            data: {
                transaction_status:transactionStatus,
                transaction_id:transactionId,
                transaction_type:transactionType,
                reservation_id:reservationId
            },
            dataType: "json",
            success: function(data) {
                location.reload();
            }
        });
    });
}

if ($(".exist-transaction-type").length) {
    $('.exist-transaction-type').click(function() {
        var transactionType = $(this).attr('data-type');
        $(this).closest('div').find('.exist-transaction-type-button').html($(this).text()).attr('data-type', transactionType);
        $(this).closest('td').find('.exist-transaction-status-button').prop('disabled', false);
    });
}

var transactionPart = function() {
    alltabHide();
    $('#transaction_part').show();
    $('.transaction_tab').show();
    $('.transaction_tab').addClass('active');
    allButtonHide();
    $('.buttonsTransactions').show();
};

var collectionButton = function() {
    $("#transaction_type option").show();
    $('#transactionProcess').html('Create Transaction');

    $('#balancViewTransect').show();
    transactionPart();
};


if ($(".transaction_tab").length > 0) {
    $( ".transaction_tab" ).click(function() {
        transactionPart();
    });
}

if ($("#collectionButton").length > 0) {
    $("#collectionButton").click(function() {
        collectionButton();
        $('#transaction_type').val(GLOBAL_DEFAULT_TYPE).trigger('change');
    });
}

if ($("#reservation_action_validate_card").length > 0) {
    $("#reservation_action_validate_card").click(function() {
        $('#balancViewTransect').hide();
        $('#transactionProcess').html('Save');
	    $("#transaction_type option").hide();
        $("#transaction_type option[value='7']").show();
        $('#transaction_type').val(TRANSACTION_VALIDATION).trigger('change');

        transactionPart();
    });
}

if ($("#transactionProcess").length > 0) {
    $("#transactionProcess").click(function() {
        var btn = $('#transactionProcess'),
	        validate = $('#transaction-form').validate();

        btn.prop('disabled', true);
        btn.button('loading');

        if ($('#cc_id').val() == 0 && ($('#transaction_type').val() == TRANSACTION_COLLECT || $('#transaction_type').val() == TRANSACTION_REFUND)) {
            $('.credit-card-list li').addClass('red-border');
            btn.prop('disabled', false);
            btn.button('reset');
            return;
        }

	    if ($('#transaction-form').valid()) {
	        var obj = $('#transaction-form').serializeArray();

	        obj.push({name: 'reservation_id', value: $('#booking_id').val()});
	        obj.push({name: 'res_number', value: $('#booking_res_number').val()});
	        obj.push({name: 'acc_currency_rate', value: $('#acc_currency_rate').val()});
	        obj.push({name: 'customer_currency_rate', value: $('#customer_currency_rate').val()});
	        obj.push({name: 'customerCurrency', value: $('#customerCurrency').val()});
	        obj.push({name: 'accommodationCurrency', value: $('#accommodationCurrency').val()});
	        obj.push({name: 'booking_statuses', value: $('#booking_statuses').val()});
	        obj.push({name: 'acc_currency', value: $('#accommodationCurrency').val()});
	        obj.push({name: 'accId', value: $('#accId').val()});
			obj.push({name: 'cardId', value: $('#cc_id').val()});

	        $.ajax({
	            type: "POST",
	            url: GLOBAL_TRANSACTION,
	            data: obj,
	            dataType: "json",
	            success: function(data) {
                    $('#transactionClick').val('click');
                    window.location.hash = '#financial_details';
                    location.reload();
	            }
	        });
	    } else {
	        validate.focusInvalid();
            btn.prop('disabled', false);
	        btn.button('reset');
	    }
    });
}

if ($('#ginosi-or-partner').length > 0) {
    $('#ginosi-or-partner').change(function() {
        var partner_id = $(this).val(),
	        card_id = $('.credit-card').attr('data-card-id');

        $('#transactionProcess').attr('disabled', true);

        $.ajax({
            type: "POST",
            url: GLOBAL_CHANGE_PARTNER_ID,
            data: {
	            partner_id: partner_id,
	            card_id: card_id
            },
            dataType: "json",
            success: function(data) {
                $('#transactionProcess').attr('disabled', false);
                $('.credit-card-list li.active').eq(0).attr('data-partner-id', partner_id);
                notification(data);
            }
        });
    })
}

if ($(".parent_container_charge").length > 0) {
    $(".parent_container_charge").click(function() {
		var parent = $(this).closest('.parent_container_charge'),
			slide = parent.find('.slides_container_charge'),
	        chevron = parent.find('.row-arrow');

        if (slide.is(":hidden")) {
            slide.slideDown('fast');
            chevron
				.removeClass('icon-chevron-down')
				.addClass('icon-chevron-up');
        } else {
            slide.slideUp('fast');
            chevron
				.removeClass('icon-chevron-up')
				.addClass('icon-chevron-down');
        }
    });
}


if ($(".chargedView").length) {
    $(".chargedView").click(function() {
        var slide = $(this).next();

        if (slide.is(":hidden")) {
            slide.show();
        } else {
            slide.hide();
        }
    });
}

if ($(".revers-charges").length) {
    $(".revers-charges").click(function() {
        if ($('.deleted').is(":hidden")) {
            $('.deleted').show();
        } else {
            $('.deleted').hide();
            $('.deleted').next().hide();
        }
    });
}

if ($(".total-charges-view").length) {
    $(".total-charges-view").click(function() {
        if ($('.total-charges-item').is(":hidden")) {
            $('.total-charges-item').show();
        } else {
            $('.total-charges-item').hide();
            $('.total-charges-item').next().hide();

        }
    });
}

///////BLACK LIST PART
if ($("#fraudButton").length > 0) {
    $("#fraudButton").click(function(e) {
        hasPendingProccess(e);

        $('#fraudModal').modal('show');
    });
}


function blackList(num) {
    $('#fraudModal .fraud-cancel').hide();

    var btn = $('#fraudModal a.fraud-add'),
	    reservation_id = $('#booking_id').val();

	btn.button('loading');

    $.ajax({
        type: "POST",
        url: GLOBAL_BLACK_LIST,
        data: {reservation_id:reservation_id, num:num},
        dataType: "json",
        success: function(data) {
            if (data.status == 'success') {
                location.reload();
            } else {
                $('#fraudModal .fraud-cancel').show();
                btn.button('reset');
                notification(data);
            }
        }
    });
}

if ($(".logs-tab").length > 0 ) {
    $(".logs-tab").click(function() {
        logsPart();
    });
}

var logsPart = function() {
    alltabHide();

    $('#logs').show();
    $('.logs-tab').addClass('active');
};

$("#pin-btn").click(function() {
    var $self = $(this),
	    currentStatus = $(this).hasClass('active') ? 1 : 0,
	    userId = $(this).data("user_id"),
	    resNum = $(this).data("res_num");

    $.ajax({
        url: GLOBAL_PIN_RESERVATION,
        type: "POST",
        data: {
            pin: currentStatus,
            userId: userId,
            resNum: resNum
        },
        cache: false,
        dataType: "json",
        success: function(data) {
            if (data.status == 'success') {
                $self
                    .toggleClass('active')
                    .toggleClass('btn-default')
                    .toggleClass('btn-primary');
            }

	        $self.blur();

            notification(data);
        }
    });
});

$("#lock-btn").click(function() {
	var $self = $(this),
		lock = $(this).hasClass('active') ? 0: 1,
		id = $('#booking_id').val();

	$.ajax({
		url: GLOBAL_LOCK_RESERVATION,
		type: "POST",
		data: {
			lock: lock,
			id: id
		},

		cache: false,
		dataType: "json",
		success: function(data) {
			if (data.status == 'success') {
				$self
					.toggleClass('active')
					.toggleClass('btn-default')
					.toggleClass('btn-danger');
			}

			$self.blur();
			notification(data);
		}
	});
});

$('.receipt-body').on('keyup keypress', '#custom_email', function() {
    if ($('#custom_email').val().length) {
        var emailReg = new RegExp(/^([\w-\.]+)@((?:[\w]+\.)+)([a-zA-Z]{2,4})$/i);
        var valid = emailReg.test($('#custom_email').val());

        if (!valid) {
            $('#custom_email').css('border-color', '#a94442');
            $('#sendMailReceipt').attr('disabled', true);
        } else {
            $('#sendMailReceipt').attr('disabled', false);
            $('#custom_email').removeAttr('style');
        }
    } else {
        $('#sendMailReceipt').attr('disabled', false);
        $('#custom_email').removeAttr('style');
    }
});

if ($("#reservation_action_generate_receipt").length) {
    $('#reservation_action_generate_receipt').click(function() {
        var btn = $('#reservation_action_generate_receipt');
            btn.button('loading');

        $.ajax({
            url: GLOBAL_GET_RECEIPT,
            type: "POST",
            data: {
                reservation_id: $('#booking_id').val()
            },
            cache: false,
            dataType: "json",
            success: function(data) {
                if (data.status == 'success') {
                    $('#receiptModal .modal-body').html(data.result);
                    $('#receiptModal').modal();
                } else {
                    notification(data);
                }

                setTimeout(function() {
                    btn.button('reset');
                }, 500);
            }
        });
    });
}

var sendReceiptEmail = function() {
    var btn = $('#sendMailReceipt');
    btn.button('loading');

    $.ajax({
        url: GLOBAL_SEND_RECEIPT,
        type: "POST",
        data: {
            reservation_id: $('#booking_id').val(),
            custom_email: $('#selected-email').val()
        },
        cache: false,
        dataType: "json",
        success: function(data) {
            notification(data);
            btn.button('reset');
            $('#receiptModal').modal('hide');
        }
    });
};

if ($("#sendMailReceipt").length) {
    $('#sendMailReceipt').click(function() {
        if (GLOBAL_FRAUD_DETECT >= 100) {
            $('#receiptBlackListModal').modal();
            $('#receiptModal').modal('hide');
        } else {
            sendReceiptEmail();
        }
    });
}

if ($("#printReceipt").length) {
    $('#printReceipt').click(function() {
        $('.receipt-modal').css('width', '700px');
        window.print();
        $('.receipt-modal').css('width', 'auto');
    });
}

if ($("#send_receipt_black_list").length) {
    $('#send_receipt_black_list').click(function() {
        $('#receiptBlackListModal').modal('hide');
        $('#receiptModal').modal();
        sendReceiptEmail();
    });
}

if ($("#cancel_receipt_black_list").length) {
    $('#cancel_receipt_black_list').click(function() {
        $('#receiptBlackListModal').modal('hide');
        $('#receiptModal').modal();
    });
}

///////// Change Date ///////////
var changeDatePart = function() {
    alltabHide();
    $('#change_reservation_tab_part').show();
    $('.change_reservation_date_tab').show();
    $('.change_reservation_date_tab').addClass('active');
    allButtonHide();
    $('.change-date-buttons').show();
};

if ($(".change_reservation_date_tab").length > 0) {
    $(".change_reservation_date_tab").click(function() {
        changeDatePart();
    });
}

if ($("#change-date-btn").length > 0) {
    $("#change-date-btn").click(function() {
        changeDatePart();
    });
}

function changeDateForInfo () {
    var btn = $('#confirm-change-date-btn');
    btn.button('loading');

    $.ajax({
        url: GLOBAL_CHANGE_DATE,
        type: "POST",
        data: {
            reservation_id: $('#booking_id').val(),
            dateFrom: $('#res-date-from').val(),
            dateTo: $('#res-date-to').val(),
            is_get_info: true
        },
        cache: false,
        dataType: "json",
        success: function(data) {
            if (data.status == 'success') {
                $('#change-date-info').html(data.data);
            } else {
                notification(data);
            }

            setTimeout(function(){
                btn.button('reset');
            }, 200);
        }
    });
}

if ($("#confirm-change-date-btn").length) {
    $('#confirm-change-date-btn').click(function() {
        var btn = $('#confirm-change-date-btn');
        btn.button('loading');

        $.ajax({
            url: GLOBAL_CHANGE_DATE,
            type: "POST",
            data: {
                reservation_id: $('#booking_id').val(),
                dateFrom: $('#res-date-from').val(),
                dateTo: $('#res-date-to').val()
            },
            cache: false,
            dataType: "json",
            success: function(data) {
                if (data.status == 'success') {
                    location.reload();
                } else {
                    notification(data);
                }

                setTimeout(function(){
                    btn.button('reset');
                }, 200);
            }
        });
    });
}

$('.cancelChangeButton').click(function() {
    window.location.hash = '#booking_details';
    location.reload();
});

function resolveComment(node) {
    var elem = $(node),
        id = elem.attr('id').split('-')[2],
        requestData = new FormData();

    requestData.append('id', id);
    elem.button('loading');

    $.ajax({
        url: GLOBAL_RESOLVE_COMMENT,
        type: "POST",
        data: requestData,
        contentType: false,
        processData: false,
        cache: false,
        success: function(data) {
            if (data.status == 'success') {
                $(elem).hide('fast');

                setTimeout(function() {
                    $(elem).closest('td').append('<span class="label label-success pull-right"><i class="glyphicon glyphicon-ok-sign"></i> Resolved</span>');
                }, 500);
            } else {
                elem.button('reset');
            }

            notification(data);
        }
    });
}
