$(function(){
    /** Datatable configuration */
    if (jQuery().dataTable) {
        $('.search').click(function(e) {
            e.preventDefault();

            if (window.gTable) {
                gTable.fnDraw();
            } else {
                window.gTable = $('#budget-management-table').dataTable({
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
                            name: "status",
	                        class: 'w1'
                        }, {
                            name: "name"
                        }, {
                            name: "department"
                        }, {
                            name: "period"
                        }, {
                            name: "amount",
		                    class: 'text-right'
                        },{
                            name: "balance",
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
	                aoColumnDefs: [{
		                aTargets: [0],
		                fnCreatedCell: function(nTd, sData, oData, iRow, iCol) {
			                var className = '';

			                switch (sData) {
				                case 'Pending':
					                className = 'warning'; break;
				                case 'Approved':
					                className = 'success'; break;
				                case 'Rejected':
					                className = 'danger'; break;
				                default:
					                className = 'default';
			                }

			                $(nTd).html('<span class="label label-' + className + '">' + sData + '</span>');
		                }
	                }],
                    fnServerParams: function (aoData) {
                        jQuery.each($("#budget-form").serializeObject(), function (index, val) {
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


    $('#frozen').val(0);
    $('#archived').val(0);

    $('.clearForm').click(function(event){
        event.preventDefault();
        $('#name').val('');
        $('#period').val('');
        $('#status').val(0);
        $('#frozen').val(0);
        $('#archived').val(0);
        $('#country').val(-1);
        $('#global').val(-1);
        $('#department').val(-1);

        $('#user')[0].selectize.clear();
    });

    if (jQuery().daterangepicker) {
        $dateRangePickeroptions = {
            ranges: {
                'Today': [moment(), moment()],
                'Next 7 Days': [moment(), moment().subtract(-6, 'days')],
                'Next 30 Days': [moment(), moment().subtract(-29, 'days')],
                'Until The End Of This Month': [moment(), moment().endOf('month')]
            },
            //minDate: moment().subtract(1, 'days'),
            maxDate: moment().subtract(-1, 'years'),
            startDate: moment(),
            endDate: moment().subtract(-1, 'months'),
            format: 'YYYY-MM-DD'
        };

        $('#period').daterangepicker(
            $dateRangePickeroptions
        );
    }

    $users = $('#user');
    $users.selectize({});
    $users[0].selectize.clear();
});
