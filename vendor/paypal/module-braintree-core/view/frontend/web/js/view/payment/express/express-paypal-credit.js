/**
 * Express Paypal Credit button component
 */
define([
    'jquery',
    'underscore',
    'uiComponent',
    'mage/url',
    'domReady!'
], function ($, _, Component, url) {
    'use strict';

    const config = _.get(window.checkoutConfig.payment, 'braintree_paypal_credit', {});

    return Component.extend({
        defaults: {
            template: 'PayPal_Braintree/express/express-paypal-credit',
            isActive: _.get(config, 'isActive', false),
            checkoutCurrency: window.checkoutConfig.quoteData.base_currency_code,
            checkoutAmount: window.checkoutConfig.quoteData.base_grand_total,
            checkoutLocale: _.get(config, 'locale', null),
            buttonLabel: _.get(config, ['style', 'label'], null),
            buttonColor: _.get(config, ['style', 'color'], null),
            buttonShape: _.get(config, ['style', 'shape'], null),
            actionSuccess: url.build('braintree/paypal/review/')
        },

        /**
         * Initializes regular properties of instance.
         *
         * @returns {Object} Chainable.
         */
        initConfig: function () {
            this._super();

            return this;
        },

        /**
         * Is the payment method active.
         *
         * @return {boolean}
         */
        isMethodActive: function () {
            return this.isActive;
        },

        /**
         * Is Billing address required.
         *
         * @return {string}
         */
        getIsRequiredBillingAddress: function () {
            return _.get(config, 'isRequiredBillingAddress', '0') === '0' ? '' : 'true';
        },

        /**
         * Get the merchant's name config.
         *
         * @return {string}
         */
        getMerchantName: function () {
            return _.get(config, 'merchantName', '');
        }
    });
});
