define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery'
    ],
    function (Component, $) {
        'use strict';
        $(document).ready(function() {
            var settings = {
                "url": "/test/page/createiframe",
                "method": "GET",
                "timeout": 0,
                "withCredentials": 0,
              };
              $.ajax(settings).done(function (response) {
                    $('#qisttpayifram').attr('src',response.result.iframe_url);
              });
              window.addEventListener('message', function(e) {
                // Get the sent data
                const data = e.data;
                var decoded = null;
                try {
                    decoded = JSON.parse(data);                          
                    var flag = decoded.hasOwnProperty('message');
                    var successStatus = decoded.success;
                    if(flag == true && successStatus == true){
                        jQuery( "#qp-submit-button" ).trigger('click');
                        ///form Submit
                    }
                } catch(e){
                    return;
                }    
            });
        });
        return Component.extend({
            defaults: {
                template: 'Qisst_Magento24/payment/qppayment'
            },
            getMailingAddress: function () {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            },
            getInstructions: function () {
                return window.checkoutConfig.payment.instructions[this.item.method];
            },
        });
    }
);
