/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
define([
    'Magento_Checkout/js/view/payment/default',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/model/quote',
    'jquery',
    'braintree',
    'braintreeLpm',
    'PayPal_Braintree/js/model/full-screen-loader',
    'mage/translate',
    'underscore'
], function (Component, customerData, quote, $, braintree, lpm, fullScreenLoader, $t, _) {
    'use strict';

    return Component.extend({
        code: 'braintree_local_payment',
        paymentMethodNonce: null,
        config: {
            clientToken: null,
            merchantAccountId: null,
            redirectOnFail: null
        },

        /**
         * Initialize config values
         *
         * @param config
         */
        initialize: function (config) {
            this._super();
            this.config.clientToken = config.clientToken;
            this.config.merchantAccountId = config.merchantAccountId;
            this.config.redirectOnFail = config.redirectOnFail;
            this.setupLpmFallback();
        },

        /**
         * Setup local payment fallback scenario
         */
        setupLpmFallback: function () {
            let self = this;

            fullScreenLoader.startLoader();

            braintree.create({
                authorization: self.config.clientToken
            }, function (clientError, clientInstance) {
                if (clientError) {
                    self.redirectCustomerOnFailure($t('Unable to initialize Braintree Client.'));
                    return;
                }

                lpm.create({
                    client: clientInstance,
                    merchantAccountId: self.config.merchantAccountId
                }, function (lpmError, lpmInstance) {
                    if (lpmError) {
                        self.redirectCustomerOnFailure($t(lpmError.message));
                    }

                    if (lpmInstance.hasTokenizationParams()) {
                        lpmInstance.tokenize(function (tokenizeError, payload) {
                            if (tokenizeError) {
                                self.redirectCustomerOnFailure($t(tokenizeError.message));
                            } else {
                                // Send the nonce to the server to create a transaction
                                self.setPaymentMethodNonce(payload.nonce);
                                self.isPlaceOrderActionAllowed(true);

                                if (!window.checkoutConfig.isCustomerLoggedIn) {
                                    // Set the email to the quote.
                                    let checkoutData = customerData.get('checkout-data')();

                                    quote.guestEmail = checkoutData.inputFieldEmailValue;
                                }

                                // Check all agreements if any available.
                                let agreements = $('.checkout-agreements input[type="checkbox"]');

                                if (agreements.length) {
                                    agreements.prop('checked', true);
                                }

                                self.placeOrder();
                            }
                        });
                    } else {
                        let error = 'Payment can not be processed as invalid parameters received';

                        self.redirectCustomerOnFailure($t(error));
                    }
                });
            });
        },

        /**
         * Get code
         *
         * @returns {string}
         */
        getCode: function () {
            return this.code;
        },

        /**
         * Set payment method nonce
         *
         * @param nonce
         */
        setPaymentMethodNonce: function (nonce) {
            this.paymentMethodNonce = nonce;
        },

        /**
         * Get data
         *
         * @returns {{additional_data: {payment_method_nonce: null}, method: string}}
         */
        getData: function () {
            let data = {
                'method': this.getCode(),
                'additional_data': {
                    'payment_method_nonce': this.paymentMethodNonce
                }
            };

            data['additional_data'] = _.extend(data['additional_data'], this.additionalData);

            return data;
        },

        /**
         * redirect customer to the configured page if any error/failure/cancelled.
         */
        redirectCustomerOnFailure: function (message) {
            if (message) {
                let braintreeData = customerData.get('braintree')(),
                    errors = braintreeData.errors || [];

                errors.push(message);

                customerData.set('braintree', { errors: errors });
            }

            window.location.href = this.config.redirectOnFail;
        }
    });
});
