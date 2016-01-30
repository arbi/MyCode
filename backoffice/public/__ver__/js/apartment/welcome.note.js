$(function(){
    $('[data-toggle="tooltip"]').tooltip()
});

function setPaperType($type) {
    if ($type == 'a4') {
        $('#welcomeNote').removeClass('letter').addClass('a4');
    } else {
        $('#welcomeNote').removeClass('a4').addClass('letter');
    }
    $('#printModal').modal('hide');
    window.print();
}