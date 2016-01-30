$(function () {
    var $taxType = $('.tax-type');
    var locationId = $('#edit_id').val();
    var $locationForm = $("#location_form");

    /** Datatable configuration */
    if (jQuery().dataTable) {
        gTable = $('#historyDatatable').dataTable({
            bFilter: true,
            bInfo: true,
            bServerSide: false,
            bProcessing: false,
            bPaginate: true,
            bAutoWidth: false,
            bStateSave: true,
            iDisplayLength: 25,
            sAjaxSource: null,
            sPaginationType: "bootstrap",
            aoColumns: [
                {
                    name: "date",
                    width: "150px"
                }, {
                    name: "user",
                    width: "200px"
                }, {
                    name: "action"
                }, {
                    name: "message",
                    sortable: false
                }
            ],
            aaSorting: [[0, "desc"]],
            aaData: aaData
        });
    }

    if (parseInt(locationId) > 0) {
        var rmEvent1 = 'removeImges(1, ' + parseInt(locationId) + ')';
        var rmEvent2 = 'removeImges(2, ' + parseInt(locationId) + ')';
    } else {
        var rmEvent1 = 'removeImg(1)';
        var rmEvent2 = 'removeImg(2)';
    }

    $.buttons = {};

    $.buttons.img1ButtonSet = '\
        <div class="btn-group pull-left">\
            <button type="button" class="btn btn-success" id="img1Button" name="img1ButtonSet">\
                <span class="glyphicon glyphicon-cloud-upload"></span>\
                Upload <span class="hidden-sm">Image</span>\
            </button>\
            <button class="btn btn-success dropdown-toggle" data-toggle="dropdown">\
                <span class="caret"></span>\
            </button>\
            <ul class="dropdown-menu">\
                <li>\
                    <a onclick="' + rmEvent1 + '">\
                        <span class="glyphicon glyphicon-remove-circle"></span>\
                        Delete Image <span class="hidden-sm">File</span>\
                    </a>\
                </li>\
            </ul>\
        </div>';

    $.buttons.img1Button = '\
        <button type="button" id="img1Button" class="btn btn-success" name="img1Button">\
            <span class="glyphicon glyphicon-cloud-upload"></span>\
            Upload <span class="hidden-sm">Image</span>\
        </button>';

    $.buttons.img2ButtonSet = '\
        <div class="btn-group pull-left">\
            <button type="button" class="btn btn-success" id="img2Button" name="img2ButtonSet">\
                <span class="glyphicon glyphicon-cloud-upload"></span>\
                <span class="hidden-sm">Upload</span> Thumbnail\
            </button>\
            <button class="btn btn-success dropdown-toggle" data-toggle="dropdown">\
                <span class="caret"></span>\
            </button>\
            <ul class="dropdown-menu">\
                <li>\
                    <a onclick="' + rmEvent2 + '">\
                        <span class="glyphicon glyphicon-remove-circle"></span>\
                        Delete Thumbnail <span class="hidden-sm">File</span>\
                    </a>\
                </li>\
            </ul>\
        </div>';

    $.buttons.img2Button = '\
        <button type="button" id="img2Button" class="btn btn-success" name="img2Button">\
            <span class="glyphicon glyphicon-cloud-upload"></span>\
            <span class="hidden-sm">Upload</span> Thumbnail\
        </button>';

    // if Image file already uploaded
    if ($('#img1_attachment-container').length > 0) {
        $('#img1').after($.buttons.img1ButtonSet);
    } else { // if Image file still not uploaded
        $('#img1').after($.buttons.img1Button);
    }

    if ($('#img2_attachment-container').length > 0) {
        $('#img2').after($.buttons.img2ButtonSet);
    } else { // if Image file still not uploaded
        $('#img2').after($.buttons.img2Button);
    }

    $locationForm.delegate("#img1Button", "click", function () {
        $('#img1').trigger('click');
    });

    $locationForm.delegate("#img2Button", "click", function () {
        $('#img2').trigger('click');
    });

    var attachment1 = $('#img1');
    attachment1.change(function handleFileSelect(evt) {
        $("button").attr("disabled", "disabled");
        var self = $(this);
        var files = evt.target.files; // FileList object

        // Loop through the FileList and render image files as thumbnails.
        for (var i = 0, f; f = files[i]; i++) {
            // Only process image files.
            if (!f.type.match('image.*')) {
                continue;
            }

            var reader = new FileReader();

            // Closure to capture the file information.
            reader.onload = (function (theFile) {
                return function (e) {
                    if ($('#img1_attachment-container')) {
                        $('#img1_attachment-container').remove();
                    }

                    var img = new Image;
                    img.src = e.target.result;
                    img.onload = function () {
                        if ($('#img1Button').attr('name') === 'img1Button') {
                            $('#img1Button').after($.buttons.img1ButtonSet).remove();
                        }
                        var ratio = img.height / img.width;
                        var img_height = ((180 * ratio) / 2) - 22;
                        var container =
                                '<div id="img1_attachment-container" class="preview">' +
                                '<img style="width: 100%;" src="' + e.target.result + '">' +
                                '</div>';
                        $('#img1_preview').append(container);
                    };
                };
            })(f);

            // Read in the image file as a data URL.
            reader.readAsDataURL(f);
        }
        var data = new FormData();
        data.append('file', $('#img1')[0].files[0]);
        data.append('img', '1');
        $.ajax({
            url: GLOBAL_UPLOAD_IMG,
            type: "POST",
            data: data,
            contentType: false,
            processData: false,
            cache: false,
            success: function (data) {
                $("button").removeAttr("disabled");
                if (data.status == 'success') {
                    $("#img1_post").val(data.src);
                } else {
                    notification(data);
                }
            }
        });

    });


    var attachment2 = $('#img2');
    attachment2.change(function handleFileSelect(evt) {
        $("button").attr("disabled", "disabled");
        var self = $(this);
        var files = evt.target.files; // FileList object

        // Loop through the FileList and render image files as thumbnails.
        for (var i = 0, f; f = files[i]; i++) {
            // Only process image files.
            if (!f.type.match('image.*')) {
                continue;
            }

            var reader = new FileReader();

            // Closure to capture the file information.
            reader.onload = (function (theFile) {
                return function (e) {
                    if ($('#img2_attachment-container')) {
                        $('#img2_attachment-container').remove();
                    }
                    var img = new Image;
                    img.src = e.target.result;
                    img.onload = function () {
                        if ($('#img2Button').attr('name') === 'img2Button') {
                            $('#img2Button').after($.buttons.img2ButtonSet).remove();
                        }
                        var ratio = img.height / img.width;
                        var img_height = ((180 * ratio) / 2) - 22;
                        var container =
                                '<div id="img2_attachment-container"  class="preview">' +
                                '<img style="width: 100%;" src="' + e.target.result + '">' +
                                '</div>';
                        $('#img2_preview').append(container);
                    };
                };
            })(f);

            // Read in the image file as a data URL.
            reader.readAsDataURL(f);
        }
        var data = new FormData();
        data.append('file', $('#img2')[0].files[0]);
        data.append('img', '2');
        $.ajax({
            url: GLOBAL_UPLOAD_IMG,
            type: "POST",
            data: data,
            contentType: false,
            processData: false,
            cache: false,
            success: function (data) {
                $("button").removeAttr("disabled");
                if (data.status == 'success') {
                    $("#img2_post").val(data.src);
                } else {
                    notification(data);
                }
            }
        });

    });

    $taxType.change(function () {
        var $parentRow = $(this).closest('.row');
        var $taxValue = $parentRow.find('.tax-val-group');
        var $taxIncluded = $parentRow.find('.tax-included-group');
        var $taxDuration = $parentRow.find('.tax-duration');

        if (this.value > 0) {
            $taxValue.toggleClass('soft-hide', false);
            $taxIncluded.toggleClass('soft-hide', false);
            $taxDuration.toggleClass('soft-hide', false);
            if (this.value == 1) {
                $parentRow.find('.tax-value').toggleClass('border-radus-4', false);
                $parentRow.find('.addon-percent').toggleClass('soft-hide', false);
                $parentRow.find('.addon-currency').toggleClass('soft-hide', true);
            } else if (this.value == 2) {
                $parentRow.find('.addon-percent').toggleClass('soft-hide', true);
                $parentRow.find('.addon-currency').toggleClass('soft-hide', false);
            } else if (this.value == 3) {
                $parentRow.find('.addon-percent').toggleClass('soft-hide', true);
                $parentRow.find('.addon-currency').toggleClass('soft-hide', false);
            }
        } else {
            $taxValue.toggleClass('soft-hide', true);
            $taxIncluded.toggleClass('soft-hide', true);
            $taxDuration.toggleClass('soft-hide', true);
        }
    });

    $taxType.trigger('change');

    if (EDIT_MODE == 0 || LOCATION_TYPE == 2) {
        $('.country-hide').hide();
    }
});

