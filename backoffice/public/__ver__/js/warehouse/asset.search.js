$(function() {
    var $categoryTypeBasedPart = $('#category-type-based-part');
    var $datatablePart = $('#datatable-part');
    var $searchButton = $('#btn_search');
    var $btnClearForm = $('#btn_clear_form');

    $searchButton.click(function (event) {
        event.preventDefault();
        var categoryVal = parseInt($('#category-id').val());
        if (categoryVal == -1) {
            categoryType = GLOBAL_TYPE_CONSUMABLE
        } else if (categoryVal == -2) {
            categoryType = GLOBAL_TYPE_VALUABLE
        } else {
            var categoryType = parseInt(GLOBAL_MAP_OF_CATEGORY_TYPES[categoryVal]);
        }

        switch (categoryType) {

            case GLOBAL_TYPE_VALUABLE:
                if (window.valuablegTable) {
                    valuablegTable.fnDraw();
                }
                var $dataTableValueable = $('#datatable-assets-valuable');
                valuablegTable = $dataTableValueable.dataTable({
                    bAutoWidth: false,
                    bFilter: true,
                    bInfo: true,
                    bPaginate: true,
                    bProcessing: true,
                    bServerSide: true,
                    bStateSave: true,
                    iDisplayLength: 25,
                    sPaginationType: "bootstrap",
                    sAjaxSource: '/warehouse/asset/ajax-search-valuable',
                    sServerMethod: "POST",
                    aaSorting: [[1, 'desc']],
                    aoColumns: [
                        {
                            name: 'name'
                        }, {
                            name: 'category',
                            width: '10%',
                            class: 'hidden-sm hidden-xs nowrap'
                        }, {
                            name: 'location',
                            width: '16%',
                            class: 'hidden-xs nowrap'
                        },{
                            name: 'status',
                            width: '16%',
                            class: 'hidden-xs nowrap'
                        }, {
                            name: 'assignee',
                            class: 'hidden-sm hidden-xs'
                        }, {
                            name: 'edit',
                            sortable: false,
                            searchable: false,
                            width: 1
                        }],
                    fnServerParams: function (aoData) {
                        jQuery.each($("#assets-consumable-search-form").serializeObject(), function (index, val) {
                            var myObject = {
                                name: index,
                                value: val
                            };
                            aoData.push(myObject);
                        });
                    }
                });

                if ($dataTableValueable.hasClass('hidden')) {
                    $dataTableValueable.removeClass('hidden');
                }
                break;
            case GLOBAL_TYPE_CONSUMABLE:
                if (window.consumablegTable) {
                    consumablegTable.fnDraw();
                }
                var $dataTableConsumable = $('#datatable-assets-consumable');
                consumablegTable = $dataTableConsumable.dataTable({
                    bAutoWidth: false,
                    bFilter: true,
                    bInfo: true,
                    bPaginate: true,
                    bProcessing: true,
                    bServerSide: true,
                    bStateSave: true,
                    iDisplayLength: 25,
                    sPaginationType: "bootstrap",
                    sAjaxSource: '/warehouse/asset/ajax-search-consumable',
                    sServerMethod: "POST",
                    aaSorting: [[1, 'desc']],
                    aoColumns: [
                        {
                            name: 'category',
                            width: '10%',
                            class: 'hidden-sm hidden-xs nowrap'
                        }, {
                            name: 'location',
                            width: '16%',
                            class: 'hidden-xs nowrap'
                        },{
                            name: 'quantity',
                            width: '16%',
                            class: 'hidden-xs nowrap'
                        }, {
                            name: 'running_out',
                            class: 'hidden-sm hidden-xs',
                            sortable: false,
                            searchable: false
                        },{
                            name: 'threshold',
                            class: 'hidden-sm hidden-xs',
                        }, {
                            name: 'edit',
                            sortable: false,
                            searchable: false,
                            width: 1
                        }],
                    fnServerParams: function (aoData) {
                        jQuery.each($("#assets-consumable-search-form").serializeObject(), function (index, val) {
                            var myObject = {
                                name: index,
                                value: val
                            };
                            aoData.push(myObject);
                        });
                    }
                });

                if ($dataTableConsumable.hasClass('hidden')) {
                    $dataTableConsumable.removeClass('hidden');
                }

                break;
        }


    });


    $btnClearForm.click(function(event) {
       event.preventDefault();
        var categoryVal = parseInt($('#category-id').val());
        if (categoryVal == -1) {
            categoryType = GLOBAL_TYPE_CONSUMABLE
        } else if (categoryVal == -2) {
            categoryType = GLOBAL_TYPE_VALUABLE
        } else {
            var categoryType = parseInt(GLOBAL_MAP_OF_CATEGORY_TYPES[categoryVal]);
        }
        switch (categoryType) {
            case GLOBAL_TYPE_VALUABLE:
                var $status = $('#status');
                var $location = $('#location');
                var $assignee = $('#assignee');
                $status[0].selectize.addItem(0);
                $location[0].selectize.addItem(0);
                $assignee[0].selectize.addItem(0);
                break;
            case GLOBAL_TYPE_CONSUMABLE:
                var $location = $('#location');
                $location[0].selectize.addItem(0);
                $('#running-out').attr('checked', false);
                break;
        }
    });

    $('#category-id').change(function() {
        if ($(this).val() == 0) {
            $categoryTypeBasedPart.html('<p class="help-block">Please Select one of categories to start filtering</p>');
            $datatablePart.html('');
            $searchButton.addClass('disabled').attr("disabled","disabled");
            return false;
        }
        $searchButton.removeClass('disabled').removeAttr("disabled");
        var categoryVal = parseInt($('#category-id').val());
        if (categoryVal == -1) {
            categoryType = GLOBAL_TYPE_CONSUMABLE
        } else if (categoryVal == -2) {
            categoryType = GLOBAL_TYPE_VALUABLE
        } else {
            var categoryType = parseInt(GLOBAL_MAP_OF_CATEGORY_TYPES[categoryVal]);
        }

        $.ajax({
            url: '/warehouse/asset/render-template-search',
            type: "POST",
            data: {
                categoryType : categoryType
            },
            cache: false,
            success: function (data) {
                if (data.status == 'error') {
                    notification(data);
                } else {
                    $categoryTypeBasedPart.html(data.partial_html);
                    $datatablePart.html(data.partial_datatable_html);

                    var locationTargetSelectize = $('#location').selectize({
                        preload: true,
                        maxItems: 1,
                        create: false,
                        valueField: 'id',
                        labelField: 'text',
                        searchField: 'text',
                        sortField: [
                            {field: 'label', direction: 'asc'},
                            {field: 'text', direction: 'asc'}
                        ],
                        render: {
                            option: function(option, escape) {
                                var label;
                                switch (escape(option.label)) {
                                    case 'apartment':
                                        label = '<span class="label label-success">Apartment</span>';
                                        break;
                                    case 'storage':
                                        label = '<span class="label label-primary">Storage</span>';
                                        break;
                                    case 'office':
                                        label = '<span class="label label-info">Office</span>';
                                        break;
                                    case 'building':
                                        label = '<span class="label label-warning">Building</span>';
                                        break;
                                    default :
                                        label = ''
                                }
                                return '<div>'
                                    + label
                                    + '<span> ' + escape(option.text) + ' </span>'
                                    + '<small class="text-muted">' + escape(option.info) + '</small>'
                                    + '</div>'
                            },
                            item: function(option, escape) {
                                var label;
                                switch (escape(option.label)) {
                                    case 'apartment':
                                        label = '<span class="label label-success">Apartment</span>';
                                        break;
                                    case 'storage':
                                        label = '<span class="label label-primary">Storage</span>';
                                        break;
                                    case 'office':
                                        label = '<span class="label label-info">Office</span>';
                                        break;
                                    case 'building':
                                        label = '<span class="label label-warning">Building</span>';
                                        break;
                                    default :
                                        label = ''
                                }
                                return '<div>'
                                    + label
                                    + '<span> ' + escape(option.text) + ' </span>'
                                    + '<small class="text-muted">' + escape(option.info) + '</small>'
                                    + '</div>'
                            }
                        }
                    });

                    locationTargetSelectize[0].selectize.clear();
                    locationTargetSelectize[0].selectize.addOption({'id' : 0, text: '-- All locations --', info: '', 'label' : ''});
                    locationTargetSelectize[0].selectize.addItem(0);
                    $.each(JSON.parse(data.location_list), function(index, value) {
                        locationTargetSelectize[0].selectize.addOption(value);
                    });

                    switch (categoryType) {
                        case GLOBAL_TYPE_VALUABLE:
                                $('#assignee').selectize({
                                    plugins: ['remove_button'],
                                    selectOnTab: true
                                });
                                $('#status').selectize({
                                    plugins: ['remove_button'],
                                    selectOnTab: true
                                });
                            break;
                    }
                }
            }
        });
    });



});
