$(function() {
    $( ".aliasRemoveRow" ).click(function() {
        removeAliasRow(this);
    });
    $('#asset-category-form').validate({
        rules: {
            name: {
                required: true
            },
            type: {
                required: true,
                number: true,
                min: 1
            },
            'sku_names[]': {
                required: false
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
        errorPlacement: function(error, element) {
        }
    });

	$('.add-sku').click(function(e) {
		e.preventDefault();

		var tr = $(this).closest('tr').clone();
        tr.find('.add-sku').text('Delete').removeClass('btn-primary').addClass('btn-danger').addClass('remove-sku');
		tr.prependTo('.sku-container tbody');
        $(this).closest('tr').find('.sku-name').val('');
	});

	$('.sku-container').on('click', '.remove-sku', function(e) {
		e.preventDefault();

		$(this).closest('tr').remove();
	});

    if (parseInt(CATEGORY_ID)) {
        $('select#type').attr('disabled', true);
    }

    $('#asset-category-form').submit(function() {
        if (parseInt(CATEGORY_ID)) {
            $('#type').removeAttr('disabled');
        }
    });

    $("#add-aliases").click(function() {
        var $lastRow = $('#aliases_table>tbody');
        if ($lastRow.children().length < 10) {
            var $tr = $(this).closest('tr');
            var $aliasName = $tr.find('.alias-name');
            var inputForm = '<div class="input-prepend input-append form-group margin-0" id="alias_0"><div class="col-sm-12"><input name="aliases[]" type="text" class="form-control" id="alias_0" maxlength="50" value="' + $aliasName.val() + '" onblur="checkAliasUniqueness(this)" data-id="" /></div></div>';

            var html = '<tr class="alias_tr">\
                        <td class="text-right">' + inputForm + '</td>\
                        <td><a href="javascript:void(0)" class="btn btn-sm btn-danger btn-block aliasRemoveRow">Delete</a> <input value="0" type="hidden" name="deletedAliases[]" class="removeRow"/></td>\
                    </tr>';
            $lastRow.prepend(html);
            $aliasName.val('');
            $(".aliasRemoveRow").click(function () {
                removeAliasRow(this);
            });
        } else {
            notification({
                status: 'warning',
                msg: 'Max. 10 aliases are allowed'
            });
        }
    });

    $('div#modal_merge_category').on('click', '#merge-category', function (e) {
        e.preventDefault();
        var btn = $(this);
        btn.button('loading');

        $.ajax({
            type: "POST",
            url: MERGE_CATEGORY,
            data: {
                current_category_id: CATEGORY_ID,
                merge_category_id: $('#merge_category_id').val(),
                name: $('#name').val(),
                type: CATEGORY_TYPE
            },
            dataType: "json",
            success: function(data) {
                if (data.status == 'reload') {
                    window.location.href = '/warehouse/category';
                    btn.button('reset');

                } else {
                    btn.button('reset');
                    notification(data);
                }
            }

        });
    });

    if (HISTORY_DATA.length > 0) {
        $('#history_clean').hide();

        $('#datatable_history').DataTable({
            bFilter: true,
            bInfo: true,
            bServerSide: false,
            bProcessing: false,
            bPaginate: true,
            bAutoWidth: false,
            bStateSave: false,
            iDisplayLength: 10,
            sAjaxSource: false,
            sPaginationType: "bootstrap",
            aaSorting: [[0, 'desc']],
            aaData: HISTORY_DATA,
            sDom: 'l<"enabled">frti<"bottom"p><"clear">',
            aoColumns: [
                {
                    "name": "date",
                    "width": "150px"
                }, {
                    "name": "user",
                    "width": "200px"
                }, {
                    "name": "message",
                    "sortable": false
                }
            ]
        });
    } else {
        $('#datatable_history').hide();
        $('#history_clean').show();
    }
});

function removeAliasRow(obj){
    var parent = $(obj).closest('.alias_tr');
    $(parent).remove();
}

function checkAliasUniqueness(node) {
    var $node = $(node);
    var id = $node.attr('data-id');
    var name = $node.val();

    $.ajax({
        type: "POST",
        url: CHECK_UNIQUE_URL,
        data: {
            id: id,
            name: name
        },
        dataType: "json",
        success: function (data) {
            if (data.status == 'error') {
                notification({
                    'status': data.status,
                    msg: data.msg
                });
            }
        }
    });
}
