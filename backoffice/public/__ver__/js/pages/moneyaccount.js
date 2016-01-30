function general_hide(elementList) {
	$('.form-group').not('#legal-entity-country-container').not('#cardholder-full-address-container').show();
    if (parseInt($('#legal_entity_id').val()) == 0) {
		$('#legal-entity-country-container').hide()
	} else {
		$('#legal-entity-country-container').show()
	}
	if (parseInt($('#card_holder_id').val()) == 0) {
		$('#cardholder-full-address-container').hide()
	} else {
		$('#cardholder-full-address-container').show()
	}
	if (elementList.length) {
		for (var element in elementList) {
			$('#' + elementList[element]).closest('.form-group').hide();
		}
	}
}

function legalEntityCountry()
{
	var id = parseInt($('#legal_entity_id').val());
	if (id == 0) {
		$('#legal-entity-country').closest('.form-group').hide();
	} else {
		$.ajax({
			url: GLOBAL_GET_LEGAL_ENTITY_COUNTRY,
			type: "POST",
			data: { id: id },
			cache: false,
			success: function(data) {
				if (data.status = 'success') {
					$('#legal-entity-country').text(data.country);
					$('#legal-entity-country').closest('.form-group').show();
				} else {
					notification(data);
				}

			}
		});
	}

}

function cardHolderFullAddress()
{
	var id = parseInt($('#card_holder_id').val());
	if (id == 0) {
		$('#cardholder-full-address-container').hide();
	} else {
		$.ajax({
			url: '/finance/money-account/ajax-get-full-address',
			type: "POST",
			data: { id: id },
			cache: false,
			success: function(data) {
				$('#cardholder-full-address-container').show();
				$('#cardholder-full-address').text(data.full_address)
			}
		});
	}
}


$(function() {
	var bankNotSet = false;

	$('.bank-account-activate').click(function(e) {
		e.preventDefault();

		var btn = $(this),
			activationUrl = btn.attr('href'),
			id = btn.attr('data-id'),
			status = btn.attr('data-status');

		btn.button('loading');

		$.ajax({
			url: activationUrl,
			type: "POST",
			data: {
				id: id,
				status: status
			},
			cache: false,
			success: function(data) {
				if (data.status == 'success') {
					window.location.reload();
				} else {
					btn.button('reset');
					notification(data);
				}
			}
		});
	});

	$('#type').change(function() {
		$(document).trigger('type_change');
	});

	if (parseInt($('#account_details').attr('data-is-edit'))) {
		var currency = $('#currency_id').closest('.form-group'),
			type = $('#type').closest('.form-group');

		currency.insertAfter(type);

		if (!parseInt($('#bank_id').val())) {
			bankNotSet = true;
		}
	}

	$(document).on('type_change', function() {
		var typeValue = parseFloat(
			$('#type').val() || $('.type').attr('data-id')
		);

		switch (typeValue) {
			case 1: // Person
				general_hide([ 'bank_id', 'legal_entity_id', 'legal-entity-country', 'bank_account_number', 'account_ending']);
				$('.bank_info').hide();
				$('fieldset[name="options"]').hide();
				break;
			case 2: // Current
				$('fieldset[name="options"]').hide();
				$('.bank_info').show();
				if (parseInt($('#account_details').attr('data-is-edit'))) {
					if (!bankNotSet) {
						general_hide(['bank_id', 'account_ending']);
					}
				} else {
					general_hide(['account_ending']);
				}

				break;
			case 3: // Credit Card
				$('fieldset[name="options"]').show();
				$('.bank_info').show();

				if (parseInt($('#account_details').attr('data-is-edit'))) {
					if (!bankNotSet) {
						general_hide(['bank_id']);
					}
				} else {
					general_hide([]);
				}

				break;
			case 4: // Savings
				$('fieldset[name="options"]').hide();
				$('.bank_info').show();

				if (parseInt($('#account_details').attr('data-is-edit'))) {
					if (!bankNotSet) {
						general_hide(['bank_id', 'account_ending']);
					}
				} else {
					general_hide(['account_ending']);
				}

				break;
            case 5: // Debit Card
                $('fieldset[name="options"]').show();
                $('.bank_info').show();

                if (parseInt($('#account_details').attr('data-is-edit'))) {
                    if (!bankNotSet) {
                        general_hide(['bank_id']);
                    }
                } else {
                    general_hide([]);
                }

                break;
			default: // Remaining
				$('.bank_info').show();

				if (parseInt($('#account_details').attr('data-is-edit'))) {
					if (!bankNotSet) {
						general_hide(['bank_id']);
					}
				} else {
					general_hide([]);
				}

				//if (typeValue == 2) {
				//	$('#account_ending').closest('.form-group').hide();
				//}

				break;
		}
	}).trigger('type_change');

	if (moneyAccountDocListAaData) {
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
			aaData: moneyAccountDocListAaData,
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
		$('.tbl-wrapper').html(
			'<div class="alert alert-success" role="alert">' +
			'<span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>' +
			' There are no items to display</div>'
		);
	}

	$('#attachBtn').click(function() {
		$('.nav-tabs.tabs-general li.active').removeClass('active');
		$('.tab-highlights').show();
		$('.tab-highlights a').trigger('click');
	});

	$('#cancelAttachBtn').click(function() {
		$('.nav-tabs.tabs-general li.active').removeClass('active');
		$('.tab-highlights').hide();
		$('a[href="#account_attachments"]').trigger('click');
	});
	legalEntityCountry();
	$('#legal_entity_id').change(function(){
		legalEntityCountry();
	})

	cardHolderFullAddress();
	$('#card_holder_id').change(function(){
		cardHolderFullAddress();
	})
});
