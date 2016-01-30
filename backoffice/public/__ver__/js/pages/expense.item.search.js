$(function() {
    var $supplier = $('.item-search-supplier'),
        $period = $('.item-search-period'),
        $costCenter = $('.item-search-cost-center'),
        $category = $('.item-search-category'),
        $download = $('.download'),
        $searchItemForm = $('#search-item-form'),
        $creatorId = $('.creator_id');


    $creatorId.selectize();
    $creatorId[0].selectize.clear();
    $creatorId[0].selectize.addItem({value:"", text:""});
    $creatorId[0].selectize.addOption("");

    $('.filter-reset').click(function(){
        $supplier[0].selectize.clear();
        $costCenter[0].selectize.clear();
        $category[0].selectize.clear();
        $period.val('');
        $('.item-search-reference').val('');
        $('.item-search-amount').val('');
        $('.item-search-creation-date').val('');
    });

    // Sub Category List
    $.ajax({
        url: '/finance/purchase-order/get-sub-categories',
        type: 'POST',
        async: false,
        error: function () {
            notification({
                status: 'error',
                msg: 'ERROR! Something went wrong (sub category list)'
            });
        },
        success: function (data) {
            if (data.status == 'success') {
                data = data.data;

                var categories = [],
                    subCategoriesSimple = [],
                    subCategoryAndCategories = [],
                    subCategories = {},
                    order = 1;

                for (var categoryId in data) {
                    if (data.hasOwnProperty(categoryId)) {
                        categories.push({
                            value: categoryId,
                            text: data[categoryId].name
                        });

                        subCategoryAndCategories.push({
                            id: categoryId,
                            name: data[categoryId].name,
                            type: 1,
                            order: order++
                        });

                        subCategories[categoryId] = [];

                        for (var subCategoryId in data[categoryId].sub) {
                            if (data[categoryId].sub.hasOwnProperty(subCategoryId)) {
                                subCategories[categoryId].push({
                                    value: data[categoryId].sub[subCategoryId].id,
                                    text: data[categoryId].sub[subCategoryId].name
                                });

                                subCategoryAndCategories.push({
                                    id: data[categoryId].sub[subCategoryId].id,
                                    name: data[categoryId].sub[subCategoryId].name,
                                    type: 2,
                                    order: order++
                                });

                                subCategoriesSimple.push({
                                    value: data[categoryId].sub[subCategoryId].id,
                                    text: data[categoryId].sub[subCategoryId].name,
                                    categoryId: categoryId
                                });
                            }
                        }
                    }
                }

               window.subCategoryAndCategories = subCategoryAndCategories;
            } else {
                notification(data);
            }
        }
    });

    // Setup daterangepicker
    $period.daterangepicker({
        format: globalDateFormat,
        drops: 'down',
        locale: {
            firstDay: 1
        }
    });

    // Supllier
    $supplier.selectize({
        valueField: 'unique_id',
        labelField: 'name',
        searchField: ['name'],
        render: {
            option: function (item, escape) {
                // Account type definition: 3 - affiliate, 4 - supplier, 5 - people
                var label = (item.type == 3 ? 'primary' : (item.type == 4 ? 'warning' : 'success'));

                return '<div><span class="label label-' + label + '">' + escape(item.label) + '</span> ' + escape(item.name) + '</div>';
            },

            item: function (item, escape) {
                return '<div data-name="' + escape(item.name) + '" data-type="' + escape(item.type) + '" data-id="' + escape(item.id) + '"><span class="label label-primary">' + escape(item.label) + '</span> ' + escape(item.name) + '</div>';
            }
        },
        load: function (query, callback) {
            if (query.length < 2) {
                return callback();
            }

            $.ajax({
                url: '/finance/purchase-order/get-accounts',
                type: 'POST',
                data: {'q': encodeURIComponent(query)},
                error: function () {
                    callback();
                },
                success: function (res) {
                    callback(res.data);
                }
            });
        },
        onType: function () {
            $supplier[0].selectize.clearOptions();
        },
        persist: false,
        hideSelected: true,
        highlight: false
    });


    // Cost Center
    $costCenter.selectize({
        plugins: ['remove_button'],
        valueField: 'unique_id',
        searchField: ['name', 'label'],
        persist: false,
        hideSelected: true,
        highlight: false,
        score: function () {
            return function (item) {
                return item.type * 1000 + item.id;
            };
        },
        render: {
            option: function (item, escape) {
                // Type definition: 1 - apartment, 2 - office, 3 - group
                var type = parseInt(item.type),
                    label = (type == 1 ? 'primary' : (type == 2 ? 'success' : 'info'));

                // Don't show groups
                if (type == 3) {
                    return '';
                }

                return '<div><span class="label label-' + label + '">' + escape(item.label) + '</span> ' + escape(item.name) + '</div>';
            },
            item: function (item, escape) {
                // Type definition: 1 - apartment, 2 - office, 3 - group
                var type = parseInt(item.type),
                    label = (type == 1 ? 'primary' : (type == 2 ? 'success' : 'info'));

                return '<div data-account="supplier" data-type="' + escape(type) + '" data-id="' + escape(item.id) + '" data-currency-id="' + escape(item.currency_id) + '"><span class="label label-' + label + '">' + escape(item.label) + '</span> ' + escape(item.name) + '</div>';
            }
        },
        load: function (query, callback) {
            if (query.length < 2) {
                return callback();
            }

            $.ajax({
                url: '/finance/purchase-order/get-cost-centers',
                type: 'POST',
                data: {'q': encodeURIComponent(query)},
                error: function () {
                    callback();
                },
                success: function (res) {
                    callback(res.data);
                }
            });
        },
        onType: function () {
            $costCenter[0].selectize.clearOptions();
        }
    });

    // Category
    $category.selectize({
        valueField: 'order',
        labelField: 'name',
        searchField: ['name'],
        sortField: [{
            field: 'order'
        }],
        options: window.subCategoryAndCategories,
        render: {
            option: function (item, escape) {
                var option = escape(item.name);

                // 1 - Category, 2 - Sub Category
                if (item.type == 1) {
                    option = '<strong>' + option + '</strong>';
                } else {
                    option = '<span style="padding-left: 10px;">' + option + '</strong>';
                }

                return '<div>' + option + '</div>';
            },
            item: function (item, escape) {
                return '<div data-id="' + escape(item.id) + '" data-type="' + escape(item.type) + '">' + escape(item.name) + '</div>';
            }
        },
        persist: false,
        hideSelected: false
    });

    // Setup date pickers
    $searchItemForm.find('.dp').daterangepicker({
        'singleDatePicker': true,
        'format': globalDateFormat
    });

    $searchItemForm.find('.item-search-creation-date').daterangepicker({
        format: globalDateFormat,
        drops: 'down'
    });


    $("#btn-search-po-items").click(function () {
        if (typeof gTable != 'undefined') {
            gTable.fnDraw();
        } else {
            /** Datatable configuration */
            gTable = $('#datatable-po-items').dataTable({
                bAutoWidth: false,
                bFilter: false,
                bInfo: true,
                bPaginate: true,
                bProcessing: true,
                bServerSide: true,
                bStateSave: true,
                iDisplayLength: 25,
                sPaginationType: "bootstrap",
                sAjaxSource: '/finance/item/get-datatable-data',
                aaSorting: [[0, "desc"]],
                aoColumns: [
                    {
                        name: "date_created",
                        searchable: false,
                        width: '100'
                    }, {
                        name: "period",
                        sortable: false,
                        width: '110'
                    },{
                        name: "supplier",
                        sortable: false
                    }, {
                        name: "supplier_reference",
                        sortable: false
                    } ,{
                        name: "cost_centers",
                        sortable: false
                    }, {
                        name: "category"
                    }, {
                        name: "subcategory"
                    }, {
                        name: "amount"
                    }, {
                        name: "currency"
                    },{
                        name: "comment",
                        class: 'text-center',
                        sortable: false
                    },{
                        name: "type",
                        sortable: false
                    }, {
                        name: "actions",
                        sortable: false
                    }
                ],
                "fnServerParams": function (aoData) {
                    additionalParams = $searchItemForm.serializeObject();
                    jQuery.each(additionalParams, function (index, val) {
                        if (index != 'item-search-category' || !val) {
                            var myObject = {
                                name: index,
                                value: val
                            };
                        } else {
                            var $div = $('.selectize-control.item-search-category div[data-value="' + val + '"]');
                            var myObject = {
                                name: index,
                                value: $div.attr('data-id') + '_' + $div.attr('data-type')
                            };
                        }
                        aoData.push(myObject);

                    });
                },
                drawCallback: function( settings ) {
                    $('[data-toggle="popover"]').popover();
                }
            });
            if ($('#datatable-po-items').hasClass('hidden')) {
                $('#datatable-po-items').removeClass('hidden');
            }
            gTable.fnDraw();
        }
    });


    $download.click(function(e) {
        e.preventDefault();
        var btn = $(this);
        btn.button('loading');
        var params = [];
        var additionalParams = $searchItemForm.serializeObject();
        jQuery.each(additionalParams, function (index, val) {
            if (index != 'item-search-category' || !val) {
                var myObject = {
                    name: index,
                    value: val
                };
            } else {
                var $div = $('.selectize-control.item-search-category div[data-value="' + val + '"]');
                var myObject = {
                    name: index,
                    value: $div.attr('data-id') + '_' + $div.attr('data-type')
                };
            }
            params.push(myObject);
        });


        $.get(
            '/finance/item/validate-download-csv' + '?' + $.param(params),
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
        var params = [];
        var additionalParams = $searchItemForm.serializeObject();
        jQuery.each(additionalParams, function (index, val) {
            if (index != 'item-search-category' || !val) {
                var myObject = {
                    name: index,
                    value: val
                };
            } else {
                var $div = $('.selectize-control.item-search-category div[data-value="' + val + '"]');
                var myObject = {
                    name: index,
                    value: $div.attr('data-id') + '_' + $div.attr('data-type')
                };
            }
            params.push(myObject);
        });
        location.href = '/finance/item/download-csv' + '?' + $.param(params);
    }
});