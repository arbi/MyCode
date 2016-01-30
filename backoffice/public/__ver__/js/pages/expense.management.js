$(function() {
	var $expenseTableContainer = $('.expense-table-container'),
		$expenseSearch = $('.expense-search'),
		$search = $(".search"),
		$download = $(".download"),
		$clear = $('.clear'),
		$currency = $('.currency-id'),
		$creator = $('.creator-id'),
		$manager = $('.manager-id'),
		$status  = $('.status'),
		$financeStatus  = $('.finance_status'),
		$creationDate = $('.creation-date'),
		$expectedComplitionDate = $('.expected_completion_date'),
		$data = $('.data'),
		// Exceptional permission (top to bottom)
		isFinance = parseInt($data.attr('data-is-finance')),
		isBudgetHolder = !isFinance && parseInt($data.attr('data-is-budget-holder')),
		isStandardEmployee = !isFinance && !isBudgetHolder;

    function disableOrEnableButtons()
    {

        var poId = $('.tid').val();
        var currency = $currency.val();
        var manager = $manager.val();
        var creator = $creator.val();
        var title = $('.title').val();
        var creationDate = $creationDate.val();
        var expectedComplitionDate = $expectedComplitionDate.val();
        var status = $status.val();
        var financeStatus = $financeStatus.val();
        if (
            poId == ''
            &&
            currency == ''
            &&
            manager == ''
            &&
            creator == ''
            &&
            title == ''
            &&
            creationDate == ''
            &&
            expectedComplitionDate == ''
            &&
            status == ''
            &&
            financeStatus == ''
        ) {
            $search.addClass('disabled');
            $download.addClass('disabled');
        } else {
            $search.removeClass('disabled');
            $download.removeClass('disabled');
        }
    }

    $('.tid').keyup(function(){
        disableOrEnableButtons();
    });
    $('.currency-id, .creator-id, .manager-id, .status, .finance_status').change(function(){
        disableOrEnableButtons();
    });
    $('.creation-date, .expected_completion_date').bind('change keyup', function(){
        disableOrEnableButtons();
    });

	// Initialize (It is make sense you are the finance associate, budget holder or standard employee)
	// And.. Yes!!! You are right! It's sexy but useless here but sexy.
	~function() {

		if (isBudgetHolder || isStandardEmployee) {
			// Remvoe elements
			$creator.remove();
			$manager.remove();

			// Remove empty row
			$expenseSearch.find('.row').eq(1).remove();
		}

		// ATTENTION! for standard employee look at bottom

		// finance can everything
	}();

	$download.click(function(e) {
		e.preventDefault();
        if ($(this).hasClass('disabled')) {
            return false;
        }

		var btn = $(this);
		btn.button('loading');

		$.get(
			GLOBAL_CHECK_DOWNLOAD_CSV_URL + '?' + $expenseSearch.serialize(),
			function (data, status) {
				if (data.status == 'error') {
					notification(data);
				} else {
					downloadCsv()
				}
			}
		);

		btn.button('reset');
	});

	function downloadCsv()
	{
		var dataSerialized = [],
			additionalParams = $expenseSearch.serializeObject();

		jQuery.each(additionalParams, function(index, val) {
			dataSerialized.push({
				name: index,
				value: val
			});
		});


		location.href = GLOBAL_DOWNLOAD_CSV_URL + '?' + $.param(dataSerialized);
	}

	$search.click(function(e) {
		e.preventDefault();
        if ($(this).hasClass('disabled')) {
            return false;
        }

		var url = $(this).attr('data-url');

		if (window.gTable) {
			gTable.fnDraw();
		} else {
			window.gTable = $('.expense-datatable').dataTable({
				bAutoWidth: false,
				bFilter: false,
				bInfo: true,
				bPaginate: true,
				bProcessing: true,
				bServerSide: true,
				iDisplayLength: 25,
				sPaginationType: 'bootstrap',
				sAjaxSource: url,
				aaSorting: [[0, 'desc']],
				aoColumns: [{
					name: 'id',
					class: 'hidden-xs',
					width: 36
				}, {
					name: 'date',
					searchable: false,
					width: '110'
				},{
                    name: 'validity',
                    searchable: false,
                    sortable: false,
                    width: '110'
                }, {
					name: 'status'
				}, {
					name: 'finance_status'
				}, {
					name: 'ticket_balance',
					class: 'text-right',
					searchable: false,
					width: '7%'
				}, {
					name: 'limit',
					class: 'text-right hidden-xs hidden-sm',
					searchable: false,
					width: '7%'
				}, {
                    name: 'currency',
                    searchable: false,
                    width: '45'
                }, {
					name: 'purpose',
					class: 'hidden-xs hidden-sm',
					searchable: false,
                    sortable: false,
					width: '50'
				}, {
					name: 'edit',
					searchable: false,
                    sortable: false,
					width: 1
				}],
				aoColumnDefs: [{
                    aTargets: [9],
                    fnCreatedCell: function(nTd, sData, oData, iRow, iCol) {
                        var cell = $(nTd),
                            id = oData[0],
                            html = ['<a href="/finance/purchase-order/ticket/', id, '" class="btn btn-xs btn-primary pull-left" target="_blank" data-html-content="Edit"></a>'].join('');

                        cell.html(html);
                    }
			    }],
				fnServerParams: function (aoData) {
					jQuery.each($("#expense-search").serializeObject(), function(index, val) {
						aoData.push({
							name: index,
							value: val
						});
					});


				},
                drawCallback: function( settings ) {
                    $('[data-toggle="popover"]').popover();
                }
			});

			if ($expenseTableContainer.hasClass('hidden')) {
				$expenseTableContainer.removeClass('hidden');
			}
		}
	});

    $manager.selectize();
    $status.selectize();
    $financeStatus.selectize();
    $creator.selectize();
    $currency.selectize();

    $currency[0].selectize.clear();
    $manager[0].selectize.clear();
    $status[0].selectize.clear();
    $financeStatus[0].selectize.clear();
    $creator[0].selectize.clear();


	$creationDate.daterangepicker({
		ranges: {
			'Today': [moment(), moment()],
			'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
			'Last 7 Days': [moment().subtract(6, 'days'), moment()],
			'Last 30 Days': [moment().subtract(29, 'days'), moment()],
			'This Month': [moment().startOf('month'), moment().endOf('month')],
			'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
		},
		startDate: moment().subtract(29, 'days'),
		endDate: moment(),
		format: 'YYYY-MM-DD'
	});

    $expectedComplitionDate.daterangepicker({
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        },
        startDate: moment().subtract(29, 'days'),
        endDate: moment(),
        format: globalDateFormat
    });



	$clear.on('click', function(e) {
		e.preventDefault();

		var selectizeElements = [$currency, $manager, $creator, $status, $financeStatus],
			simpleElements = [
				'.account-reference',
				'.expected_completion_date',
				'.creation-date',
				'.amount',
				'.transaction_amount',
				'.tid'
			];

		// Clear standard inputs
		for (var i = 0; i < simpleElements.length; i++) {
			$(simpleElements[i]).val('');
		}

		// Clear selectized elements
		for (var j in selectizeElements) {
			if (selectizeElements.hasOwnProperty(j)) {
				selectizeElements[j][0].selectize.clear();
			}
		}
        $search.addClass('disabled');
        $download.addClass('disabled');
	});
});
