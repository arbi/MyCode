function disableChargeAuthorizationFormInputs() {
    var $expirationMonth = $('#cc-expiration-month').val();
    var $expirationYear = $('#cc-expiration-year').val();
    var $securityCode = $('#cc-security-code').val();
    var $billingAddress = $('#billing-address').val();

    var $expirationDate = $expirationMonth + ' / ' + $expirationYear;

    $('#cc-expiration-date-for-print').html($expirationDate);
    $('#cc-security-code-for-print').html($securityCode);
    $('#billing-address-for-print').html($billingAddress);
}

function validateForm() {
    var $expirationMonth = $('#cc-expiration-month').val();
    var $expirationYear = $('#cc-expiration-year').val();
    var $securityCode = $('#cc-security-code').val();
    var $billingAddress = $('#billing-address').val();

    if ($expirationMonth != 0 && $expirationYear != 0 && $securityCode && $billingAddress) {
        document.getElementById('print-ccca-form').disabled = false;
    } else {
        document.getElementById('print-ccca-form').disabled = true;
    }
}

function printCccaForm() {
    disableChargeAuthorizationFormInputs();
    window.print();
}
