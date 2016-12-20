/*jshint browser:true jquery:true*/
/*global alert*/
define(
    [
        'Magento_Checkout/js/model/quote',
        'jquery'
    ],
    function (quote, $) {
        "use strict";

        return function (shippingMethod) {
            if(shippingMethod !== null) {
                if (shippingMethod.carrier_code == 'logitrail') {
                    $("#shipping-method-buttons-container").find(".button").prop("disabled", true);
                    $("#logitrailHolder").show();
                } else {
                    $("#shipping-method-buttons-container").find(".button").prop("disabled", false);
                    $("#logitrailHolder").hide();
                }
            }
            quote.shippingMethod(shippingMethod)
        }
    }
);
