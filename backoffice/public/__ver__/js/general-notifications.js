$(function () {
    pullAllNotifications();
});

$(document).on('click','.notifications-part',function(){
    var url = $(this).attr('data-url');
    if (url == '') {
        return false;
    }
    var win = window.open(url, '_blank');
    if(win){
        //Browser has allowed it to be opened
        win.focus();
    }
});
$(document).on('click','.notifications-part a',function(event){
  event.stopPropagation();
});

$(document).on('click', '.archive-notification', function(event) {
    event.preventDefault();
    event.stopPropagation();
    var notificationId = $(this).attr('data-id');
    archiveNotification(notificationId);
});

function notificationsCount(cnt)
{
    $('.notifications-count').text(cnt);
    if (parseInt(cnt) == 0) {
        $('.notifications-count').hide();
    } else {
        $('.notifications-count').show();
    }
}

function fillNotifications(notifications)
{
    $('li.notification-li, li.notifications-divider-li').remove();
    var $ulNotifications = $('.ul-notifications');
    var cnt = 0;
    $.each(notifications, function(index, value) {
        var additionalClass = (cnt++ >= 4) ? ' hidden' : '';
        var idTag = ' data-id="' + value.id + '"';
        var html =
            '<li' + idTag +' class="notification-li' + additionalClass +'">' +
                '<div class="notifications-part pull-left" data-url="' + value.url +'">' +
                    value.message +
                '</div>' +
                '<div class="actions-part pull-left text-center">' +
                    '<a href="#"  class="archive-notification" data-id="' + value.id +'"><i class="glyphicon glyphicon-remove-circle"></i></a>';
        html += '</div>' +
                '<div class="clearfix"></div>' +
            '</li>' +
            '<li class="divider notifications-divider-li '+ additionalClass +'" '+ idTag +'></li>';
        $('.see-all-notifcations').before(html);
    })
}

function pullAllNotifications()
{
    $.ajax({
        url: '/notification/pull',
        type: "POST",
        data: {},
        cache: false,
        success: function (data) {
              if (typeof data.data != 'undefined') {
                  notificationsCount(data.data.length);
                  fillNotifications(data.data);
              }
        }
    });
}


function removeNotificationFromList(notificationId)
{
    $('li[data-id="' + notificationId + '"]').fadeOut(500,function(){
        $('li[data-id="' + notificationId + '"]').remove();
        pullAllNotifications();
    });
}

function archiveNotification(notificationId)
{
    $.ajax({
        url: '/notification/archive',
        type: "POST",
        data: {id: notificationId},
        cache: false,
        success: function (data) {
            if (data.status == 'success') {
                removeNotificationFromList(notificationId);
                //if we are on notifications page
                if ($('#notification-status-switcher').length > 0) {
                    $('#notification-status-switcher a.active').trigger('click');
                }
            }
        }
    });
}

