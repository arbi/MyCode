var omniboxSelectize;
$(function() {
    $omnibox = $('#omnibox');
    $oldbox = $('#oldbox');
    if ($omnibox.length) {
        var omniMinLength;

        if($(window).width() < 768) {
            omniMinLength = 3;
            var eOmnibox = $(".search-form, .omnibox-div");
            eOmnibox.parent().prepend(eOmnibox);
        } else {
            omniMinLength = 2;
        }

        omniboxSelectize = $omnibox.selectize({
            valueField: 'id',
            labelField: 'text',
            searchField: ['text', 'info'],
            sortField: [
                {
                    field: 'type'
                }
            ],
            optgroups: [
                {
                    value: 'apartment-card',
                    label: 'Apartment Cards'
                },
                {
                    value: 'building-card',
                    label: 'Building Cards'
                },
                {
                    value: 'reservation-card',
                    label: 'Reservation Cards'
                },
                {
                    value: 'apartment',
                    label: 'Apartments'
                },
                {
                    value: 'apartment-group',
                    label: 'Apartment Groups'
                },
                {
                    value: 'employee',
                    label: 'Employees'
                }
            ],
            optgroupField: 'label',
            delay: 0,
            render: {
                option: function(option, escape) {
                    var label;
                    switch (escape(option.label)) {
                        case 'employee':
                            label = '<img src="' + option.avatar + '" title="' + option.name + '" class="ginosik-avatar-selectize pull-left">';
                            break;
                        case 'apartment-card':
                            label = '<span class="label label-success pull-left">A</span>';
                            break;
                        case 'reservation-card':
                            label = '<span class="label label-primary pull-left">R</span>';
                            break;
                        case 'building-card':
                            label = '<span class="label label-info pull-left">B</span>';
                            break;
                        default:
                            label = '';

                    }

                    return '<div>'
                        + label
                        + '<span class="pull-left text-overflow omnibox-item-name"> ' + escape(option.text) + ' </span>'
                        + (option.info ? ', <small class="text-muted">' + escape(option.info) + '</small>' : '')
                        + ((option.label == 'employee' && option.canManage) ? '<div class="pull-right label label-primary margin-top-2" data-url="/user/edit/' + option.id + '">manage</div>' : '')
                        + '</div>'
                },
                item: function(option, escape) {
                    var label;
                    switch (escape(option.label)) {
                        case 'apartment-card':
                            label = '<span class="label label-success">Apartment Card</span>';
                            break;
                        case 'reservation-card':
                            label = '<span class="label label-primary">Reservation Card</span>';
                            break;
                        case 'building-card':
                            label = '<span class="label label-info">Building Card</span>';
                            break;
                        case 'employee':
                            label = '<img src="' + option.avatar + '" title="' + option.name + '" class="ginosik-avatar-selectize">';
                            break;
                        case 'apartment':
                            label = '<span class="label label-warning">Apartment</span>';
                            break;
                        case 'apartment-group':
                            label = '<span class="label label-warning">Apartment Group</span>';
                            break;
                    }
                    return '<div>'
                        + label
                        + '<span> ' + escape(option.text) + ' </span>'
                        + '</div>'
                }
            },
            load: function(query, callback) {
                if (!query.length || query.length < omniMinLength) return callback();
                $.ajax({
                    type: "POST",
                    url: "/omnisearch",
                    dataType: 'json',
                    data: {
                        query: query
                    },
                    error: function() {
                        callback();
                    },
                    success: function(res) {
                        if (res.status == 'error') {
                            notification(res);
                        } else {
                            callback(res);
                            omniboxSelectize[0].selectize.refreshOptions();
                        }
                    }
                });
            },
            onItemAdd: function(value, $item) {

                var target = (typeof window.event != 'undefined') ? window.event.target : false;

                if (target != false && $(target).attr('data-url')) {
                    window.location.href = $(target).attr('data-url');
                } else {
                    var item = omniboxSelectize[0].selectize.sifter.items['' + value];
                    switch (item.label) {
                        case 'apartment':
                            window.location.href = "/apartment/" + item.id;
                            break;
                        case 'employee':
                            window.location.href = "/profile/" + item.id;
                            break;
                        case 'apartment-group':
                            window.location.href = "/concierge/edit/" + item.id;
                            break;
                        case 'apartment-card':
                            window.location.href = "/frontier?id=" + item.id;
                            break;
                        case 'reservation-card':
                            window.location.href = "/frontier?id=" + item.id;
                            break;
                        case 'building-card':
                            window.location.href = "/frontier?id=" + item.id;
                            break;
                    }
                }
            }
        });

        omniboxSelectize[0].selectize.focus();

        $('.search-form').on('submit', function(e) {
            var searchValue = $(this).find('input').val();
            e.preventDefault();
            var pattern = new RegExp('^[1-9][0-9](.*)$');
            if (pattern.test(searchValue)) {
                $.ajax({
                    type: "POST",
                    url: "/omnisearch",
                    data: {
                        resNum: searchValue
                    },
                    dataType: "json",

                    success: function(data) {
                        if (data.status == 'success') {
                            window.location.href = "/booking/edit/" + data.result;

                        }
                    }
                });
            }
            return false;
        });
    }
});