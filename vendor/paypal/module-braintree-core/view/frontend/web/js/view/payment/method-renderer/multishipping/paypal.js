/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
define([
    'jquery',
    'underscore',
    'braintreeCheckoutPayPalAdapter',
    'Magento_Checkout/js/model/quote',
    'PayPal_Braintree/js/view/payment/method-renderer/paypal',
    'PayPal_Braintree/js/helper/format-amount',
    'Magento_Checkout/js/action/set-payment-information',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Checkout/js/model/full-screen-loader',
    'mage/translate'
], function (
    $,
    _,
    Braintree,
    quote,
    Component,
    formatAmount,
    setPaymentInformationAction,
    additionalValidators,
    fullScreenLoader,
    $t
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'PayPal_Braintree/payment/multishipping/paypal',
            submitButtonSelector: '[id="parent-payment-continue"]',
            reviewButtonHtml: ''
        },

        /**
         * @override
         */
        initObservable: function () {
            this.reviewButtonHtml = $(this.submitButtonSelector).html();
            return this._super();
        },

        initClientConfig: function () {
            this.clientConfig = _.extend(this.clientConfig, this.getPayPalConfig());
            this.clientConfig.paypal.enableShippingAddress = false;

            _.each(this.clientConfig, function (fn, name) {
                if (typeof fn === 'function') {
                    this.clientConfig[name] = fn.bind(this);
                }
            }, this);
            this.clientConfig.buttonPayPalId = 'parent-payment-continue';
        },

        /**
         * @override
         */
        onActiveChange: function (isActive) {
            this.updateSubmitButtonHtml(isActive);
            this._super(isActive);
        },

        /**
         * @override
         */
        beforePlaceOrder: function (data) {
            this._super(data);
        },

        /**
         * Re-init PayPal Auth Flow
         */
        reInitPayPal: function () {
            this.disableButton();
            this.clientConfig.paypal.amount = formatAmount(this.grandTotalAmount);

            if (!quote.isVirtual()) {
                this.clientConfig.paypal.enableShippingAddress = false;
                this.clientConfig.paypal.shippingAddressEditable = false;
            }

            Braintree.setConfig(this.clientConfig);

            if (Braintree.getPayPalInstance()) {
                Braintree.getPayPalInstance().teardown(function () {
                    Braintree.setup();
                });
                Braintree.setPayPalInstance(null);
            } else {
                Braintree.setup();
                this.enableButton();
            }
        },

        loadPayPalButton: function (paypalCheckoutInstance, funding) {
            if (funding === 'credit') {
                Braintree.config.buttonId = this.getCreditButtonId();
            } else if (funding === 'paylater') {
                Braintree.config.buttonId = this.getPayLaterButtonId();
            } else {
                Braintree.config.buttonId = this.getPayPalButtonId();
            }

            let paypalPayment = Braintree.config.paypal,
                onPaymentMethodReceived = Braintree.config.onPaymentMethodReceived,
                style = {
                    label: Braintree.getLabel(funding),
                    color: Braintree.getColor(funding),
                    shape: Braintree.getShape(funding)
                },
                payPalButtonId = Braintree.config.buttonId,
                payPalButtonElement = $('#' + Braintree.config.buttonId),
                events = Braintree.events,

                button = window.paypal.Buttons({
                    fundingSource: funding,
                    env: Braintree.getEnvironment(),
                    style: style,
                    commit: true,
                    locale: Braintree.config.paypal.locale,

                    createOrder: function () {
                        return paypalCheckoutInstance.createPayment(paypalPayment);
                    },

                    onCancel: function (data) {
                        console.log('checkout.js payment cancelled', JSON.stringify(data, 0, 2));

                        if (typeof events.onCancel === 'function') {
                            events.onCancel();
                        }
                    },

                    onError: function (err) {
                        let error = 'PayPal Checkout could not be initialized. Please contact the store owner.';

                        Braintree.showError($t(error));
                        Braintree.config.paypalInstance = null;
                        console.error('Paypal checkout.js error', err);

                        if (typeof events.onError === 'function') {
                            events.onError(err);
                        }
                    },

                    onClick: function (data) {
                    // To check term & conditions input checked - validate additional validators.
                        if (!additionalValidators.validate()) {
                            return false;
                        }

                        if (typeof events.onClick === 'function') {
                            events.onClick(data);
                        }
                    },

                    onApprove: function (data) {
                        return paypalCheckoutInstance.tokenizePayment(data)
                            .then(function (payload) {
                                onPaymentMethodReceived(payload);
                            });
                    }
                });

            payPalButtonElement.html('');

            // Render
            Braintree.config.paypalInstance = paypalCheckoutInstance;

            if (button.isEligible() && payPalButtonElement.length) {
                button.render('#' + payPalButtonId).then(function () {
                    Braintree.enableButton();
                    if (typeof Braintree.config.onPaymentMethodError === 'function') {
                        Braintree.config.onPaymentMethodError();
                    }
                }).then(function (data) {
                    if (typeof events.onRender === 'function') {
                        events.onRender(data);
                    }
                });
            }
        },

        /**
         * Get configuration for PayPal
         *
         * @returns {Object}
         */
        getPayPalConfig: function () {
            let totals = quote.totals(),
                config = {};

            config.paypal = {
                flow: 'checkout',
                amount: formatAmount(this.grandTotalAmount),
                currency: totals['base_currency_code'],
                locale: this.getLocale(),
                requestBillingAgreement: true,

                /**
                 * Triggers on any Braintree error
                 */
                onError: function () {
                    this.paymentMethodNonce = null;
                },

                /**
                 * Triggers if browser doesn't support PayPal Checkout
                 */
                onUnsupported: function () {
                    this.paymentMethodNonce = null;
                }
            };

            if (!quote.isVirtual()) {
                config.paypal.enableShippingAddress = false;
                config.paypal.shippingAddressEditable = false;
            }

            if (this.getMerchantName()) {
                config.paypal.displayName = this.getMerchantName();
            }

            return config;
        },

        /**
         * Get shipping address
         *
         * @returns {{}}
         */
        getShippingAddress: function () {
            return {};
        },

        /**
         * @override
         */
        getData: function () {
            let data = this._super();

            data['additional_data']['is_active_payment_token_enabler'] = true;

            return data;
        },

        /**
         * @override
         */
        isActiveVault: function () {
            return true;
        },

        /**
         * Checks if payment method nonce is already received.
         *
         * @returns {Boolean}
         */
        isPaymentMethodNonceReceived: function () {
            return this.paymentMethodNonce !== null;
        },

        /**
         * Update submit button on multi-addresses checkout billing form.
         *
         * @param {Boolean} isActive
         */
        updateSubmitButtonHtml: function (isActive) {
            $(this.submitButtonSelector).removeClass('primary');
            if (this.isPaymentMethodNonceReceived() || !isActive) {
                $(this.submitButtonSelector).addClass('primary');
                $(this.submitButtonSelector).html(this.reviewButtonHtml);
            }
        },

        /**
         * @override
         */
        placeOrder: function () {
            if (!this.isPaymentMethodNonceReceived()) {
                this.payWithPayPal();
            } else {
                fullScreenLoader.startLoader();

                $.when(
                    setPaymentInformationAction(
                        this.messageContainer,
                        this.getData()
                    )
                ).done(this.done.bind(this))
                    .fail(this.fail.bind(this));
            }
        },

        /**
         * {Function}
         */
        fail: function () {
            fullScreenLoader.stopLoader();

            return this;
        },

        /**
         * {Function}
         */
        done: function () {
            fullScreenLoader.stopLoader();
            $('#multishipping-billing-form').trigger('submit');

            return this;
        }
    });
});
