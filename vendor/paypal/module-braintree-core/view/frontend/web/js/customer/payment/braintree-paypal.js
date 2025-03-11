define([
    'uiComponent',
    'jquery',
    'ko',
    'underscore',
    'PayPal_Braintree/js/view/payment/adapter',
    'braintreePayPalCheckout',
    'mage/translate'
], function (Component, $, ko, _, braintree, paypalCheckout, $t) {
    'use strict';

    /**
     * braintree is not an instance of Component so we need to merge in our changes
     * and return an instance of Component with the final merged object.
     */
    var uiC = _.extend(braintree, {
        clientToken: null,
        uiConfig: null,
        paymentMethodNonce: null,

        viewModel: {
            errorMessage: ko.observable()
        },

        /**
         * @inheritDoc
         */
        initialize: function (uiConfig) {
            this._super();

            this.uiConfig = uiConfig;
            this.merchantName = uiConfig.merchantName;
            this.locale = uiConfig.locale;
            this.currency = uiConfig.currency;
            this.orderAmount = uiConfig.orderAmount;
            const self = this;

            this.clientConfig = {

                additionalData: {},
                buttonId: 'paypal_container',

                /**
                 * Device data initialization
                 * @param {String} deviceData
                 */
                onDeviceDataReceived: function (deviceData) {
                    this.additionalData['device_data'] = deviceData;
                },

                /**
                 * Triggers when widget is loaded
                 * @param {Object} context
                 */
                onReady: function (context) {
                    paypalCheckout.create({
                        client: context.clientInstance
                    }, async function (paypalCheckoutErr, paypalCheckoutInstance) {
                        self.setPayPalInstance(paypalCheckoutInstance);

                        await paypalCheckoutInstance.loadPayPalSDK({
                            vault: true
                        });

                        window.paypal.Buttons({
                            fundingSource: window.paypal.FUNDING.PAYPAL,

                            createBillingAgreement: function () {
                                return paypalCheckoutInstance.createPayment({
                                    flow: 'vault',

                                    enableShippingAddress: false,
                                    shippingAddressEditable: false,

                                    amount: self.orderAmount,
                                    currency: self.currency,
                                    locale: self.locale
                                });
                            },

                            onApprove: function (data) {
                                $('body').trigger('processStart');
                                return paypalCheckoutInstance.tokenizePayment(data, function (err, payload) {
                                    if (err) {
                                        $('body').trigger('processStop');
                                        self.viewModel.errorMessage(
                                            $t('Please try again with another form of payment.'));
                                        return;
                                    }

                                    $.ajax({
                                        url: '/rest/default/V1/braintree/mine/payment/vault',
                                        type: 'POST',
                                        data: JSON.stringify({
                                            payment: {
                                                payment_method_code: 'braintree_paypal',
                                                payment_method_nonce: payload.nonce,
                                                device_data: self.deviceData
                                            }
                                        }),
                                        contentType:'application/json; charset=utf-8',
                                        success: function () {
                                            window.location.reload();
                                        },
                                        error: function (error) {
                                            $('body').trigger('processStop');
                                            console.warn(error.message);
                                        }
                                    });
                                });
                            },

                            onCancel: function (data) {
                                console.log('PayPal payment canceled', JSON.stringify(data, 0, 2));
                            },

                            onError: function (err) {
                                console.error('PayPal error', err);
                            }
                        }).render('#paypal_container_account');
                    });
                },

                /**
                 * Triggers on any Braintree error
                 * @param {Object} response
                 */
                onError: function (response) {
                    self.showError($t('PayPal error msg'));
                    throw response.message;
                },

                /**
                 * Triggers when customer click "Cancel"
                 */
                onCancelled: function () {
                    self.showError($t('The process has been cancelled'));
                },

                onPaymentMethodReceived: function (response) {
                    self.paymentMethodNonce = response.nonce;
                    $('#braintree-paypal-payment-method-nonce').val(response.nonce);
                    $('#braintree-paypal-form').trigger('submit');
                },

                dataCollector: {
                    paypal: true
                },
                paypal: {
                    container: 'paypal_container_account',
                    flow: 'vault',
                    singleUse: false,
                    amount: self.orderAmount,
                    currency: self.currency,
                    locale: self.locale,
                    enableShippingAddress: false,
                    displayName: self.merchantName,

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
                }
            };

            this.setConfig(this.clientConfig);
            this.clientToken = uiConfig.clientToken;
        },

        /**
         * @inheritDoc
         */
        getClientToken: function () {
            return this.clientToken;
        },

        /**
         * @returns {String}
         */
        getColor: function () {
            return this.color;
        },

        /**
         * @returns {String}
         */
        getShape: function () {
            return this.shape;
        },

        /**
         * @returns {String}
         */
        getLayout: function () {
            return this.layout;
        },

        /**
         * @returns {String}
         */
        getSize: function () {
            return this.size;
        },

        /**
         * @returns {String}
         */
        getEnvironment: function () {
            return this.environment;
        },

        /**
         * @returns {String}
         */
        getDisabledFunding: function () {
            return this.disabledFunding;
        },

        /**
         * Set the PayPal instance or null it by setting the value of the property.
         *
         * @param val
         */
        setPayPalInstance: function (val) {
            this.paypalInstance = val;
        },

        /**
         * Run the teardown script to remove the PayPal instance.
         */
        teardownPayPalInstance: function () {
            if (this.paypalInstance) {
                this.paypalInstance.teardown(function () {
                    $('#paypal_container_account').empty();
                });
                this.paypalInstance = null;
            }

            window.dispatchEvent(new Event('paypal:reinit-express'));
        }
    });

    return Component.extend(uiC);
});
