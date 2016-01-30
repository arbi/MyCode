$(function(){

    /** Datatable configuration */
    if (jQuery().dataTable) {
        gTable = $('#historyDatatable').dataTable({
            "bFilter": true,
            "bInfo": true,
            "bServerSide": false,
            "bProcessing": false,
            "bPaginate": true,
            "bStateSave": true,
            "bAutoWidth": false,
            "iDisplayLength": 25,
            "sAjaxSource": null,
            "sPaginationType": "bootstrap",
            "aoColumns":[
                {
                    "name": "date",
                    "bSortable": true,
                    "width": "150px"
                }, {
                    "name": "user",
                    "bSortable": true,
                    "width": "200px"
                }, {
                    "name": "action",
                    "bSortable": true
                }, {
                    "name": "message",
                    "bSortable": false
                }
            ],
            "aaSorting": [[0, 'desc']],
            "aaData": aaData
        });
    }

    if($('#logo').attr('value')){
        $('#logo').after(
            '<div id="preview" class="col-sm-5"><img src="//'+IMG_DOMAIN_NAME+$('#logo').attr('value')+'" id="logo_preview"></div>'
        );
    }

    $.buttons = {};
    $.buttons.logoButtonSet = '\
        <div class="btn-group pull-right helper-margin-right-64px">\
            <button type="button" class="btn btn-success" id="uploadLogo" name="uploadLogoSet">\
                <span class="glyphicon glyphicon-upload"></span>\
                Upload Logo File\
            </button>\
            <button class="btn btn-success dropdown-toggle helper-margin-right-04em" data-toggle="dropdown">\
                <span class="caret"></span>\
            </button>\
            <ul class="dropdown-menu">\
                <li>\
                    <a onclick="deleteLogo()">\
                        <span class="glyphicon glyphicon-remove-circle"></span>\
                        Delete Logo File\
                    </a>\
                </li>\
            </ul>\
        </div>';

    $.buttons.logoButton = '\
        <button type="button" name="uploadLogo" id="uploadLogo" class="btn btn-success pull-right helper-margin-right-64px" value="#deletePartner">\
            <span class="glyphicon glyphicon-upload"></span>\
            Upload Logo File\
        </button>';

    if($('#logo_preview').length > 0){
        $('#img_file').after($.buttons.logoButtonSet);
    } else {
        $('#img_file').after($.buttons.logoButton);
    }

    $("#partner").delegate("#uploadLogo", "click", function() {
    //$('#uploadLogo').on('click', function(){
        $('#img_file').trigger('click');
    });

    var attachment = $('#img_file');

    attachment.change(function handleFileSelect(evt) {

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
			reader.onload = (function(theFile) {
				return function(e) {
					// Render thumbnail.
					if($('#logo_preview')){
                        $('#logo_preview').remove();
                    }

                    if($('.attachment-container')){
                        $('.attachment-container').remove();
                    }

                    var img = new Image;
                    img.src = e.target.result;
                    img.onload = function() {

                        if($('#uploadLogo').attr('name') === 'uploadLogo'){
                            $('#uploadLogo').after($.buttons.logoButtonSet).remove();
                        }

                        var ratio = img.height/img.width;
                        var img_height = ((180*ratio)/2)-22;

//                        var container =
//						'<div class="attachment-container">' +
//							'<img style="width: 180px;" src="' + e.target.result + '">' +
//                            '<div class="preview-border" style="top:'+img_height+'px;"></div>' +
//						'</div>';
                        var container =
						'<div class="attachment-container">' +
							'<img style="width: 180px;" src="' + e.target.result + '">' +
						'</div>';
                        if(($('#preview')).length > 0){
                            $('#preview').append(container);
                        } else {
                            $('#logo').after('<div id="preview" class="col-sm-5"></div>');
                            $('#preview').append(container);
                        }
                    };


				};
			})(f);

			// Read in the image file as a data URL.
			reader.readAsDataURL(f);
		}

        if($('#savePartner').length > 0){
            //$('#savePartner').attr('data-loading-text', 'Image loading...');
            var btn = $('#savePartner');
        }
        if($('#addPartner').length > 0){
            var btn = $('#addPartner');
        }
        btn.button('loading');


        var data = new FormData();
        //data.append( 'userId', $('#partner-number').attr('value') );
        data.append( 'file', $('#img_file')[0].files[0] );

        $.ajax({
            url: GLOBAL_BASE_PATH + 'upload/ajax-upload-partner-logo',
            type: "POST",
            data: data,
            contentType: false,
            processData: false,
            cache: false,
            success: function(data){
                if (data.status == 'error') {
                    $.pnotify.defaults.history = false;
                    $.pnotify({
                    title: data.msg,
                    text: '',
                    type: 'error',
                    history: false,
                    icon: false,
                    hide: true,
                    sticker: true});
                    //$("#avatar").attr('src', oldAvatar);
                }
                else{
                    $("#newLogo").attr('value', data.src);
                }
                btn.button('reset');
            }
        });
	});
    $('#deletePartner').click( function(e){
        e.preventDefault();
        $('#deletePartnerModal').modal('show');
    });


    $('#sendEmail').click( function(e) {
        e.preventDefault();
        sendEmailToPartner();
    });

    $('#loginPartner').click( function(e) {
        e.preventDefault();
        gid = $('#partner-number').val();
        passwd = $('#loginPartner').val();

        $('#login-id').val(gid);
        $('#login-password').val(passwd);

        document.forms["loginForm"].submit();
    });

    $('#openPartner').click( function(e) {
        e.preventDefault();
        window.open($(this).val(), "_blank");
    });

    $("#partner_deactivate_button").click(function() {
        var partner_id = $("#partner-number").val();
        $.ajax({
            type: "POST",
            url: GLOBAL_BASE_PATH + 'partners/activate/' + partner_id + '/0',
            dataType: "json",
            success: function(data) {
                window.location.reload();
            },
            error: function() {
                notification({
                    msg: "Something went wrong. Please refresh the page and try again.",
                    status: "error"
                })
            }
        });
    });

    $("#partner_activate_button").click(function() {
        var partner_id = $("#partner-number").val();
        $.ajax({
            type: "POST",
            url: GLOBAL_BASE_PATH + 'partners/activate/' + partner_id + '/1',
            dataType: "json",
            success: function(data) {
                window.location.reload();
            },
            error: function() {
                notification({
                    msg: "Something went wrong. Please refresh the page and try again.",
                    status: "error"
                })
            }
        });
    });

    $('#partner-city-commission-button').click(function(e) {
        var partnerCity = $('#partner_city').val();
        var partnerCityCommission = $('#partner_city_commission').val();
        var partnerId = $('#partnerId').val();
        if (partnerCity > 0 && partnerCityCommission > 0 && partnerId > 0) {
            $(this).closest('tr').removeClass('has-error').addClass('has-success');
            var btn = $(this);
            btn.button('loading');
            $.ajax({
                type: "POST",
                url: GLOBAL_PARTNER_CITY_COMMISSION,
                data: {
                    city_id:partnerCity,
                    commission:partnerCityCommission,
                    partner_id:partnerId
                },
                dataType: "json",
                success: function(data) {
                    if(data.status == 'success'){
                        location.reload();
                    } else {
                        notification(data);
                        btn.button('reset');
                    }
                }
            });
        } else {
            $(this).closest('tr').removeClass('has-success').addClass('has-error');
        }
    });

    $(".deletePartnerCityCommissionCall").click(function() {
        $('#partnerCityCommissionModal').modal();
        var url = $(this).attr('data-url');
        $('#deletePartnerCityCommission').prop('href', url);
    });

    /************TAB*******************/
    var hash = window.location.hash;
    hash && $('ul.nav a[href="' + hash + '"]').tab('show');

    $('.nav-tabs a').click(function (e) {
        window.location.hash = this.hash;
    });

    $('#business_model').trigger('change');

});

