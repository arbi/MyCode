$(function() {
    var $parkingSpots = $('#parking_spot_ids');

    $("#apartment_details").ajaxForm({
        // dataType identifies the expected content type of the server response
        dataType:  'json',
        beforeSubmit: function(formData, jqForm, options) {
            var valid = $(jqForm[0]).valid();
            if(valid)
                $('#save_button').button('loading');
            return valid;
        },
        // success identifies the function to invoke when the server response
        // has been received
        success:   function(data) {
            $('#save_button').button('reset');
            notification(data);
        }
    });

	$('.add-furniture-table').on('click', '.delete-furniture', function(e) {
		e.preventDefault();

		var btn = $(this);

		$('#deleteFurnitureModal').modal('show').attr({
			'data-url': btn.attr('data-url'),
			'data-id': $(this).closest('tr').attr('data-id')
		});

		btn.button('loading');
	});

	$('#deleteFurnitureButton').click(function(e) {
		e.preventDefault();

		var btn = $('.delete-furniture'),
			modal = $('#deleteFurnitureModal');

		$.ajax({
			type: "POST",
			url: modal.attr('data-url'),
			dataType: "json",
			success: function(data) {
				if (data.status == 'success') {
					btn.closest('tr').each(function() {
						if ($(this).attr('data-id') == modal.attr('data-id')) {
							$(this).hide();
						}
					});
				}

				notification(data);
				btn.button('reset');
				modal.modal('hide');
			}
		});
	});

	$('#deleteFurnitureModal').on('hidden', function() {
		$('.delete-furniture').button('reset');
	});

	$('.add-furniture').click(function(e) {
		e.preventDefault();

		if ($('.furniture-type').val() <= 0) {
			$('.furniture-type').focus();

			return false;
		}

		if ($('.furniture-count').val() <= 0) {
			$('.furniture-count').focus();

			return false;
		}

		var btn = $(this),
			type = btn.closest('tr').find('.furniture-type').val(),
			count = btn.closest('tr').find('.furniture-count').val(),
			apartmentId = btn.attr('data-apartment-id'),
			title = btn.closest('tr').find('.furniture-type option:selected').text();

		btn.button('loading');

		$.ajax({
			type: "POST",
			url: $(this).attr('href'),
			data: {
				apartment_id: apartmentId,
				type: type,
				count: count
			},
			dataType: "json",
			success: function(data) {
				if (data.status == 'success') {
					var tbody = btn.closest('table').find('tbody');

					if (tbody.find('td').eq(0).text() == 'No furniture') {
						tbody.find('tr').eq(0).remove();
					}

					$('<tr data-id="' + data.data.id + '">\
						<td>' + title + '</td>\
						<td class="text-center">' + data.data.count + '</td>\
						<td><a href="#deleteFurnitureModalEx" data-url="' + data.data.url + '" data-loading-text="Deleting..." class="btn btn-sm btn-danger delete-furniture">Delete</a></td>\
					</tr>').hide().prependTo(tbody).show('fast');
				}

				notification(data);
				btn.button('reset');
			}
		});
	});

    $parkingSpots.selectize({
        create: false,
        plugins: ['remove_button'],
        valueField: 'id',
        labelField: 'unit',
        searchField: ['unit'],
        sortField: [
            {
                field: 'unit'
            }
        ]
    });

    if (PARKING_LOT_IS_VIRTUAL) {
        $('#parking_spot_ids').next().hide();
        $('#parking_spot_ids').parent().append(getVirtualLotDiv());
    }

    $.each(apartmentParkingSpots, function(index, spotId) {
        $parkingSpots[0].selectize.addItem(spotId);
    });

    $('#parking_lot_id').change(function() {
        var selectizeObj = $parkingSpots[0].selectize;
        selectizeObj.clear();

        $.ajax({
            type: "POST",
            url: GET_PARKING_SPOTS_URL,
            data: {
                parking_lot_id: $(this).val()
            },
            dataType: "json",
            success: function(data) {
                if (data.is_virtual) {
                    $('#parking_spot_ids').next().hide();

                    if (!$('#parking_spots_all').length) {
                        $('#parking_spot_ids').parent().append(getVirtualLotDiv());
                    } else {
                        $('#parking_spots_all').show();
                    }
                } else {
                    $('#parking_spot_ids').next().show();
                    $('#parking_spots_all').hide();

                    selectizeObj.clearOptions();
                    $.each(data, function(index, option) {
                        selectizeObj.addOption({id: option.id, unit: option.unit});
                    });
                }
            }
        });
    });

});

function getVirtualLotDiv() {
    return '<div class="col-sm-12 help-block" id="parking_spots_all">All spots</div>';
}
