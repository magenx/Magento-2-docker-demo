/**
 * Braintree Google Pay payment method integration.
 **/
define([
    'underscore',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
    'Magento_Vault/js/view/payment/vault-enabler',
    'PayPal_Braintree/js/googlepay/button',
    'PayPal_Braintree/js/googlepay/model/parsed-response',
    'PayPal_Braintree/js/view/payment/validator-handler'
], function (
    _,
    Component,
    quote,
    VaultEnabler,
    GooglePayButton,
    parsedResponseModel,
    validatorManager
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'PayPal_Braintree/googlepay/core-checkout',
            validatorManager: validatorManager,
            paymentMethodNonce: null,
            creditCardBin: null,
            deviceData: null,
            grandTotalAmount: 0,
            vaultEnabler: null,
            additionalData: {}
        },

        /**
         * @returns {exports.initialize}
         */
        initialize: function () {
            this._super();
            this.vaultEnabler = new VaultEnabler();
            this.vaultEnabler.setPaymentCode(this.getVaultCode());

            return this;
        },

        /**
         * Inject the Google Pay button into the target element
         */
        getGooglePayBtn: function (id) {
            GooglePayButton.init(
                document.getElementById(id),
                this
            );
        },

        /**
         * Subscribe to grand totals
         */
        initObservable: function () {
            this._super();
            this.vaultEnabler = new VaultEnabler();
            this.vaultEnabler.setPaymentCode(this.getVaultCode());

            this.validatorManager.initialize();

            this.grandTotalAmount = parseFloat(quote.totals()['base_grand_total']).toFixed(2);
            this.currencyCode = quote.totals()['base_currency_code'];

            quote.totals.subscribe(function () {
                if (this.grandTotalAmount !== quote.totals()['base_grand_total']) {
                    this.grandTotalAmount = parseFloat(quote.totals()['base_grand_total']).toFixed(2);
                }
            }.bind(this));

            return this;
        },

        /**
         * Google Pay place order method
         */
        startPlaceOrder: function (device_data) {
            let self = this;

            /* Set the nonce & bin and trigger 3DS if card is not network tokenized */
            self.paymentMethodNonce = parsedResponseModel.getNonce();
            self.creditCardBin = parsedResponseModel.getBin();

            if (parsedResponseModel.getIsNetworkTokenized() === false) {
                // place order on success validation
                self.validatorManager.validate(self, function () {
                    self.setDeviceData(device_data);
                    return self.placeOrder('parent');
                }, function () {
                    self.paymentMethodNonce = null;
                    self.creditCardBin = null;
                });
            } else {
                self.setDeviceData(device_data);
                self.placeOrder();
            }
        },

        /**
         * Save device_data
         */
        setDeviceData: function (device_data) {
            this.deviceData = device_data;
        },

        /**
         * Retrieve the client token
         * @returns null|string
         */
        getClientToken: function () {
            return window.checkoutConfig.payment[this.getCode()].clientToken;
        },

        /**
         * Payment request info
         */
        getPaymentRequest: function () {
            let result = {
                transactionInfo: {
                    totalPriceStatus: 'FINAL',
                    totalPrice: this.grandTotalAmount,
                    currencyCode: this.currencyCode
                },
                allowedPaymentMethods: [
                    {
                        'type': 'CARD',
                        'parameters': {
                            'allowedCardNetworks': this.getCardTypes(),
                            'billingAddressRequired': true,
                            'billingAddressParameters': {
                                format: 'FULL',
                                phoneNumberRequired: true
                            }
                        }

                    }
                ],
                shippingAddressRequired: false,
                emailRequired: false
            };

            if (this.getEnvironment() !== 'TEST') {
                result.merchantInfo = { merchantId: this.getMerchantId() };
            }

            return result;
        },

        /**
         * Merchant display name
         */
        getMerchantId: function () {
            return window.checkoutConfig.payment[this.getCode()].merchantId;
        },

        /**
         * Environment
         */
        getEnvironment: function () {
            return window.checkoutConfig.payment[this.getCode()].environment;
        },

        /**
         * Card Types
         */
        getCardTypes: function () {
            return window.checkoutConfig.payment[this.getCode()].cardTypes;
        },

        /**
         * BTN Color
         */
        getBtnColor: function () {
            return window.checkoutConfig.payment[this.getCode()].btnColor;
        },

        /**
         * Get data
         * @returns {Object}
         */
        getData: function () {
            let data = {
                'method': this.getCode(),
                'additional_data': {
                    'payment_method_nonce': this.paymentMethodNonce,
                    'device_data': this.deviceData,
                    'is_network_tokenized': parsedResponseModel.getIsNetworkTokenized()
                }
            };

            if (parsedResponseModel.getIsNetworkTokenized() === false) {
                data['additional_data'] = _.extend(data['additional_data'], this.additionalData);
                this.vaultEnabler.visitAdditionalData(data);
            }

            return data;
        },

        /**
         * Return image url for the Google Pay mark
         */
        getPaymentMarkSrc: function () {
            return window.checkoutConfig.payment[this.getCode()].paymentMarkSrc;
        },

        /**
         * @returns {Boolean}
         */
        isVaultEnabled: function () {
            return this.vaultEnabler.isVaultEnabled();
        },

        /**
         * @returns {String}
         */
        getVaultCode: function () {
            return window.checkoutConfig.payment[this.getCode()].vaultCode;
        }
    });
});
