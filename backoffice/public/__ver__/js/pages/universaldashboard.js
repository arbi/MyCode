$(function() {
    var $body = $('body');

	// Widgets
	var ud_widget_heading = $('.ud-block .panel-heading'),
		ud_init = function(event) {
		var legend = $(this),
			parent = $(this).closest('.ud-block'),
			ud_widget_id = $(this).attr('data-widget-id'),
			wrapper = parent.find('.dataTables_wrapper'),
			chevron = parent.find('.row-arrow'),
			count = parent.find('.ud-count'),
			cookie_value = getCookie(ud_widget_id);
		if (cookie_value != null) {
			if (event.type != 'click') {
				if (cookie_value == 1) {
					legend.addClass('data-hidden');
				} else {
					legend.removeClass('data-hidden');
				}
			}
		} else {
			setCookie(ud_widget_id, 0);
		}

		if (legend.hasClass('data-hidden')) {
			if (event.type == 'click') {
				setCookie(ud_widget_id, 1);
			}

			legend.removeClass('data-hidden');
			wrapper.slideDown('fast');
			chevron
				.removeClass('glyphicon-chevron-down')
				.addClass('glyphicon-chevron-up');
		} else {
			if (event.type == 'click') {
				setCookie(ud_widget_id, 0);
			}

			legend.addClass('data-hidden');
			wrapper.slideUp('fast');
			chevron
				.removeClass('glyphicon-chevron-up')
				.addClass('glyphicon-chevron-down');
		}

		if (parent.css('display') == 'none') {
			var duration = 0.2;

			$('.ud-block').each(function() {
				var self = $(this);
				setTimeout(function() {
					self.fadeIn('fast');

				}, (duration++) * 20);
			});
		}

	};

	ud_widget_heading.each(ud_init);
	ud_widget_heading.click(ud_init);

    $('[data-toggle="tooltip"]').tooltip();

    $body.delegate('.success-notification', 'click', function(e) {
        e.preventDefault();

        var self = $(this);
        self.button('loading');

        $.get($(this).attr('data-target'), function(data) {
            notification(data);

            if (data.status == 'success') {
                $(self).hide('fast', function() {
                    self.closest('div').append('<span class="label label-success"><i class="glyphicon glyphicon-ok-sign"></i> Done</span>');
                });
            }

            self.button('reset');
        });
    });

    $body.delegate('.cancel-pending-transfer', 'click', function(e) {
        e.preventDefault();

        var self = $(this);
        self.button('loading');

	    $.ajax({
		    url: self.attr('href'),
		    type: 'POST',
		    success: function(data) {
			    if (data.status == 'success') {
				    $(self).hide('fast', function() {
					    self.closest('td').append('<span class="label label-success"><i class="glyphicon glyphicon-ok-sign"></i> Cancelled</span>');
				    });
			    } else {
				    self.button('reset');
			    }
		    }
	    });
    });

    $body.delegate('.btn-task-verify', 'click', function(e) {
        e.preventDefault();

        var self = $(this);
        self.button('verifying');

        $.ajax({
            url: '/task/ajaxsave',
            type: "POST",
            data: {
                edit_id: $(this).attr('data-task-id'),
                task_status: 6
            },
            success: function(data) {
                if (data.status == 'success') {
                    $(self).hide('fast', function() {
                        self.closest('td').append('<span class="label label-success"><i class="glyphicon glyphicon-ok-sign"></i> Verified </span>');
                    });
                } else {
                    self.button('reset');
                }

                notification(data);
            }
        });
    });

    $body.delegate('.resolve-reservation-issue', 'click', function(e) {
        e.preventDefault();

        var self = $(this);

        issue_id = self.attr('data-target-id');

        requestData = new FormData(),
        requestData.append('id', issue_id);

        self.button('loading');

        $.ajax({
            url: self.attr('data-target'),
            type: "POST",
            data: requestData,
            contentType: false,
            processData: false,
            cache: false,
            success: function(data) {
                if (data.status == 'success') {
                	$(self).hide('fast', function() {
                            self.closest('div').append('<span class="label label-success"><i class="glyphicon glyphicon-ok-sign"></i> Resolved </span>');
                        });
                } else {
	                $(self).hide('fast', function() {
                            self.closest('div').append('<span class="label label-danger"><i class="glyphicon glyphicon-ok-sign"></i> Not Resolved </span>');
                        });
                }

                notification(data);
            }
        });

        self.button('reset');
    });

    $body.delegate('.asset-resolve', 'click', function(e) {
        e.preventDefault();

        var self = $(this);
        self.button('loading');

        $.get($(this).attr('data-target'), function(data) {
            notification(data);

            if (data.status == 'success') {
                $(self).hide('fast', function() {
                    self.closest('td').html('<span class="label label-success"><i class="glyphicon glyphicon-ok-sign"></i> Resolved </span>');
                });
            }

            self.button('reset');
        });
    });

    $body.delegate('.asset-received', 'click', function(e) {
        e.preventDefault();

        var self = $(this);
        var orderId = self.closest('tr').find('.asset-matching-orders').val();
        var quantity = self.closest('tr').find('.asset-quantity').val();

        var data = {
            orderId: orderId,
            quantity: quantity
        };

        self.button('loading');

        $.ajax({
            url: self.attr('data-target'),
            type: "POST",
            data: data,
            success: function(data) {
                if (data.status == 'success') {
                    $(self).hide('fast', function() {
                        self.closest('td').html('<span class="label label-success"><i class="glyphicon glyphicon-ok-sign"></i> Received </span>');
                    });
                }

                notification(data);
            }
        });

        self.button('reset');
    });

    $body.delegate('.complete-item', 'click', function(e) {
        e.preventDefault();

        var self = $(this);

        self.button('loading');

        $.ajax({
            url: self.attr('href'),
            type: 'POST',
            success: function(data) {
                if (data.status == 'success') {
                    $(self).hide('fast', function() {
                        self.closest('td').append('<span class="label label-success"><i class="glyphicon glyphicon-ok-sign"></i> Completed</span>');
                    });
                }

                notification(data);
            }
        });

        self.button('reset');
    });
    
    $body.delegate('.archive-category', 'click', function(e) {
        e.preventDefault();

        var self = $(this);
        self.button('loading');

        $.get($(this).attr('data-target'), function(data) {
            notification(data);

            if (data.status == 'success') {
                $(self).hide('fast', function() {
                    self.closest('td').html('<span class="label label-success"><i class="glyphicon glyphicon-ok-sign"></i> Archived </span>');
                });
            }

            self.button('reset');
        });
    });
});


