/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global define*/
define(
    [
        'jquery',
        'mageUtils',
        'Codaone_LogitrailModule/js/model/shipping-rates-validation-rules',
        'mage/translate'
    ],
    function ($, utils, validationRules, $t) {
        'use strict';

        return {
            validationErrors: [],
            validate: function (address) {
                var self = this;

                this.validationErrors = [];
                $.each(validationRules.getRules(), function (field, rule) {
                    if (rule.required && utils.isEmpty(address[field])) {
                        var message = $t('Field ') + field + $t(' is required.');

                        self.validationErrors.push(message);
                    }
                });

                if(this.validationErrors.length == 0) {
                    //TODO: Fix racing condition with form saving to db
                    window.setTimeout(function()  {
                        Logitrail.checkout(window.logitrailConfig);
                        Logitrail.currentCheckout.frame.onload = function(){
                            jQuery('#logitrailLoader').hide();
                        };
                    }, 1000);
                }
                return !Boolean(this.validationErrors.length);
            }
        };
    }
);
