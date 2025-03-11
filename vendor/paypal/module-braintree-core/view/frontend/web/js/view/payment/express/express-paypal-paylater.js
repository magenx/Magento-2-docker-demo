/**
 * Express PayLater button component
 */
define([
    'jquery',
    'underscore',
    'uiComponent',
    'mage/url',
    'domReady!'
], function ($, _, Component, url) {
    'use strict';

    const config = _.get(window.checkoutConfig.payment, 'braintree_paypal_paylater', {});

    return Component.extend({
        defaults: {
            template: 'PayPal_Braintree/express/express-paypal-paylater',
            isActive: _.get(config, 'isActive', false),
            checkoutCurrency: window.checkoutConfig.quoteData.base_currency_code,
            checkoutAmount: window.checkoutConfig.quoteData.base_grand_total,
            checkoutLocale: _.get(config, 'locale', null),
            buttonLabel: _.get(config, ['style', 'label'], null),
            buttonColor: _.get(config, ['style', 'color'], null),
            buttonShape: _.get(config, ['style', 'shape'], null),
            actionSuccess: url.build('braintree/paypal/review/'),
            isMessageActive: _.get(config, 'isMessageActive', false),
            messageTextColor: _.get(config ,['message', 'text_color'], null),
            messageLayout: _.get(config ,['message', 'layout'], null),
            messageLogoPosition: _.get(config ,['message', 'logo_position'], null),
            messageLogo: _.get(config ,['message', 'logo'], null)
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
         * Is the payment method message active.
         *
         * @return {boolean}
         */
        isMethodMessageActive: function () {
            return this.isMessageActive;
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