function vacationRequest(id, val) {
    var id_button  = (val == 1) ? 'vac_accept_' + id : 'vac_reject_' + id,
	    btn = $('#' + id_button),
	    obj = {
		    value: val,
		    id: id
	    };

    btn.button('loading');

    $.ajax({
        type: "POST",
        url: GLOBAL_VAC_ACC_REJ,
        data: obj,
        dataType: "json",
        success: function(data) {
            if (data.status == 'success') {
                $('#vac_accept_' + id).hide('fast');
                $('#vac_reject_' + id).hide('fast');

                setTimeout(function() {
                    if (data.result == 1) {
	                    $('#vac_accept_' + id).closest('td').prepend('<span class="label label-success"><i class="glyphicon glyphicon-ok-sign"></i> Approved</span>');
                    } else if (val == 3) {
	                    $('#vac_accept_' + id).closest('td').append('<span class="label label-danger"><i class="glyphicon glyphicon-ok-warning"></i> Rejected</span>');
                    }
                }, 500);
            } else {
	            btn.button('reset');
            }

	        notification(data);
        }
    });
}

function reviewStatus(id, val, accId) {

    var id_button = '';

    switch (val) {
        case 1: id_button = 'review_approve_1_' + id;
            break;
        case 2: id_button = 'review_reject_2_' + id;
            break;
        case 3: id_button = 'review_approve_3_' + id;
            break;
    }

	var btn = $('#' + id_button),
		obj = {
			value: val,
			id: id,
			apartment_id: accId
		};

    btn.button('loading');

    $.ajax({
        type: "POST",
        url: 'universal-dashboard/ajax-approve-review',
        data: obj,
        dataType: "json",
        success: function(data) {
            if (data.status == 'success') {

                $('#review_approve_1_' + id).hide('fast');
                $('#review_reject_2_' + id).hide('fast');
                $('#review_approve_3_' + id).hide('fast');

                setTimeout(function() {
	                var reviewElem = $('#review_app_' + id);

                    switch(val){
                        case 1: reviewElem.closest('div').prepend('<span class="label label-success"><i class="glyphicon glyphicon-ok-sign"></i> Approved With Text</span>');
                            break;
                        case 2: reviewElem.closest('div').prepend('<span class="label label-danger"><i class="glyphicon glyphicon-warning-sign"></i> Rejected</span>');
                            break;
                        case 3: reviewElem.closest('div').prepend('<span class="label label-success"><i class="glyphicon glyphicon-ok-sign"></i> Approved</span>');
                            break;
                    }
                }, 500);
            } else {
	            btn.button('reset');
            }

            notification(data);
        }
    });
}