state('save_button', function (e) {
    e.preventDefault();

    var validate = $('#location_form').validate();

    if ($('#location_form').valid())
    {
        saveLocation(false);
    }
    else {
        validate.focusInvalid();
    }
});

state('save_button_with_slug', function (e) {
    e.preventDefault();

    var validate = $('#location_form').validate();

    if ($('#location_form').valid())
    {
        $('#changeUrlModal').modal();

        return;
    }
    else {
        validate.focusInvalid();
    }
});

$('#save_modal_button').click(function () {
    $('#changeUrlModal').modal('hide');
    saveLocation(true);
});

function saveLocation(withSlug) {
    tinymce.triggerSave();

    var obj = $('#location_form').serialize();
    var btn = $('#save_dropdown');

    if (withSlug) {
        obj+= "&slug=" + 1; // re-create slug
    }

    btn.button('loading');

    $.ajax({
        type: "POST",
        url: GLOBAL_SAVE,
        data: obj,
        dataType: "json",
        success: function (data) {
            if (data.status == 'success') {
                if (parseInt(data.id) > 0) {
                    window.location.href = GLOBAL_BASE_PATH + 'location/edit/' + data.id + '-' + data.location_id + '-' + data.type;
                } else {
                    notification(data);
                }
            } else {
                notification(data);
            }
            btn.button('reset');
        }
    });
}

