$(function() {
    var $account = $('#account');
    $account.selectize({
        create: false,
        plugins: ['remove_button'],
        valueField: 'value',
        labelField: 'text',
        searchField: ['text'],
        sortField: [
            {
                field: 'text'
            }
        ]
    });
    $account[0].selectize.clear();

    var $transactionAccount = $('#transaction_account');
    $transactionAccount.selectize({
		valueField: 'unique_id',
		labelField: 'name',
		searchField: ['name'],
		render: {
			option: function(item, escape) {
				// Account type definition: 3 - affiliate, 4 - supplier, 5 - people
				var label = (item.type == 3 ? 'primary' : (item.type == 4 ? 'warning' : 'success'));

				return '<div><span class="label label-' + label + '">' + escape(item.label) + '</span> ' + escape(item.name) + '</div>';
			},
			item: function(item, escape) {
				return '<div data-name="' + escape(item.name) + '" data-type="' + escape(item.type) + '" data-id="' + escape(item.account_id) + '"><span class="label label-primary">' + escape(item.label) + '</span> ' + escape(item.name) + '</div>';
			}
		},
		load: function(query, callback) {
			if (query.length < 2) {
				return callback();
			}

			$.ajax({
				url: SUPPLIER_URL,
				type: 'POST',
				data: {'q': encodeURIComponent(query)},
				error: function() {
					callback();
				},
				success: function(res) {
					callback(res.data);
				}
			});
		},
        onItemAdd: function (value, $item) {
            $.getJSON(GET_SUPPLIER_ACCOUNT + '/' + value, function(data) {

                for (var i in data) {
                    var item = data[i];
                    $account[0].selectize.addOption({
                        text: item.name,
                        value: item.id
                    });
                }
            });
        },
        onItemRemove: function (value) {
            $account[0].selectize.clearOptions();
        },

		persist: false,
		hideSelected: true,
		highlight: false
	});

    var $type = $('#type');
    $type.selectize({
        create: false,
        plugins: ['remove_button'],
        valueField: 'value',
        labelField: 'text',
        searchField: ['text'],
        sortField: [
            {
                field: 'text'
            }
        ]
    });
    $type[0].selectize.clear();

    var $status = $('#status');
    $status.selectize({
        create: false,
        plugins: ['remove_button'],
        valueField: 'value',
        labelField: 'text',
        searchField: ['text'],
        sortField: [
            {
                field: 'text'
            }
        ]
    });
    $status[0].selectize.clear();


    $('.clearForm').click(function(event){
        event.preventDefault();
        $('#amount').val('');
        $('#reason').val('');

        $type[0].selectize.clear();
        $status[0].selectize.clear();
        $account[0].selectize.clear();
        $transactionAccount[0].selectize.clear();
    });

    /** Datatable configuration */
    if (jQuery().dataTable) {
        $('.search').click(function(e) {
            e.preventDefault();

            if (window.gTable) {
                gTable.fnDraw();
            } else {
                window.gTable = $('#espm-management-table').dataTable({
                    bAutoWidth: false,
                    bFilter: false,
                    bInfo: true,
                    bPaginate: true,
                    bProcessing: true,
                    bServerSide: true,
                    iDisplayLength: 25,
                    sPaginationType: 'bootstrap',
                    sAjaxSource: $(this).attr('data-url'),
                    aaSorting: [[0, 'desc']],
                    aoColumns: [
                        {
                            name: "supplier"
                        }, {
                            name: "account"
                        }, {
                            name: "type"
                        }, {
                            name: "status"
                        }, {
                            name: "amount",
                            class: 'text-right'
                        }, {
                            name: "created by"
                        }, {
                            name: "actions",
                            sortable: false,
                            searchable: false,
                            width: "1%"
                        }
                    ],
                    fnServerParams: function (aoData) {
                        jQuery.each($("#espm-form").serializeObject(), function (index, val) {
                            var myObject = {
                                name: index,
                                value: val
                            };

                            aoData.push(myObject);
                        });
                    }
                });

                if ($('.dt-data').hasClass('hidden')) {
                    $('.dt-data').removeClass('hidden');
                }
            }
        });
    }

    $('.fn-buttons a').on('click', function(e) {
        e.preventDefault();

        $(this).closest('.archive-status').find('.fn-buttons a').removeClass('active');
        $(this).addClass('active');
        var isArchived = $(this).closest('.archive-status').find('.is-archived');
        switch ($(this).attr('data-status')) {
            case 'all':
                isArchived.val(2);
                break;
            case 'normal':
                isArchived.val(0);
                break;
            case 'archived':
                isArchived.val(1);
                break;
        }
        gTable.fnGetData().length;
        gTable.fnDraw();
    });

    // Search on [Enter]
    $('#espm-form').keypress(function (e) {
        if (e.which == 13) {
            $('.search').trigger('click');
        }
    });
});