$('#business_model').change(function () {
    if (this.value == 2) {
        $('#is_deducted_commission').closest('.form-group').hide();
    } else {
        $('#is_deducted_commission').closest('.form-group').show();
    }
});

state('addPartner', function(e) {
    e.preventDefault();
    var validate = $('#partner').validate();

    if ($('#partner').valid()) {
        var btn = $('#addPartner');

        btn.button('loading');

        var obj = $('#partner').serializeArray();
        var attachment = $('#img_file');
        obj.push({name: 'image', value: $('#newLogo').attr('value')});
        $.ajax({
            type: "POST",
            url: GLOBAL_BASE_PATH + 'partners/ajax-add-partner',
            data: obj,
            dataType: "json",
            success: function(data) {
                if(data.status == 'success'){
                    window.location.href = GLOBAL_BASE_PATH + 'partners';
                } else {
                    notification(data);
                    btn.button('reset');
                }
            }
        });
    } else {
        validate.focusInvalid();
    }
});


state('savePartner', function(e) {
    e.preventDefault();
    var validate = $('#partner').validate();

    if ($('#partner').valid()) {
        var btn = $('#savePartner');
        btn.button('loading');


        var obj = $('#partner').serializeArray();
        var gid = $('#partner-number').val();
        obj.push({name: 'gid', value: gid});
        obj.push({name: 'image', value: $('#newLogo').attr('value')});
        obj.push({name: 'old_image', value: $('#logo').attr('value')});

        $.ajax({
            type: "POST",
            url: GLOBAL_BASE_PATH + 'partners/ajax-save-partner',
            data: obj,
            dataType: "json",
            success: function(data) {
                if(data.status == 'success'){
                    $('#logo').attr('value', data.newLogo);
                    notification(data);
                } else {
                    notification(data);
                }
                btn.button('reset');
            }
        });
    } else {
        validate.focusInvalid();
    }
});

