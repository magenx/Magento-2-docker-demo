/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'uiComponent',
    'Magento_PaymentServicesPaypal/js/view/payment/actions/get-sdk-params'
], function (Component, getSdkParams) {
    'use strict';

    return Component.extend({
        defaults: {
            sdkParamsKey: 'paypal',
            sdkParams: [],
            cacheTtl: 30000
        },

        /**
         * Get sdk params
         *
         * @return {Promise<Object>}
         */
        getSdkParams: function () {
            return getSdkParams(this.cacheTtl)
                .then(function (sdkParams) {
                    this.sdkParams = sdkParams[this.sdkParamsKey];
                }.bind(this));
        }
    });
});
