$(function() {

    function drawDatatable() {

        /** Datatable configuration */
        gTable = $('#datatable_statistics_info').dataTable({
            bAutoWidth: false,
            bFilter: false,
            bInfo: true,
            bPaginate: true,
            bProcessing: true,
            bServerSide: false,
            bStateSave: true,
            iDisplayLength: 25,
            sPaginationType: "bootstrap",
            sAjaxSource: DATATABLE_AJAX_SOURCE,
            aoColumns:[
                {
                    name: "name",
                    sortable: true,
                    class: "hidden-xs"
                }, {
                    name: "building",
                    sortable: true
                }, {
                    name: "city",
                    sortable: true,
                    class: "hidden-xs hidden-sm"
                }, {
                    name: "pax",
                    sortable: true
                }, {
                    name: "bedrooms",
                    sortable: true,
                    class : "hidden-xs"
                }, {
                    name: "unsold",
                    sortable: true
                },{
                    name: "month1",
                    sortable: true,
                    width: "10%"
                },{
                    name: "month2",
                    sortable: true,
                    width: "10%"
                },{
                    name: "month3",
                    sortable: true,
                    width: "10%"
                }
            ],
            aoColumnDefs: [],
            fnServerParams: function ( aoData ) {
                additionalParams = $("#statistics-form").serializeObject();
                jQuery.each(additionalParams, function(index, val) {
                    var myObject = {
                        name:  index,
                        value: val
                    };

                    aoData.push( myObject );
                });
            }
        });

        getNextMonths();

        if ($('#datatable_statistics_container').hasClass('hidden')) {
            $('#datatable_statistics_container').removeClass('hidden');
        }
    }

    $("#btn_search_statistics").click(function() {
        if (window.gTable) {
            gTable.fnReloadAjax();
            getNextMonths();
        } else {
            drawDatatable();
        }
    });

    $("#statistics-form").keypress(function (e) {
        if (e.which == 13) {
            $( "#btn_search_statistics" ).trigger('click');
            e.preventDefault();
        }
    });


    $('#apt_location').on('keypress keyup', function() {
        batchCatcomplete('apt_location', 'apt_location_id', FIND_COUNTRY_CITY_AUTOCOMPLETE_URL);
        if (!$('#apt_location').val().length) {
            $('#apt_location_id').attr('value', '');
        }
    });

    $('#building').on('keypress keyup', function() {
        batchCatcomplete('building', 'building_id', BUILDING_NAME_AUTOCOMPLETE_URL);
        if (!$('#building').val().length) {
            $('#building_id').attr('value', '');
        }
    });

    if ($('#request_date').val()) {
        $('#starting_form option[value='+$('#request_date').val()+']').prop('selected', true);
        $("#btn_search_statistics").trigger('click');
    }

    $('#starting_form').on('change', function(){
        $('#request_date').val($('#starting_form option:selected').val());
    });

    function getNextMonths() {
        var monthList = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

        var selectedDate  = $('#starting_form option:selected').val();
        var selectedMonth = selectedDate.split('_');

        var now = new Date();

        var month1 = now.setDate(1);
        var month1 = now.setMonth(parseInt(selectedMonth[1]) - 1);
        var month2 = now.setMonth(parseInt(selectedMonth[1]));
        var month3 = now.setMonth(parseInt(selectedMonth[1]) + 1);
        var month1 = new Date(month1);
        var month2 = new Date(month2);
        var month3 = new Date(month3);

        $('th.month1').html(monthList[month1.getMonth()] + ' %');
        $('th.month2').html(monthList[month2.getMonth()] + ' %');
        $('th.month3').html(monthList[month3.getMonth()] + ' %');
    }
});
