/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global define*/
define(
    [],
    function () {
        'use strict';

        return {
            getRules: function () {
                return {
                    'firstname': {
                        'required': true
                    },
                    'lastname': {
                        'required': true
                    },
                    'street': {
                        'required': true
                    },
                    'postcode': {
                        'required': true
                    },
                    'city': {
                        'required': true
                    },
                    'country_id': {
                        'required': true
                    }
                };
            }
        };
    }
);
