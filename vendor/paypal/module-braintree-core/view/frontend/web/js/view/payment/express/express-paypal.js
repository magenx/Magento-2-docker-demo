/**
 * Express Paypal button component
 */

define([
    'jquery',
    'underscore',
    'uiComponent',
    'mage/url',
    'PayPal_Braintree/js/paypal/button',
    'PayPal_Braintree/js/helper/get-cart-line-items-helper',
    'domReady!'
], function ($, _, Component, url, paypalButton, getCartLineItems) {
    'use strict';

    const config = _.get(window.checkoutConfig.payment, 'braintree_paypal', {});

    return Component.extend({
        defaults: {
            template: 'PayPal_Braintree/express/express-paypal',
            isActive: _.get(config, 'isActive', false),
            clientToken: _.get(config, 'clientToken', null),
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
        },

        /**
         * Initialize Braintree PayPal buttons.
         *
         * PayPal Credit & PayPal Pay Later & PayPal Pay Later Messaging rely on PayPal to be enabled.
         */
        initPayPalButtons: function () {
            if (!this.isMethodActive() || !this.clientToken) {
                return;
            }

            let buttonConfig = {
                    'clientToken': this.clientToken,
                    'currency': this.checkoutCurrency,
                    'environment': config.environment,
                    'merchantCountry': config.merchantCountry,
                    'isCreditActive': _.get(window.checkoutConfig.payment,
                        ['braintree_paypal_credit', 'isActive'], false)
                },

                cartLineItems = getCartLineItems();

            paypalButton.init(
                buttonConfig,
                JSON.stringify(cartLineItems)
            );
        }
    });
});
