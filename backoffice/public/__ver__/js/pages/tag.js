var gTable;

function previewTag($elem, name, labelClass)
{
    $elem
        .html('<span class="glyphicon glyphicon-tag"></span> ' + name)
        .attr('class','label ' + labelClass);
}

$(function() {
    var $tagStyle = $('#tag-style');

    /** Datatable configuration */
    if (jQuery().dataTable) {
        gTable = $('#datatable_tags').dataTable({
            bAutoWidth: false,
            bFilter: true,
            bInfo: true,
            bPaginate: true,
            bProcessing: true,
            bServerSide: true,
            bStateSave: true,
            iDisplayLength: 25,
            sPaginationType: "bootstrap",
            sAjaxSource: DATATABLE_AJAX_SOURCE,
            sDom: 'l<"enabled">frti<"bottom"p><"clear">',
            aoColumns:[
                {
                    name: "name",
                    class: "editable-tag-label"
                }, {
                    name: "count",
                }, {
                    name: "link",
                    sortable: false,
                    searchable: false,
                    width: '1'
                }, {
                    name: "edit",
                    sortable: false,
                    searchable: false,
                    width: '1'
                }, {
                    name: "delete",
                    sortable: false,
                    searchable: false,
                    width: '1'
                }
            ],
            aaSorting: [[1, 'asc']]
        });
    }

    $('#new-tag-name').keyup(function(){
        var newTagName = $(this).val();
        if (!newTagName.length) {
            $('#add-tag').fadeOut();
        } else {
            $('#add-tag').fadeIn();
        }
        var labelClass = $tagStyle.val();
        previewTag($('#new-tag-preview'), newTagName, labelClass);
    });

    $tagStyle.change(function(){
        var newTagName = $('#new-tag-name').val();
        var labelClass = $(this).val();
        previewTag($('#new-tag-preview'), newTagName, labelClass);
    });

    $('#open-tag-modal').click(function(event) {
        var $modal = $('#add-edit-tag-modal');
        event.preventDefault();
        $('#new-tag-name').val('');
        $('#new-tag-preview').text('');
        $tagStyle.val(GLOBAL_DEFAULT_LABEL);
        $modal.attr('data-id',0);
        $modal.find('.modal-title').text('Add Tag');
        $modal.find('#add-tag').text('Add Tag').hide();
        $modal.modal('show');
    });

    $tagStyle.val(GLOBAL_DEFAULT_LABEL);
});

$(document).on('click', '#add-tag',  function(event) {
    event.preventDefault();
    $.ajax({
        url: GLOBAL_EDIT_TAG,
        type: "POST",
        data: {
            'tag-name' : $('#new-tag-name').val(),
            'style'    : $('#tag-style').val(),
            'tag-id'   : $('#add-edit-tag-modal').attr('data-id')
        },
        dataType: "json",
        success: function(data){
            notification(data);
            if (data.status == 'success') {
                gTable.fnDraw();
                $('#add-edit-tag-modal').modal('hide');
            }
        }
    });
});

$(document).on('click', '.btn-edit-tag', function() {
    var tagId    = $(this).attr('data-id');
    var tagStyle = $(this).attr('data-style');
    var tagName  = $(this).attr('data-text');
    var $nameField = $('#new-tag-name');
    var $modal = $('#add-edit-tag-modal');

    $nameField.val(tagName);
    $modal.attr('data-id',tagId);
    $modal.find('#tag-style').val(tagStyle);
    $nameField.trigger('keyup');
    $modal.find('.modal-title').text('Edit Tag');
    $modal.find('#add-tag').text('Edit Tag').show();
    $modal.modal('show');
});

$(document).on("mouseover", "#datatable_tags tbody tr", function() {
   $(this).find('.remove-tag').show();
});

$(document).on("mouseleave", "#datatable_tags tbody tr", function() {
    $(this).find('.remove-tag').hide();
});

$('#datatable_tags').on('click','.btn-delete-tag',function() {
    var tagId = $(this).attr('data-id');
    $('#delete-tag-button').attr('data-id',tagId);
    $('#delete-tag-modal').modal('show');

});

$(document).on('click','#delete-tag-button',function() {
    var tagId = $(this).attr('data-id');
    $.ajax({
        url: GLOBAL_DELETE_TAG,
        type: "POST",
        data: {
            'id' : tagId
        },
        dataType: "json",
        success: function(data){
            notification(data);
            gTable.fnDraw();
            $('#delete-tag-modal').modal('hide');
        }
    });
});