function removeImg(val) {
    switch (val) {
        case 1:
            var button = $.buttons.img1Button;
            break;
        case 2:
            var button = $.buttons.img2Button;
            break;
    }
    $('#img' + val + '_post').val('');
    $('#img' + val + '_preview').html('');
    $('#img' + val + 'Button').parent().remove();
    $('#img' + val).after(button);
}

function removeImges(val, id) {
    $.ajax({
        type: "POST",
        url: GLOBAL_DELETE,
        data: {
            val: val,
            id: id
        },
        dataType: "json",
        success: function (data) {
            if (data.status == 'success') {
                removeImg(val);
                notification(data);
            } else {
                notification(data);
            }
        }
    });
}

$(function () {
    var isProvince = false;

    $.validator.addMethod("percent", function (value, element) {
        return this.optional(element) || /^[0-9]{1,3}(\.[0-9]{1,2})?$/i.test(value);
    }, "Percent is invalid.");

    var autocomplete_val = false,
            isCountry = false,
            min = 0;
    ;

    if ($('#edit_id').val() == '') {
        autocomplete_val = true;
    }

    if ($('#type_location').val() == 2 || ($('#add_type').length > 0 && $('#add_type').val() == 2)) {
        isCountry = true;
        min = 1;
    }

    if ($('#type_location').val() == 4 || ($('#add_type').length > 0 && $('#add_type').val() == 4)) {
        isProvince = true;
    }
    $.validator.addMethod("amount", function (value, element) {
        return this.optional(element) || /^[0-9]+(\.[0-9]{1,2})?$/i.test(value);
    }, "Amount is invalid");
    $('#location_form').validate({
        rules: {
            name: {
                required: true
            },
            currency: {
                number: true
            },
            required_postal_code: {
                number: true
            },
            contact_phone: {

            },
            autocomplete_txt: {
                required: autocomplete_val
            },
            tot: {
                required: false,
                amount: true
            },
            vat: {
                required: false,
                amount: true
            },
            sales_tax: {
                required: false,
                amount: true
            },
            city_tax: {
                required: false,
                amount: true
            },
            province_short_name: {
                required: isProvince
            }
        },
        messages: {},
        highlight: function (element, errorClass, validClass) {
            $(element).closest('.form-group').removeClass('has-success').addClass('has-error');
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).closest('.form-group').removeClass('has-error').addClass('has-success');
        },
        success: function (label) {
            $(label).closest('form').find('.valid').removeClass("invalid");
        },
        errorPlacement: function (error, element) {
        }
    });
});