function markAsPaid(id) {
    var btn = $('#mark_paid_to_aff_' + id),
	    obj = {
		    id: id
	    };

    btn.button('loading');

    $.ajax({
        type: "POST",
        url: GLOBAL_MARK_PAID_TO_AFF,
        data: obj,
        dataType: "json",
        success: function(data) {
            if (data.status == 'success') {
                btn.remove();
            }

            notification(data);
            btn.button('reset');
        }
    });
}

function resolveVacation(id) {
    var id_button  = 'vac_resolve_' + id,
	    btn = $('#' + id_button),
	    obj = {
		    id: id
	    };
    btn.button('loading');

    $.ajax({
        type: "POST",
        url: GLOBAL_VAC_RES,
        data: obj,
        dataType: "json",
        success: function(data) {
            if (data.status == 'success') {
                setTimeout(function() {
                    $(btn).hide('fast', function() {
                        btn.closest('td').append('<span class="label label-success"><i class="glyphicon glyphicon-ok-sign"></i> Resolved</span>');
                    });
                    }, 500);
            } else {
	            btn.button('reset');
            }

	        notification(data);
        }
    });
}

function unpin(resNum, userId) {
	var btn = $('#' + resNum);
    btn.button('loading');

    $.ajax({
        url: GLOBAL_PINNED_RES,
        type: "POST",
        data: {
            pin    : 1,
            userId : userId,
            resNum : resNum
        },

        cache: false,
        dataType: "json",
        success: function(data) {
            if (data.status == 'success') {
            	if (!data.pin) {

            		$('#' + resNum).hide('fast');

            		setTimeout(function() {
        				$('#' + resNum).closest('td').prepend('<span class="label label-success"><i class="glyphicon glyphicon-ok-sign"></i> Unpinned</span>');
            		}, 500);
            	}
            } else {
            	btn.button('reset');

            }

            notification(data);
        }
    });
}

function changePendingTransaction(node, transactionStatus, transactionId, reservationId) {
    var elem = $(node),
        transactionType = $(node).closest('td').find('.exist-transaction-type-button').attr('data-type');
    elem.closest('.btn-group').find('button').button('loading');
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
            if (data.status == 'success' || data.status == 'warning') {
                elem.closest('td').find('.btn-group').hide('fast');

				setTimeout(function() {
					elem.closest('td').append('<span class="label label-success"><i class="glyphicon glyphicon-ok-sign"></i> Changed</span>');
				}, 500);
			} else {
				elem.button('reset');
			}

			notification(data);
		}
	});
}

function changePendingTransactionType(obj, transactionType) {
    $(obj).closest('div').find('.exist-transaction-type-button').html($(obj).text()).attr('data-type', transactionType);
    $(obj).closest('td').find('.exist-transaction-status-button').prop('disabled', false);
}

