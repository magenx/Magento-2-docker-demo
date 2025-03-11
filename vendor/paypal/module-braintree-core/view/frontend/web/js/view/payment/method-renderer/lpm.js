define(
    [
        'Magento_Checkout/js/view/payment/default',
        'ko',
        'underscore',
        'jquery',
        'braintree',
        'braintreeLpm',
        'PayPal_Braintree/js/form-builder',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/action/select-billing-address',
        'PayPal_Braintree/js/helper/remove-non-digit-characters',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/url',
        'mage/translate'
    ],
    function (
        Component,
        ko,
        _,
        $,
        braintree,
        lpm,
        formBuilder,
        messageList,
        selectBillingAddress,
        removeNonDigitCharacters,
        fullScreenLoader,
        quote,
        additionalValidators,
        url,
        $t
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                code: 'braintree_local_payment',
                paymentMethodsAvailable: ko.observable(false),
                paymentMethodNonce: null,
                template: 'PayPal_Braintree/payment/lpm'
            },

            clickPaymentBtn: function (method) {
                let self = this;

                if (additionalValidators.validate()) {
                    fullScreenLoader.startLoader();

                    braintree.create({
                        authorization: self.getClientToken()
                    }, function (clientError, clientInstance) {
                        if (clientError) {
                            self.setErrorMsg($t('Unable to initialize Braintree Client.'));
                            fullScreenLoader.stopLoader();
                            return;
                        }

                        lpm.create({
                            client: clientInstance,
                            merchantAccountId: self.getMerchantAccountId()
                        }, function (lpmError, lpmInstance) {
                            if (lpmError) {
                                self.setErrorMsg(lpmError);
                                fullScreenLoader.stopLoader();
                                return;
                            }

                            lpmInstance.startPayment({
                                amount: self.getAmount(),
                                currencyCode: self.getCurrencyCode(),
                                email: self.getCustomerDetails().email,
                                phone: removeNonDigitCharacters(_.get(self.getCustomerDetails(), 'phone', '')),
                                givenName: self.getCustomerDetails().firstName,
                                surname: self.getCustomerDetails().lastName,
                                shippingAddressRequired: !quote.isVirtual(),
                                address: self.getAddress(),
                                paymentType: method,
                                onPaymentStart: function (data, start) {
                                    start();
                                },
                                // This is a required option, however it will apparently never be used in the current
                                // payment flow. Therefore, both values are set to allow the payment flow to continue,
                                // rather than error out.
                                fallback: {
                                    url: self.getFallbackUrl(),
                                    buttonText: self.getFallbackButtonText()
                                }
                            }, function (startPaymentError, payload) {
                                fullScreenLoader.stopLoader();
                                if (startPaymentError) {
                                    switch (startPaymentError.code) {
                                    case 'LOCAL_PAYMENT_POPUP_CLOSED':
                                        self.setErrorMsg($t('Local Payment popup was closed unexpectedly.'));
                                        break;
                                    case 'LOCAL_PAYMENT_WINDOW_OPEN_FAILED':
                                        self.setErrorMsg($t('Local Payment popup failed to open.'));
                                        break;
                                    case 'LOCAL_PAYMENT_WINDOW_CLOSED':
                                        self.setErrorMsg($t('Local Payment popup was closed. Payment cancelled.'));
                                        break;
                                    default:
                                        self.setErrorMsg('Error! ' + startPaymentError);
                                        break;
                                    }
                                } else {
                                    // Send the nonce to your server to create a transaction
                                    self.setPaymentMethodNonce(payload.nonce);
                                    self.placeOrder();
                                }
                            });
                        });
                    });
                }
            },

            getAddress: function () {
                let shippingAddress = quote.shippingAddress();

                if (quote.isVirtual()) {
                    return {
                        countryCode: shippingAddress.countryId
                    };
                }

                return {
                    streetAddress: shippingAddress.street[0],
                    extendedAddress: shippingAddress.street[1],
                    locality: shippingAddress.city,
                    postalCode: shippingAddress.postcode,
                    region: shippingAddress.region,
                    countryCode: shippingAddress.countryId
                };
            },

            getAmount: function () {
                return quote.totals()['base_grand_total'].toString();
            },

            getBillingAddress: function () {
                return quote.billingAddress();
            },

            getClientToken: function () {
                return window.checkoutConfig.payment[this.getCode()].clientToken;
            },

            getCode: function () {
                return this.code;
            },

            getCurrencyCode: function () {
                return quote.totals()['base_currency_code'];
            },

            getCustomerDetails: function () {
                let billingAddress = quote.billingAddress();

                return {
                    firstName: billingAddress.firstname,
                    lastName: billingAddress.lastname,
                    phone: billingAddress.telephone !== null ? billingAddress.telephone : '',
                    email: typeof quote.guestEmail === 'string'
                        ? quote.guestEmail : window.checkoutConfig.customerData.email
                };
            },

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

            getMerchantAccountId: function () {
                return window.checkoutConfig.payment[this.getCode()].merchantAccountId;
            },

            getPaymentMethod: function (method) {
                let methods = this.getPaymentMethods();

                for (let i = 0; i < methods.length; i++) {
                    if (methods[i].method === method) {
                        return methods[i];
                    }
                }
            },

            /**
             * Get allowed local payment methods
             *
             * @returns {*}
             */
            getPaymentMethods: function () {
                return window.checkoutConfig.payment[this.getCode()].allowedMethods;
            },

            /**
             * Get payment icons
             *
             * @returns {*}
             */
            getPaymentMarkSrc: function () {
                return window.checkoutConfig.payment[this.getCode()].paymentIcons;
            },

            /**
             * Get title
             *
             * @returns {*}
             */
            getTitle: function () {
                return window.checkoutConfig.payment[this.getCode()].title;
            },

            /**
             * Get fallback url
             *
             * @returns {String}
             */
            getFallbackUrl: function () {
                return window.checkoutConfig.payment[this.getCode()].fallbackUrl;
            },

            /**
             * Get fallback button text
             * @returns {String}
             */
            getFallbackButtonText: function () {
                return window.checkoutConfig.payment[this.getCode()].fallbackButtonText;
            },

            /**
             * Initialize
             *
             * @returns {*}
             */
            initialize: function () {
                this._super();
                return this;
            },

            /**
             * Is payment method active?
             *
             * @returns {boolean}
             */
            isActive: function () {
                let address = quote.billingAddress() || quote.shippingAddress(),
                    methods = this.getPaymentMethods();

                for (let i = 0; i < methods.length; i++) {
                    if (methods[i].countries.includes(address.countryId)) {
                        return true;
                    }
                }

                return false;
            },

            /**
             * Is country and currency valid?
             *
             * @param method
             * @returns {boolean}
             */
            isValidCountryAndCurrency: function (method) {
                let address = quote.billingAddress(),
                    countryId = address.countryId,
                    quoteCurrency = quote.totals()['base_currency_code'],
                    paymentMethodDetails = this.getPaymentMethod(method);

                if (!address) {
                    this.paymentMethodsAvailable(false);
                    return false;
                }

                if (countryId !== 'GB' && paymentMethodDetails.countries.includes(countryId)
                    && (quoteCurrency === 'EUR' || quoteCurrency === 'PLN') || countryId === 'GB'
                    && paymentMethodDetails.countries.includes(countryId) && quoteCurrency === 'GBP') {
                    this.paymentMethodsAvailable(true);
                    return true;
                }

                return false;
            },

            /**
             * Set error message
             *
             * @param message
             */
            setErrorMsg: function (message) {
                messageList.addErrorMessage({
                    message: message
                });
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
             * Validate form
             *
             * @param form
             * @returns {*|jQuery}
             */
            validateForm: function (form) {
                return $(form).validation() && $(form).validation('isValid');
            }
        });
    }
);
