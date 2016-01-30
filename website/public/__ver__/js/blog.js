function socialButtons(){
    var greybar   = $('#greybar');
    var socialBar = $('#socialButtonsFixBlock');

    if ($(window).scrollTop() > (greybar.offset().top + greybar.height())) {
        socialBar.addClass('show');
    } else {
        socialBar.removeClass('show');
    }
}
window.onscroll = socialButtons;
window.onload   = socialButtons;