/**
 * Braintree Google Pay button api
 **/
define([
    'uiComponent',
    'underscore',
    'jquery',
    'mage/translate',
    'mage/storage',
    'PayPal_Braintree/js/form-builder',
    'PayPal_Braintree/js/googlepay/implementations/shortcut/3d-secure',
    'PayPal_Braintree/js/googlepay/model/parsed-response',
    'PayPal_Braintree/js/googlepay/model/payment-data',
    'PayPal_Braintree/js/helper/remove-non-digit-characters',
    'PayPal_Braintree/js/view/payment/validator-manager'
], function (
    Component,
    _,
    $,
    $t,
    storage,
    formBuilder,
    threeDSecureValidator,
    parsedResponseModel,
    paymentDataModel,
    removeNonDigitCharacters,
    validatorManager
) {
    'use strict';

    return Component.extend({
        defaults: {
            validatorManager: validatorManager,
            threeDSecureValidator: threeDSecureValidator,
            clientToken: null,
            merchantId: null,
            currencyCode: null,
            actionSuccess: null,
            amount: null,
            cardTypes: [],
            btnColor: 0,
            email: null,
            paymentMethodNonce: null,
            creditCardBin: null
        },

        /**
         * Set & get environment
         * "PRODUCTION" or "TEST"
         */
        setEnvironment: function (value) {
            this.environment = value;
        },
        getEnvironment: function () {
            return this.environment;
        },

        /**
         * Set & get api token
         */
        setClientToken: function (value) {
            this.clientToken = value;
        },
        getClientToken: function () {
            return this.clientToken;
        },

        /**
         * Set and get display name
         */
        setMerchantId: function (value) {
            this.merchantId = value;
        },
        getMerchantId: function () {
            return this.merchantId;
        },

        /**
         * Set and get currency code
         */
        setAmount: function (value) {
            this.amount = parseFloat(value).toFixed(2);
        },
        getAmount: function () {
            return this.amount;
        },

        /**
         * Set and get currency code
         */
        setCurrencyCode: function (value) {
            this.currencyCode = value;
        },
        getCurrencyCode: function () {
            return this.currencyCode;
        },

        /**
         * Set and get success redirection url
         */
        setActionSuccess: function (value) {
            this.actionSuccess = value;
        },
        getActionSuccess: function () {
            return this.actionSuccess;
        },

        /**
         * Set and get success redirection url
         */
        setCardTypes: function (value) {
            this.cardTypes = value;
        },
        getCardTypes: function () {
            return this.cardTypes;
        },

        /**
         * BTN Color
         */
        setBtnColor: function (value) {
            this.btnColor = value;
        },
        getBtnColor: function () {
            return this.btnColor;
        },

        /**
         * Add the 3D Secure validator config.
         *
         * @param {object} value
         */
        setThreeDSecureValidatorConfig: function (value) {
            this.threeDSecureValidator.setConfig(value);
        },

        /**
         * Add the 3D Secure validator to the validation manager with amount & billing address data set.
         * It will be added only if 3D Secure is active.
         */
        addThreeDSecureValidator: function () {
            this.threeDSecureValidator.setBillingAddress(this.getThreeDSecureBillingAddressData());
            this.threeDSecureValidator.setShippingAddress(this.getThreeDSecureShippingAddressData());
            this.threeDSecureValidator.setTotalAmount(this.getAmount());

            this.validatorManager.add(this.threeDSecureValidator);
        },

        /**
         * Payment request info
         */
        getPaymentRequest: function () {
            let result = {
                transactionInfo: {
                    totalPriceStatus: 'ESTIMATED',
                    totalPrice: this.getAmount(),
                    currencyCode: this.getCurrencyCode()
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
                shippingAddressRequired: true,
                shippingAddressParameters: {
                    phoneNumberRequired: true
                },
                emailRequired: true
            };

            if (this.getEnvironment() !== 'TEST') {
                result.merchantInfo = { merchantId: this.getMerchantId() };
            }

            return result;
        },

        /**
         * Place the order
         */
        startPlaceOrder: function (deviceData) {
            let self = this,
                payload = {
                    details: {
                        shippingAddress: self.getShippingAddressData(),
                        billingAddress: self.getBillingAddressData()
                    },
                    nonce: self.paymentMethodNonce,
                    isNetworkTokenized: parsedResponseModel.getIsNetworkTokenized(),
                    deviceData: deviceData
                };

            self.email = paymentDataModel.getEmail();
            self.paymentMethodNonce = parsedResponseModel.getNonce();
            self.creditCardBin = parsedResponseModel.getBin();


            if (parsedResponseModel.getIsNetworkTokenized() === false) {
                /* Add 3D Secure verification to payment & validate payment for non network tokenized cards */
                self.addThreeDSecureValidator();

                self.validatorManager.validate(self, function () {
                    /* Set the new nonce from the 3DS verification */
                    payload.nonce = self.paymentMethodNonce;

                    return formBuilder.build({
                        action: self.getActionSuccess(),
                        fields: {
                            result: JSON.stringify(payload)
                        }
                    }).submit();
                }, function () {
                    self.paymentMethodNonce = null;
                    self.creditCardBin = null;
                });
            } else {
                formBuilder.build({
                    action: this.getActionSuccess(),
                    fields: {
                        result: JSON.stringify(payload)
                    }
                }).submit();
            }
        },

        /**
         * Get the shipping address from the payment data model which should already be set by the calling script.
         *
         * @return {?Object}
         */
        getShippingAddressData: function () {
            let shippingAddress = paymentDataModel.getShippingAddress();

            if (shippingAddress === null) {
                return null;
            }

            return {
                streetAddress: shippingAddress.address1 + '\n' + shippingAddress.address2,
                locality: shippingAddress.locality,
                postalCode: shippingAddress.postalCode,
                countryCodeAlpha2: shippingAddress.countryCode,
                email: paymentDataModel.getEmail(),
                name: shippingAddress.name,
                telephone: removeNonDigitCharacters(_.get(shippingAddress, 'phoneNumber', '')),
                region: _.get(shippingAddress, 'administrativeArea', '')
            };
        },

        /**
         * Get the billing address from the payment data model which should already be set by the calling script.
         *
         * @return {?Object}
         */
        getBillingAddressData: function () {
            let paymentMethodData = paymentDataModel.getPaymentMethodData(),
                billingAddress = _.get(paymentMethodData, ['info', 'billingAddress'], null);

            if (paymentMethodData === null) {
                return null;
            }


            if (billingAddress === null) {
                return null;
            }

            return {
                streetAddress: billingAddress.address1 + '\n' + billingAddress.address2,
                locality: billingAddress.locality,
                postalCode: billingAddress.postalCode,
                countryCodeAlpha2: billingAddress.countryCode,
                email: paymentDataModel.getEmail(),
                name: billingAddress.name,
                telephone: removeNonDigitCharacters(_.get(billingAddress, 'phoneNumber', '')),
                region: _.get(billingAddress, 'administrativeArea', '')
            };
        },

        /**
         * Get the billing address data as required for 3D Secure verification.
         *
         * For First & last name, use a simple split by space.
         *
         * @return {?Object}
         */
        getThreeDSecureBillingAddressData: function () {
            let paymentMethodData = paymentDataModel.getPaymentMethodData(),
                billingAddress = _.get(paymentMethodData, ['info', 'billingAddress'], null);

            if (paymentMethodData === null) {
                return null;
            }

            if (billingAddress === null) {
                return null;
            }

            return {
                firstname: billingAddress.name.substring(0, billingAddress.name.indexOf(' ')),
                lastname: billingAddress.name.substring(billingAddress.name.indexOf(' ') + 1),
                telephone: removeNonDigitCharacters(_.get(billingAddress, 'phoneNumber', '')),
                street: [
                    billingAddress.address1,
                    billingAddress.address2
                ],
                city: billingAddress.locality,
                regionCode: _.get(billingAddress, 'administrativeArea', ''),
                postcode: billingAddress.postalCode,
                countryId: billingAddress.countryCode
            };
        },

        /**
         * Get the shipping address data as required for 3D Secure verification.
         *
         * For First & last name, use a simple split by space.
         *
         * @return {?Object}
         */
        getThreeDSecureShippingAddressData: function () {
            let shippingAddress = paymentDataModel.getShippingAddress();

            if (shippingAddress === null) {
                return null;
            }

            return {
                firstname: shippingAddress.name.substring(0, shippingAddress.name.indexOf(' ')),
                lastname: shippingAddress.name.substring(shippingAddress.name.indexOf(' ') + 1),
                telephone: removeNonDigitCharacters(_.get(shippingAddress, 'phoneNumber', '')),
                street: [
                    shippingAddress.address1,
                    shippingAddress.address2
                ],
                city: shippingAddress.locality,
                regionCode: _.get(shippingAddress, 'administrativeArea', ''),
                postcode: shippingAddress.postalCode,
                countryId: shippingAddress.countryCode
            };
        }
    });
});
