/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/shipping-rates-validator',
        'Magento_Checkout/js/model/shipping-rates-validation-rules',
        'Codaone_LogitrailModule/js/model/shipping-rates-validator',
        'Codaone_LogitrailModule/js/model/shipping-rates-validation-rules'
    ],
    function (
        Component,
        defaultShippingRatesValidator,
        defaultShippingRatesValidationRules,
        logitrailShippingRatesValidator,
        logitrailShippingRatesValidationRules
    ) {
        'use strict';
        defaultShippingRatesValidator.registerValidator('logitrail', logitrailShippingRatesValidator);
        defaultShippingRatesValidationRules.registerRules('logitrail', logitrailShippingRatesValidationRules);

        return Component;
    }
);