state('deletePartnerTrue', function() {

    var obj = $('#partner').serializeArray();
    var gid = $('#partner-number').val();
    obj.push({name: 'gid', value: gid});
    //var gid = $('#partner-number').val();

    $.ajax({
       type: "POST",
       url: GLOBAL_BASE_PATH + 'partners/ajax-delete-partner',
       data: obj,
       dataType: "json",
       cache: false,
       success: function(data){
           if (data.status == 'success') {
               window.location.href = GLOBAL_BASE_PATH + 'partners';
           }
           else{
               notification(data);
           }
       }
   });
});

function sendEmailToPartner() {
    var gid = $("#partner-number").val();
    var data = {gid: gid};

    var btn = $('#sendEmail');
    btn.button('sending');



    $.ajax({
       type: "POST",
       url: GLOBAL_BASE_PATH + 'partners/ajax-send-email',
       data: data,
       dataType: "json",
       cache: false,
       success: function(data){
           notification(data);
           btn.button('reset');
       }
   });
}

function deleteLogo() {
    $('#preview').remove();
    $('#newLogo').val('');
    $('#logo').val('');
    $('#uploadLogo').parent().after($.buttons.logoButton).remove();
}

$('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
    tab_name = $(e.target).attr("id");
    if (tab_name) {
        $(".page-actions .btn").hide();
        $(".page-actions .btn." + tab_name + "-btn").show();

        if (tab_name == 'edit-account-tab') {
            $(".page-actions").hide();
            $('#partner-account-form .page-actions').show();
        } else {
            $(".page-actions").show();
        }
    }
});

/*************************** External Accounts *************************/

// add external account
$(document).on('click', '#add-account, .partner-account-edit', function(e){
    var container = $('#edit-account');
    var tab       = $('#edit-account-tab');

    var data;
    if ($(e.target).hasClass('partner-account-edit')) {
        $('#edit-account-tab').text('Edit Account');
        data = {
            'id': $(e.target).data('id'),
            'partner_id' : GENERAL_PARTNER_ID
        };
    } else {
        $('#edit-account-tab').text('Add Account');
        data = {'partner_id' : $(this).data('partner-id')};
    }

    $.ajax({
        type: "POST",
        url: GENERAL_EXTERNAL_ACCOUNT_EDIT,
        data: data,
        dataType: "html",
        success: function (data) {
            container.html(data);
            tab.parent().css('display', 'block');
            tab.trigger('click');
            initUserAccountValidation();
        }
    });
});

// save partner account
$(document).on('click', '#save-partner-account', function(e) {
    $(this).button('loading');

    if($('#partner-account-form').valid()) {
        var data = $('#partner-account-form').serializeArray();
        data.push(
            {
                name: 'partnerId',
                value: $(this).data('partner-id')
            }
        );
        savePartnerAccountData(data);
    } else {
        $(this).button('reset');
    }
});

