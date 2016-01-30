$(function () {
   $('#print-permit').click(function(){
       window.print();
   })
    if (SHOW_GUEST_BALANCE_IS_NEGATIVE_WARNING_MESSAGE == 1) {
        notification(
            {
                'status' : 'warning',
                'msg'    : 'The balance is NEGATIVE. Are you sure to give parking permit to the customer?'
            }
        );
    }
});