$(function () {
    $("#autocomplete_txt").autocomplete({
        source: function (request, response) {
            $.ajax({
                url: GLOBAL_GET_PARENT_AUTO,
                data: {
                    txt: $("#autocomplete_txt").val(),
                    edit_id: $("#edit_id").val(),
                    type: $('#add_type').val(),
                    type_location: $('#type_location').val()
                },
                dataType: "json",
                type: "POST",
                success: function (data) {
                    var obj = [];

                    if (data && data.rc == '00') {
                        for (var row in data.result) {
                            var item = data.result[row];
                            var new_obj = {};

                            if (item.parent_name === undefined) {
                                new_obj.value = item.name;
                            } else {
                                new_obj.value = item.name + ' (' + item.parent_name + ')';
                            }

                            new_obj.id = item.id;
                            obj.push(new_obj);
                        }
                    }

                    response(obj);
                }
            });
        },
        max: 10,
        minLength: 1,
        autoFocus: true,
        select: function (event, ui) {
            if (ui.item) {
                $('#autocomplete_id').val(ui.item.id);
            }
        }
    });
});

$('#delete_button').click(function () {
    $.ajax({
        type: "POST",
        url: GLOBAL_DELETE_CHECK,
        data: {
            id: $('#edit_location_id').val(),
            type: $('#type_location').val()
        },
        dataType: "json",
        success: function (data) {
            if (data.status == 'success') {
                $('#deleteModal').modal();
            } else {
                notification(data);
            }
        }
    });
});

$('#delete_location_button').click(function () {
    $.ajax({
        type: "POST",
        url: GLOBAL_DELETE_LOCAION,
        data: {
            id: $('#edit_location_id').val(),
            type: $('#type_location').val(),
            detail_id: $('#edit_id').val()
        },
        dataType: "json",
        success: function (data) {
            if (data.status == 'success') {
                window.location.href = GLOBAL_BASE_PATH + 'location';
            } else {
                notification(data);
            }
        }
    });
});

function viewSitePOI(url) {
    window.open(url);
}

function changeLocationType(val) {
    $('*[id*=view_type_]').each(function() {
        $(this).css({'display':'none'});
    });

    if (val !='provinces') {
        $('#view_type_' + val).css({'display':'block'});
    }

    $('#get_parent').css({'display':'block'});

    var type_view = '';

    $('#timezone').closest('.form-group').css('display', 'none');
    $('#province_short_name').closest('.form-group').css('display', 'none');
    if (val === '2') {
        type_view = 'Continent';
    } else if (val === '4') {
        type_view = 'Country';
        $('#province_short_name').closest('.form-group').css('display', 'block');
    } else if (val === '8') {
        type_view = 'Province';

        $('#timezone').closest('.form-group').css('display', 'block');
    } else {
        type_view = 'City';
    }

    $('#get_parent_txt').html(type_view);
    $('#autocomplete_txt').val('');
    $('#autocomplete_id').val('');

    if (val === '16') {
        $('#view_type_poi').css({'display':'block'});
        $('#show_right_column').css({'display':'block'});
    } else {
        $('#view_type_poi').css({'display':'none'});
        $('#show_right_column').css({'display':'none'});
    }

    if(val > 6) {
        $("#is_searchable_container").css({'display': 'block'});
    } else {
        $("#is_searchable_container").css({'display': 'none'});
    }

    if(val == 2) {
        $("#view_type_countries").css({'display': 'block'});
        $('.city-hide').show();
        $('.poi-hide').show();
        $('.province-hide').show();
        $('.country-hide').hide();
    } else if (val == 4) {
        $('.country-hide').show();
        $('.city-hide').show();
        $('.poi-hide').show();
        $('.province-hide').hide();
    } else if (val == 16) {
        $('.province-hide').show();
        $('.country-hide').show();
        $('.city-hide').show();
        $('.poi-hide').hide();
        $('.img-part').show();
    } else {
        $('.poi-hide').show();
        $('.province-hide').show();
        $('.country-hide').show();
        $('.city-hide').hide();
        google.maps.event.trigger($('#map-canvas')[0], 'resize');
        $("#view_type_countries").css({'display': 'none'});
    }
}