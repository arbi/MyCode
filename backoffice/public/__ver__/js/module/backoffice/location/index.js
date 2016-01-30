$(function() {
    $( "#search_txt" ).selectize({
        valueField: 'id',
        labelField: 'name',
        searchField: ['name'],
        highlight: false,
        persist: false,
        hideSelected: true,
        options: [],
        render: {
            option: function(item, escape) {
                return '<div><span class="label label-primary">' + escape(item.type_view) + '</span> ' + escape(item.name) + '</div>';
            },
            item: function(item, escape) {
                return '<div><span class="label label-primary">' + escape(item.type_view) + '</span> ' + escape(item.name) + '</div>';
            }
        },
        load: function(query, callback) {
            if (query.length < 2) {
                return callback();
            }

            $.ajax({
                url: GLOBAL_SEARCH_LOCATION,
                type: 'POST',
                data: {'txt': encodeURIComponent(query)},
                error: function() {
                    callback();
                },
                success: function(res) {
                    callback(res.result);
                }
            });
        },
        onItemAdd: function(value, item) {
            var selectedItem = $( "#search_txt" )[0].selectize.sifter.items[value];
            window.location.href = '/location/edit/' + selectedItem.id + '-'+ selectedItem.location_id + '-' + selectedItem.type;
        }
    });
});
