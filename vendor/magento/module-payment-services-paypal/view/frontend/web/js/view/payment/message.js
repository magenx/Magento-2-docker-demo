/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'uiComponent',
    'scriptLoader'
], function ($, Component, loadSdkScript) {
    'use strict';

    return Component.extend({
        defaults: {
            sdkNamespace: 'paypal',
            renderContainer: null,
            amountAttribute: 'data-pp-amount',
            amount: null
        },

        /**
         * @inheritdoc
         */
        initialize: function () {
            this._super();
            this.sdkLoaded = loadSdkScript(this.scriptParams, this.sdkNamespace);

            return this;
        },

        /**
         * Update amount
         *
         * @param {*} amount
         */
        updateAmount: function (amount) {
            this.amount = amount;
            $(this.renderContainer).attr(this.amountAttribute, this.amount);
        },

        /**
         * Render message
         *
         * @return {Promise}
         */
        render: function () {
            return this.sdkLoaded.then(function (sdkScript) {
                sdkScript.Messages({
                    amount: parseFloat(this.amount).toFixed(2),
                    placement: this.placement,
                    style: this.styles
                })
                .render(this.renderContainer);
            }.bind(this)).catch(function (exception) {
                console.log('Error: Failed to load PayPal SDK script!');
                console.log(exception.message);
            });
        }
    });
});
