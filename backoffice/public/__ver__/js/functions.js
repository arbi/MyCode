function notification(data) {
    var st = data.status,
        isStick = (data.status == 'success'),
        notice = new PNotify({
            title: st.charAt(0).toUpperCase() + st.slice(1),
            text: data.msg,
            type: data.status,
            delay: 2000,
            shadow: false,
            hide: isStick,
            buttons: {
                closer: false,
                sticker: true
            }
        });
    notice.get().click(function () {
        notice.remove();
    });
}

function state(button_id, callback) {
    var sharp = button_id.substr(1, 2);
    var button = (sharp != '.' ? '#' + button_id : button_id);

    $(button).click(function (e) {
        if (callback) {
            callback(e);
        }
    });
}

function state_loading(button_id, callback) {
    var sharp = button_id.substr(0, 1);
    var button = (sharp != '.' ? '#' + button_id : button_id);

    $(button).click(function (e) {
        $(button).button('loading');

        if (callback) {
            callback(e);
        }
    });
}

function batchAutocomplete(input_id, target_id, url) {
    $("#" + input_id).autocomplete({
        source: function (request, response) {
            $.ajax({
                url: url,
                data: {txt: $("#" + input_id).val()},
                dataType: "json",
                type: "POST",
                success: function (data) {
                    var obj = [];
                    if (data && data.rc == '00') {
                        for (var row in data.result) {
                            var item = data.result[row];
                            var new_obj = {};
                            new_obj.value = item.name;
                            new_obj.id = item.id;
                            obj.push(new_obj);
                        }
                    }
                    response(obj);
                }
            })
        },
        max: 10,
        minLength: 1,
        autoFocus: true,
        select: function (event, ui) {
            if (ui.item) {
                $('#' + target_id).val(ui.item.id);
                $('#' + target_id).trigger('change');
            }
        },
        search: function (event, ui) {
            $('#' + target_id).val('');
        },
        focus: function (event, ui) {
            event.preventDefault();
        }
    });
}

$.widget("custom.catcomplete", $.ui.autocomplete, {
    _renderMenu: function (ul, items) {
        var that = this,
                currentCategory = "";
        $.each(items, function (index, item) {
            if (item.category != currentCategory) {
                ul.append("<li class='ui-autocomplete-category'>" + item.category + "</li>");
                currentCategory = item.category;
            }
            that._renderItemData(ul, item);
        });
    }
});

function batchCatcomplete(input_id, target_id, url) {
    $("#" + input_id).catcomplete({
        source: function (request, response) {
            $.ajax({
                url: url,
                data: {txt: $("#" + input_id).val()},
                dataType: "json",
                type: "POST",
                success: function (data) {
                    var obj = [];
                    if (data && data.rc == '00') {
                        for (var row in data.result) {
                            var item = data.result[row];
                            var new_obj = {};
                            new_obj.value = item.name;
                            new_obj.id = item.id;
                            new_obj.category = item.category;
                            obj.push(new_obj);
                        }
                    }
                    response(obj);
                }
            })
        },
        max: 10,
        minLength: 1,
        autoFocus: true,
        select: function (event, ui) {
            if (ui.item)
                $('#' + target_id).val(ui.item.id);
        },
        search: function (event, ui) {
            $('#' + target_id).val('');
        },
        focus: function (event, ui) {
            event.preventDefault();
        }
    });
}

$.fn.serializeObject = function () {
    var o = {};
    var a = this.serializeArray();
    $.each(a, function () {
        if (o[this.name] !== undefined) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else {
            o[this.name] = this.value || '';
        }
    });
    return o;
};

function viewSiteUrl(url) {
    window.open(url);
}

function locationViewSiteUrl(url) {
    window.open(url);
}

function setCookie(name, value, days) {
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        var expires = "; expires=" + date.toGMTString();
    }
    else
        var expires = "";
    document.cookie = name + "=" + value + expires + "; path=/";
}

function getCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ')
            c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0)
            return c.substring(nameEQ.length, c.length);
    }
    return null;
}

function deleteCookie(name) {
    setCookie(name, 0, -1);
}

function processAuthnticationResult(data) {
    if (data.result == '1') {
        var trigger_id = $('#dynamicLoginForm').attr('data-trigger');

        notification({"status": "success", "msg": "Authentication successful."});

        $('#dynamicLoginForm').modal('hide');
        $('#' + trigger_id).click();
    } else {
        $('#dynamicLoginFormError').removeClass('hide').fadeIn('slow').text('Authentication failed.');
        $('#dynamicLoginForm').modal('show');
        $('#credential').focus();
    }
}

$(document).click(function (event) {
    var target = event.target;
    target = $(target);
    if (!target.hasClass('btn')) {
        target = target.parents('.btn');
    }
    $('#dynamicLoginForm').attr('data-trigger', target.attr('id'));
});

$(document).ajaxComplete(function (event, XMLHttpRequest, ajaxOptions) {
    try {
        var result = $.parseJSON(XMLHttpRequest.responseText);
        if (result.message == "_LOGIN_REQUIRED_") {

            if ($('#dynamicLoginForm').css('display') === 'none') {
                $('#dynamicLoginForm').modal('show');
            }
        }
    } catch (e) {
        // Contact one of Tigrans
    }
});

function validateEmail($email) {
    var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
    return emailReg.test( $email );
}

function clearSearchForm(form) {
    // clearing inputs
    var inputs = form.find('input');
    for (var i = 0; i<inputs.length; i++) {
        switch (inputs[i].type) {
            // case 'hidden':
            case 'text':
                inputs[i].value = '';
                break;
            case 'radio':
            case 'checkbox':
                inputs[i].checked = false;
        }
    }

    // clearing selects
    var selects = form.find('select');
    for (var i = 0; i<selects.length; i++)
        selects[i].selectedIndex = 0;

    // clearing textarea
    var text= form.find('textarea');
    for (var i = 0; i<text.length; i++)
        text[i].innerHTML= '';

    // clearing selectize elements
    var selectizes = $('.selectized');
    var formSelectizes = form.find(selectizes);
    for (var i = 0; i<formSelectizes.length; i++)
        formSelectizes[i].selectize.clear();
}