// cancel modify account
$(document).on('click', '#cancel-partner-account', function(){
    $(this).button('loading');
    window.location.href = $(this).attr('href') + '#commission-part';
    location.reload();

    return false;
});

// archive partner account
$(document).on('click', '.partner-account-archive', function() {
    var btn = $(this);
    var id  = btn.data('id');
    var partnerId;

    if ($('#add-account').data('partner-id') == undefined) {
        partnerId = GENERAL_PARTNER_ID;
    } else {
        partnerId = $('#add-account').data('partner-id');
    }

    btn.button('loading');

    $.ajax({
        type: "POST",
        url: DATATABLE_PARTNER_ACCOUNTS_ARCHIVE,
        data: {
            'id': id,
            'partnerId': partnerId
        },
        dataType: "json",
        success: function (data) {
            if(data.url != '' && data.status == 'success'){
                window.location.href = data.url + '#commission-part';
                location.reload();
            } else {
                notification(data);
            }
            btn.button('reset');
        }
    });
});

$('#datatable_partner_account_container').removeClass('hidden');
if (window.fTable) {
    fTable.fnReloadAjax();
} else {
    fTable = $('#datatable_partner_account_info').dataTable({
        bAutoWidth: false,
        bFilter: true,
        bInfo: false,
        bPaginate: true,
        bProcessing: true,
        bServerSide: true,
        bStateSave: true,
        iDisplayLength: 25,
        sPaginationType: "bootstrap",
        sAjaxSource: DATATABLE_PARTNER_ACCOUNTS_AJAX_SOURCE,
        sDom: 'l<"enabled">frti<"bottom"p><"clear">',
        aoColumns: [
            {
                name: "isDefault",
                "sClass" : "text-center",
                "bSortable": true
            }, {
                name: "name",
                "bSortable": true
            }, {
                name: "type",
                "bSortable": true
            }, {
                name: "fullLegalName",
                "bSortable": true
            }, {
                name: "addresses",
                "bSortable": false
            }, {
                name: "countryId",
                "bSortable": false
            }, {
                name: "iban",
                "bSortable": true
            }, {
                name: "swft",
                "bSortable": true
            }, {
                "name": "edit",
                "bSortable": false,
                "bSearchable": false,
                "sClass" : "text-center",
                "sWidth" : "15%"
            }
        ],
        "aoColumnDefs": [
            {
                "aTargets": [8],
                "fnCreatedCell": function (nTd, sData, oData, iRow, iCol) {
                    var $cell = $(nTd);
                    var value = $cell.text();
                    if (value !== "0") {
                        $cell.html('<a href="javascript:void(0)" class="btn btn-xs btn-primary partner-account-edit" data-id="' + value + '">Manage</a><a href="javascript:void(0)" class="btn btn-xs btn-danger partner-account-archive" data-id="' + value + '">Archive</a>');
                    } else {
                        $cell.html('');
                    }
                }
            }
        ]
    });

    $("#datatable_partner_account_info_wrapper div.enabled").html($('#status-switch-account').html());
    $('#status-switch-account').remove();

    $(document).on('click', '#datatable_partner_account_info_wrapper .fn-buttons a', function(e) {
        e.preventDefault();

        $('.fn-buttons a').removeClass('active');
        $(this).addClass('active');

        switch ($(this).attr('data-status')) {
            case 'all':
                $("#show_status_account").attr('value', 1); break;
            case 'archived':
                $("#show_status_account").attr('value', 2); break;
        }

        fTable.fnSettings().aoServerParams.push({
            "fn": function (aoData) {
                aoData.push({
                    "name": "all",
                    "value":  $("#show_status_account").attr('value')
                });
            }
        });

        fTable.fnGetData().length;
        fTable.fnDraw();
    });
}

/**
 * Save partner account data
 *
 * @param data
 */
function savePartnerAccountData(data) {
    $.ajax({
        type: "POST",
        url: GLOBAL_PARTNER_ACCOUNT_SAVE,
        data: data,
        dataType: "json",
        success: function(data) {
            if(data.url != '' && data.status == 'success'){
                window.location.href = data.url + '#commission-part';
                location.reload();
            } else {
                notification(data);
            }

            $("#partner-account-form #save-partner-account").button('reset');
        }
    });
}