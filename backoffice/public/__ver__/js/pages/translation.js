$(function() {
    $("#btn_search_translation").click(function() {
        if (window.gTable) {
            gTable.fnReloadAjax();
        } else {
            /** Datatable configuration */
            gTable = $('#datatable_translation').dataTable({
                bAutoWidth: false,
                bFilter: false,
                bInfo: true,
                bPaginate: true,
                bProcessing: true,
                bServerSide: false,
                bStateSave: true,
                iDisplayLength: 25,
                sPaginationType: "bootstrap",
                sAjaxSource: AJAX_SOURCE_URL,
                aoColumns:[
                    {
                        name: "id",
                        sortable: true,
                        width: "5%"
                    },
                    {
                        name: "type",
                        sortable: true,
                        width: "25%"
                    },
                    {
                        name: "content",
                        sortable: true
                    },
                    {
                        name: "view",
                        sortable: false,
                        width: "1%"
                    }
                ],
                aoColumnDefs: [
                    {
                        aTargets: [3],
                        fnCreatedCell: function (nTd, sData, oData, iRow, iCol) {
                            var $cell = $(nTd);
                            var value = $cell.text();
                            $cell.html('<a href="/translation/view/' + value + '" target="_blank" class="btn btn-xs btn-primary pull-left" data-html-content="View"></a>');
                        }
                    }
                ],
                fnServerParams: function ( aoData ) {
                    additionalParams = $("#search-translation").serializeObject();
                    jQuery.each(additionalParams, function(index, val) {
                        var myObject = {
                            name:  index,
                            value: val
                        };
                        aoData.push( myObject );
                    });
                }
            });

            if ( $('#translation_table_container').hasClass('hidden') ){
                $('#translation_table_container').removeClass('hidden');
            }
        }
    });

	$('#search-translation').keypress(function (e) {
		if (e.which == 13) {
			$( "#btn_search_translation" ).trigger('click');
            return false;
		}
	});

    if($('#srch_txt').length > 0 && $('#category').length > 0 ){
        $( "#srch_txt" ).autocomplete({
            source: function(request, response) {
                if($('#category').val() > 1) {
                    $.ajax({
                    url: GLOBAL_SEARCH_AUTOCOMPLETE_URL,
                    data: {txt: $("#srch_txt").val(), type:$('#category').val()},
                    dataType: "json",
                    type: "POST",
                    success: function( data ) {
                        var obj = [];
                        if(data && data.status == 'success'){

                            for(var row in data.result){
                                 var item = data.result[row];
                                 var new_obj = {};
                                 new_obj.value = item.name;// + ' ('+item.type_view+')';
                                 new_obj.id    = item.id;
                                 obj.push(new_obj);
                            }
                            response(obj);
                        } else {
                            notification(data)
                        }
                    }
                    })
                }
            },
            max:10,
            minLength: 1,
            autoFocus: true,
            select: function( event, ui ) {
                if(ui.item !== 'undefined'){
                    var trans_id = ui.item.id;
                    $('#id_translation').val(trans_id);
                    $( "#btn_search_translation" ).trigger('click');
                }
            }
        });
    }
});

function changeType(val){
    if (val == 1) {
        $('#add_new_textline').css({'display':'block'});
        $('div.un_type').show();
        $('div.description').show();
    } else {
        $('#add_new_textline').css({'display':'none'});
        $('div.un_type').hide();
        $('div.description').hide();
    }

    if (val == 3) {
        $('div.product_type').show();
    } else {
        $('div.product_type').hide();
    }

    if (val == 2) {
        $('div.srch_txt').removeClass('col-md-6 col-md-8 col-md-9 col-md-7');
        $('div.srch_txt').addClass('col-md-9');
    } else if (val == 3) {
        $('div.srch_txt').removeClass('col-md-6 col-md-8 col-md-9 col-md-7');
        $('div.srch_txt').addClass('col-md-7');
    } else {
        $('div.srch_txt').removeClass('col-md-6 col-md-8 col-md-9 col-md-7');
        $('div.srch_txt').addClass('col-md-6');
    }

    $('#srch_txt').val('');
    $('#id_translation').val('');
}

function changeIndexLang(val){
    if (val == 'en'){
        $('.trans_srch_checkbox').css({'display':'block'});
    } else {
        $('.trans_srch_checkbox').css({'display':'none'});
    }
}

state('publish_translation', function() {
    var btn = $('#publish_translation');
    btn.button('loading');
    tinymce.triggerSave();
    var obj = $('#translation_form').serialize();
    $.ajax({
        type: "POST",
        url: GLOBAL_SAVE,
        data: obj,
        dataType: "json",
        success: function(data) {
            if(data.duplicate) {
                $('#duplicateModal').modal('show');
            } else {
                if(data.status == 'success'){
                    if(parseInt(data.id) > 0){
                        window.location.href = GLOBAL_BASE_PATH + 'translation/view/u-' + data.id + '-en';
                    } else {
                        location.reload();
                    }
                } else {
                    notification(data);
                }
            }
            btn.button('reset');
        }
    });
});

if ( $('#add-textline-page').length) {
    addTextlinePage = $('#add-textline-page').selectize({
        plugins: ['remove_button'],
        preload: true,
        create: false,
        valueField: 'id',
        labelField: 'name',
        searchField: 'name',
        options: GLOBAL_PAGE_TYPES,
        render: {

        },
        onChange: function(value) {

        }
    });
}