function reviewCharge(node) {
	var elem = $(node);
	var transection_id  = elem.attr('data-id'),
		requestData = new FormData();

	requestData.append('transection_id', transection_id);
	elem.button('loading');

	$.ajax({
		url: 'universal-dashboard/ajax-reviewed-charge',
		type: "POST",
		data: requestData,
		contentType: false,
		processData: false,
		cache: false,
		dataType: "json",
		success: function(data) {
			if (data.status == 'success') {
				elem.hide('fast');

				setTimeout(function() {
					elem.closest('td').append('<span class="label label-success"><i class="glyphicon glyphicon-ok-sign"></i> Resolved</span>');
				}, 500);
			} else {
				elem.button('reset');
			}

			notification(data);
		}
	});
}

function resolveLastMinuteReservations(node) {
    var elem = $(node),
        res_id = elem.attr('id'),
        data = new FormData();

    data.append('id', res_id);
    elem.button('loading');

    $.ajax({
        url: 'universal-dashboard/ajax-resolve-last-minute-reservation',
        type: "POST",
        data: data,
        contentType: false,
        processData: false,
        cache: false,
        success: function(data) {
            if (data.status != 'error') {
                $('#' + res_id).hide('fast');

                setTimeout(function() {
                    $('#' + res_id).closest('td').append('<span class="label label-success"><i class="glyphicon glyphicon-ok-sign"></i> Resolved</span>');
                }, 500);
            } else {
                elem.button('reset');
            }

            notification(data);
        }
    });
}

function archiveNotificationFromUD(node) {
    var elem = $(node),
        notificationId = elem.attr('data-id'),
        data = new FormData();

    data.append('id', notificationId);
    elem.button('loading');

    $.ajax({
        url: '/notification/archive',
        type: "POST",
        data: data,
        contentType: false,
        processData: false,
        cache: false,
        success: function(data) {
            if (data.status != 'error') {
                elem.hide('fast', function(){
                    elem.closest('td').append('<span class="label label-success"><i class="glyphicon glyphicon-ok-sign"></i> Verified</span>');
                });
            } else {
                elem.button('reset');
            }

            notification(data);
        }
    });
}

function applyCancellation(node) {
    var elem = $(node),
        resNumber     = elem.closest('.btn-group').attr('id'),
        bookingStatus = elem.attr('data-booking-status'),
        bookingId     = elem.attr('data-booking-id'),
        requestData   = new FormData();

    requestData.append('res_number', resNumber);
    requestData.append('booking_status', bookingStatus);
    requestData.append('booking_id', bookingId);
    elem.closest('.btn-group').find('button').button('loading');

    $.ajax({
        url: 'universal-dashboard/ajax-apply-cancelation',
        type: "POST",
        data: requestData,
        contentType: false,
        processData: false,
        cache: false,
        success: function(data) {
            if (data.status == 'success') {
                $('#' + resNumber).hide('fast', function() {
                    $('#' + resNumber).closest('td').append('<span class="label label-success"><i class="glyphicon glyphicon-ok-sign"></i> Canceled</span>');
                });
            } else {
                elem.closest('.btn-group').find('button').button('reset');
            }

            notification(data);
        }
    });
}

function markAsSettled(node) {
    var elem = $(node),
        resNumber = elem.attr('id'),
        requestData = new FormData();

    requestData.append('res_number', resNumber);
    elem.button('loading');

    $.ajax({
        url: 'universal-dashboard/ajax-mark-settled',
        type: "POST",
        data: requestData,
        contentType: false,
        processData: false,
        cache: false,
        success: function(data) {
            if (data.status == 'success') {
                $('#' + resNumber).hide('fast', function(){
                    $('#' + resNumber).closest('td').append('<span class="label label-success"><i class="glyphicon glyphicon-ok-sign"></i> Settled</span>');
                });

            } else {
                elem.button('reset');
            }

            notification(data);
        }
    });
}

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
                    $(elem).closest('td').append('<span class="label label-success"><i class="glyphicon glyphicon-ok-sign"></i> Resolved</span>');
                }, 500);
            } else {
                elem.button('reset');
            }

            notification(data);
        }
    });
}

