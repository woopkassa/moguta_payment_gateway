var paymentId = $('#payment-id').text();
var orderId = $('#order-id').text();
var orderNumber = $('.payment-form-block strong').html();
var wooppayPluginId = $('#wooppay_id').text();

if (paymentId === wooppayPluginId) {
    var paymentForm = "<div class='l-col min-0--12'>\
    <button class='green-btn big-btn default-btn' id='wooppay-submit' type='submit' style='vertical-align: middle'>Перейти к оплате</button>\
    </div>";
    $('.payment-form-block').append(paymentForm);
}

$("#wooppay-submit").click( function(){
    $.ajax({
        type: "POST",
        async: false,
        url: mgBaseDir + "/ajaxrequest",
        dataType: 'json',
        data: {
            mguniqueurl: "action/getPayLink",
            pluginHandler: "wooppay-payment",
            paymentId: paymentId,
            orderId: orderId,
            number: orderNumber,
            mgBaseDir: mgBaseDir,
        },
        cache: false,
        success: function (response) {
            if (response.status != 'error') {
                console.log(response)
                if (response.data.result != null) {
                    window.location.href = response.data.result;
                }
            }
        }
    })
});