$(function() {
    $('.carousel').carousel({
        pause: false,
        wrap: false,
        interval: 2000
    });
    $('.carousel').on('slide.bs.carousel', function () {
        var bg = $('.carousel-inner > div.item.active').css('background-image');
        bg = bg.replace('url(','').replace(')','');
        $('#jobs-cover').css('background-image', 'url('+ bg +')');
    });
});