function cancelPlannedEvaluation(node) {
    var elem = $(node),
        evaluationId = elem.attr('id'),
        data = new FormData();

    elem.button('loading');

    $.ajax({
        url: '/user-evaluation/cancel/' + evaluationId,
        type: "POST",
        data: data,
        contentType: false,
        processData: false,
        cache: false,
        success: function(data) {
            if (data.status != 'error') {
                setTimeout(function() {
                    $('#' + evaluationId).closest('td').html('<span class="label label-success"><i class="glyphicon glyphicon-ok-sign"></i> Cancelled</span>');
                }, 500);
            } else {
                elem.button('reset');
            }

            notification(data);
        }
    });
}

function resolveEvaluation(node) {
    var $elem = $(node),
        evaluationId = $elem.attr('data-id'),
        data = new FormData();

    $elem.button('loading');

    $.ajax({
        url: '/user-evaluation/resolve/' + evaluationId,
        type: "POST",
        data: data,
        contentType: false,
        processData: false,
        cache: false,
        success: function(data) {
            if (data.status != 'error') {
                setTimeout(function() {
                    $elem.closest('td').html('<span class="label label-success"><i class="glyphicon glyphicon-ok-sign"></i> Resolved</span>');
                }, 500);
            } else {
                $elem.button('reset');
            }

            notification(data);
        }
    });
}

function changeBudgetStatus(id, status, obj, e) {
    e.preventDefault();
    var $btnGroup = $(obj).closest('.btn-group');
    var self = $btnGroup.find('a');
    var approvedStatus = $btnGroup.attr('data-status');
    self.button('loading');
    $.ajax({
        url: '/finance/budget/change-status',
        type: "POST",
        dataType: "json",
        cache: false,
        data: {
            id:id,
            status:status
        },
        success: function(data) {
            if (data.status == 'success') {
                var html = status == approvedStatus
                    ? '<span class="label label-success ml5"><i class="glyphicon glyphicon-ok-sign"></i> Approved</span>'
                    :  '<span class="label label-danger ml5"><i class="glyphicon glyphicon-warning-sign"></i> Rejected</span>';

                self.closest('.btn-group').hide('fast', function() {
                    self.closest('td').append(html);
                });

            } else {
                self.button('reset');
            }

            notification(data);
        }
    });
}

function archiveOrders(node, id, e) {
    e.preventDefault();
    var elem = $(node);

    requestData = new FormData();
    requestData.append('order_id', id);

    elem.button('loading');

    $.ajax({
        url: 'universal-dashboard/ajax-archive-order',
        type: "POST",
        data: requestData,
        contentType: false,
        processData: false,
        cache: false,
        success: function(data) {
            if (data.status != 'error') {
                elem.hide('fast', function(){
                    elem.closest('td').append('<span class="label label-primary">Archived</span>');
                });
            } else {
                elem.button('reset');
            }

            notification(data);
        }
    });
}

function resolveUnpaidItem(node, id, e) {
    e.preventDefault();
    var elem = $(node);

    requestData = new FormData();
    requestData.append('expense_item_id', id);

    elem.button('loading');

    $.ajax({
        url: 'universal-dashboard/ajax-resolve-unpaid-item',
        type: "POST",
        data: requestData,
        contentType: false,
        processData: false,
        cache: false,
        success: function(data) {
            if (data.status != 'error') {
                elem.hide('fast', function(){
                    elem.closest('td').append('<span class="label label-success margin-left-5">Resolved</span>');
                });
            } else {
                elem.button('reset');
            }

            notification(data);
        }
    });
}

function markAsReceivedOrders(node, id, e) {
    e.preventDefault();
    var elem = $(node);

    requestData = new FormData();
    requestData.append('order_id', id);

    elem.button('loading');

    $.ajax({
        url: 'universal-dashboard/ajax-mark-received-order',
        type: "POST",
        data: requestData,
        contentType: false,
        processData: false,
        cache: false,
        success: function(data) {
            if (data.status != 'error') {
                elem.hide('fast', function(){
                    elem.closest('td').append('<span class="label label-success">Received</span>');
                    elem.closest('tr').find('td.my-orders-shipping-status').html('<span class="label label-info">Received</span>');
                });
            } else {
                elem.button('reset');
            }

            notification(data);
        }
    });


}
