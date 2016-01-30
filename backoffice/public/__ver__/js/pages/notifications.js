$(function () {

    $('.fn-buttons a').click(function(event) {
        $('.fn-buttons a').removeClass('active');
        $(this).addClass('active');
        getSearchedNotifications();
    });

    $('#sender').change(function() {
        getSearchedNotifications();
    });

    function getSearchedNotifications() {
        var selectedStatus = $('.fn-buttons a.active').attr('data-status');
       $('#active_archived').val(selectedStatus);
        if (typeof gTable != 'undefined') {
            gTable.fnDraw();
        } else {
            /** Datatable configuration */
            gTable = $('#datatable_notifications').dataTable({
                bAutoWidth: false,
                bFilter: true,
                bInfo: true,
                bPaginate: true,
                bProcessing: true,
                bServerSide: true,
                bStateSave: true,
                iDisplayLength: 25,
                sPaginationType: "bootstrap",
                aaSorting: [[0, "desc"], [2, "asc"]],
                aoColumns: [
                    {
                        name: "date",
                        sortable: false,
                        width: '1',
                        class: 'nowrap'
                    }, {
                        name: "from",
                        sortable: false
                    }, {
                        name: "message",
                        sortable: false,
                        class: 'link-notification'
                    }, {
                        name: "actions",
                        sortable: false,
                        width: '7%',
                        class: 'text-center-custom'

                    }
                ],
                ajax: {
                    url: SEARCH_URL,
                    data: function (d) {
                        additionalParams = $("#search-notifications").serializeObject();
                        jQuery.each(additionalParams, function (index, val) {
                            d[index] = val;
                        });
                    }
                }
            });
            if ($('#datatable_notifications').hasClass('hidden')) {
                $('#datatable_notifications').removeClass('hidden');
            }
            gTable.fnDraw();
        }
    }


     getSearchedNotifications();
  $(document).on('click', '.archive-not', function (event) {
      event.preventDefault();
      $(this).text('Loading...');
      var notificationId = $(this).attr('data-id');
      $.ajax({
          url: ARCHIVE_URL,
          type: "POST",
          data: {id: notificationId},
          cache: false,
          success: function (data) {
                notification(data);
              getSearchedNotifications();
              pullAllNotifications();
          }
      });
  });

    $(document).on('click', '.delete-not', function (event) {
        event.preventDefault();
        $(this).text('Loading...');
        var notificationId = $(this).attr('data-id');
        $.ajax({
            url: DELETE_URL,
            type: "POST",
            data: {id: notificationId},
            cache: false,
            success: function (data) {
                notification(data);
                getSearchedNotifications();
            }
        });
    });

    $(document).on('click','td.link-notification',function(){
        var $tr = $(this).closest('tr');
        var $linkButton = $tr.find('.btn-notification-link');

        var url = $linkButton.attr('href');
        if (url == '') {
            return false;
        }
        var win = window.open(url, '_blank');
        if(win){
            //Browser has allowed it to be opened
            win.focus();
        }
    });
    $(document).on('click','td.link-notification a',function(event){
        event.stopPropagation();
    });


});

