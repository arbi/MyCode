$( window ).load(function() {
    if (window.location.hash) {
        var item = window.location.hash.split('_');
        var id = item[1];
        var type = item[0].substr(1);
        showTheCard(id, type);
    }
});

$(function() {

    omniSearchContact = $('#omni-search-contact').selectize({
        valueField: 'id',
        labelField: 'text',
        searchField: ['text', 'info'],
        sortField: [
            {
                field: 'type'
            }
        ],
        render: {
            option: function(option, escape) {
                return '<div>'
                    + (option.label ? '<span class="label label-' + escape(option.labelClass) + '">' + escape(option.label) + '</span>' : '')
                    + '<span> ' + escape(option.text) + ' </span>'
                    + '<small class="text-muted">' + escape(option.info) + '</small>'
                    + '</div>'
            },
            item: function(option, escape) {
                return '<div>'
                    + (option.label ? '<span class="label label-' + escape(option.labelClass) + '">' + escape(option.label) + '</span>' : '')
                    + '<span> ' + escape(option.text) + ' </span>'
                    + '<small class="text-muted">' + escape(option.info) + '</small>'
                    + '</div>'
            }
        },
        load: function(query, callback) {
            if (!query.length || query.length < 2) return callback();
            $.ajax({
                url: GLOBAL_SEARCH_CONTACT_URL,
                type: 'POST',
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
                        $('#omni-search-contact')[0].selectize.refreshOptions();
                    }
                }
            });
        },
        onChange: function(value) {
            if (value) {
                var item = value.split('_');
                var id = item[0];
                var type = item[1];
                showTheCard(id, type);
            }
            omniSearchContact[0].selectize.clear();
        }
    });

});

function showTheCard(id, type) {
    var contacts = $('#contacts');

    $.ajax({
        url: GLOBAL_GET_CONTACT_URL,
        type: 'POST',
        dataType: 'json',
        data: {
            id: id,
            type: type
        },
        error: function() {
            notification({
                status: 'error',
                msg: 'Failed to retrieve the card. Please try again.'
            })
        },
        success: function(result) {
            if (result.status == 'error') {
                notification(result)
            } else {
                contacts.hide();
                contacts.html(result.cardsPartial);
                contacts.fadeIn();
                window.location.hash = type + '_' + id;
            }
        }
    });
}
