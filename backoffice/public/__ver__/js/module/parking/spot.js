$(function() {


    if (jQuery().dataTable) {
        gTable = $('#datatable_spots').dataTable({
            bFilter: true,
            bInfo: true,
            bServerSide: false,
            bProcessing: false,
            bPaginate: true,
            bAutoWidth: true,
            bStateSave: true,
            iDisplayLength: 25,
            sAjaxSource: null,
            sPaginationType: "bootstrap",
            aaData: aaData,
            aaSorting: [[0, "desc"]],
            "aoColumns":[
                {
                    name: "unit"

                }, {
                    name: "price"
                }, {
                    name: "permit_id",
                    class: 'parking_permit_id',
                    searchable: false,
                    sortable: false
                }, {
                    name: "buttons",
                    sortable: false,
                    searchable: false,
                    width : "1%"
                }
            ]
        });
    }


    $form = $('#parking-spot');
    $("#save-button").click(function() {
        var validate = $form.validate();
        if ($form.valid()) {
            saveChanges();
        } else {
            validate.focusInvalid();
        }
    });
});

function saveChanges()
{
    var $btn = $('#save-button');
    $btn.button('loading');
    var obj = $('#parking-spot').serializeArray();

    $.ajax({
        type: "POST",
        url: SAVE_DATA,
        data: obj,
        dataType: "json",
        success: function(data) {
            if(typeof data.url != 'undefined' && data.url != '' && data.status == 'success'){
                window.location.href = data.url;
            } else {
                notification(data);
            }
            $btn.button('reset');
        }
    });
}

$(document).on('click','.parking_permit_id',function(){
      var $inputGroup  = $(this).find('.input-group');
      if (!$inputGroup.is(':visible')) {
          var $span = $(this).find('span');
          $span.hide();
          var $input = $inputGroup.find('input');
          $input.val($span.text());
          $inputGroup.fadeIn();
      }
});

$(document).on('click','.parking_permit_id input', function(event) {
    event.stopPropagation();
});

$(document).on('keyup','.parking_permit_id input', function(event) {
    var $tr = $(this).closest('tr');
    var $saveButton = $tr.find('a.btn');
    $saveButton.fadeIn();
});

$(document).on('click','.parking_permit_id label', function(event) {
    event.stopPropagation();
    var $tr = $(this).closest('tr');
    var $saveButton = $tr.find('.save_parking_permit');
    var $inputGroup = $(this).closest('.input-group');
    var $span = $(this).closest('.parking_permit_id').find('span');
    $saveButton.fadeOut();
    $inputGroup.hide();
    $span.fadeIn();
});

$(document).on('click','.save_parking_permit', function(event) {
    event.preventDefault();
    var $self = $(this);
    var spotId = $self.attr('data-spot-id');
    var $tr = $self.closest('tr');
    var $span = $tr.find('.parking_permit_id span');
    var newPermitId = $tr.find('.input-group input').val();
    var $inputGroup = $tr.find('.input-group');
    $.ajax({
        type: "POST",
        url: SAVE_PERMIT_ID,
        data: {id:spotId, permit_id:newPermitId},
        dataType: "json",
        success: function(data) {
                notification(data);
            if (data.status == 'success') {
                $self.fadeOut();
                $span.text(newPermitId);
                $inputGroup.hide();
                $span.fadeIn();
            }
        }
    });
});

$('.delete-spot').click(function () {
    var spotId = $('#spot-id').val();
    $.ajax({
        type: "POST",
        url: GET_SPOT_RESERVATIONS,
        data: { spotId: spotId },
        dataType: "json",
        success: function(data) {
            if (data.status == 'success') {
                if (parseInt(data.hasReservations)) {
                    if (typeof window.savedHref == 'undefined') {
                        window.savedHref = $( "a#delete-spot-btn" ).attr( "href");
                    }
                    
                    $( "a#delete-spot-btn" ).removeAttr( "href");
                    $( "a#delete-spot-btn" ).attr( "disabled", true);
                    $('.res-links').html(data.links);
                } else {
                    $('.res-links').html('');
                    $( "a#delete-spot-btn" ).attr( "disabled", false);
                    if (typeof window.savedHref != 'undefined') {
                        $( "a#delete-spot-btn" ).attr("href", window.savedHref);
                    }
                }
                $('#delete-modal').modal('show');
            }
        }
    });
});
