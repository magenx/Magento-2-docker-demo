define([
    'uiComponent',
    'jquery',
    'ko',
    'underscore',
    'mage/translate',
    'uiRegistry',
    'braintreeThreeDSecure',
    'Magento_Checkout/js/model/payment/additional-validators',
    'PayPal_Braintree/js/helper/get-braintree-config',
    'PayPal_Braintree/js/view/payment/adapter'
], function (Component, $, ko, _,  $t, uiRegistry, threeDSecure, additionalValidators, getBraintreeConfig, braintree) {
    'use strict';

    /**
     * braintree is not an instance of Component so we need to merge in our changes
     * and return an instance of Component with the final merged object.
     */
    var uiC = _.extend(braintree, {
        clientToken: null,
        uiConfig: null,
        paymentMethodNonce: null,
        selectedCardType: null,

        isValidCardNumber: false,
        isValidExpirationDate: false,
        isValidCvvNumber: false,

        viewModel: {
            paymentMethodNonce: ko.observable(null),
            publicHash: ko.observable(null),
            paymentMethodCode: 'braintree',
            total: ko.observable(0),
            isThreeDSecureEnabled: ko.observable(false),
            selectedMethod: {
                price: ko.observable(0)
            },
            errorMessage: ko.observable()
        },

        /**
         * Additional payment data
         *
         * {Object}
         */
        additionalData: {},

        /**
         * @inheritDoc
         */
        initialize: async function (uiConfig) {
            this._super().observe([
                'selectedCardType'
            ]);

            await this.getBraintreeConfig();

            this.uiConfig = uiConfig;
            this.icons = uiConfig.icons;
            this.cvvImage = uiConfig.cvvImage;
            this.viewModel.isThreeDSecureEnabled(uiConfig.isThreeDSecureEnabled);
            this.viewModel.total(parseFloat(uiConfig.amount).toFixed(2));

            let self = this;

            const elm = await this._waitForElm('#co-transparent-form-braintree');

            if (elm) {
                this.clientConfig = {
                    additionalData: {},

                    /**
                     * Device data initialization
                     * @param {String} deviceData
                     */
                    onDeviceDataReceived: function (deviceData) {
                        this.additionalData['device_data'] = deviceData;
                    },

                    /**
                     * Triggers on any Braintree error
                     * @param {Object} response
                     */
                    onError: function (response) {
                        self.showError($t('Please enter a valid card number, expiry date and CVV Number.'));
                        throw response.message;
                    },

                    /**
                     * Triggers when customer click "Cancel"
                     */
                    onCancelled: function () {
                        self.showError($t('The process has been cancelled'));
                    },

                    onReady: function (context) {
                        context.setupHostedFields();
                    },

                    /**
                     * Allow a new nonce to be generated
                     */
                    onPaymentMethodError: function () {
                        this.isProcessing = false;
                    },

                    /**
                     * Set payment nonce
                     * @param {String} paymentMethodNonce
                     */
                    setPaymentMethodNonce: function (paymentMethodNonce) {
                        this.paymentMethodNonce = paymentMethodNonce;
                    },


                    /**
                     * After Braintree instance initialization
                     */
                    onInstanceReady: function (instance) {
                        instance.on('validityChange', self.onValidityChange.bind(self));
                        instance.on('cardTypeChange', self.onCardTypeChange.bind(self));
                    },

                    id: 'co-transparent-form-braintree',

                    hostedFields: {
                        number: {
                            selector: '#braintree_cc_number',
                            placeholder: '4111 1111 1111 1111'
                        },
                        expirationDate: {
                            selector: '#braintree_expirationDate',
                            placeholder: $t('MM/YYYY')
                        },
                        cvv: {
                            selector: '#braintree_cc_cid',
                            placeholder: $t('000')
                        }
                    },

                    styles: {
                        'input': {
                            'font-size': '14px',
                            'color': '#3A3A3A'
                        },
                        ':focus': {
                            'color': 'black'
                        },
                        '.valid': {
                            'color': 'green'
                        },
                        '.invalid': {
                            'color': 'red'
                        }
                    },

                    onPaymentMethodReceived: function (response) {
                        $.ajax({
                            url: '/rest/default/V1/braintree/mine/payment/vault',
                            type: 'POST',
                            data: JSON.stringify({
                                billingAddress: {},
                                payment: {
                                    payment_method_code: self.viewModel.paymentMethodCode,
                                    payment_method_nonce: response.nonce,
                                    device_data: this.additionalData.device_data
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
                    }
                };

                this.setConfig(this.clientConfig);
                this.clientToken = uiConfig.clientToken;
                this.setup();
            }
        },

        /**
         * Get list of card types
         * @returns {Object}
         */
        getCcTypesMapper: function () {
            return this.uiConfig.ccTypeMapper;
        },

        /**
         * Find mage card type by Braintree type
         * @param {String} type
         * @param {Object} availableTypes
         * @returns {*}
         */
        getMageCardType: function (type, availableTypes) {
            var storedCardType = null,
                mapper = this.getCcTypesMapper();

            if (type && typeof mapper[type] !== 'undefined') {
                storedCardType = mapper[type];

                if (_.indexOf(availableTypes, storedCardType) !== -1) {
                    return storedCardType;
                }
            }

            return null;
        },

        getBraintreeConfig: function () {
            return getBraintreeConfig()
                .then(response => {
                    this.merchantAccountId = response.data.storeConfig.braintree_merchant_account_id;
                });
        },

        /**
         * Triggers on Hosted Field changes
         * @param {Object} event
         * @returns {Boolean}
         */
        onValidityChange: function (event) {
            // Handle a change in validation or card type
            if (event.emittedBy === 'number') {
                this.selectedCardType(null);

                if (event.cards.length === 1) {
                    this.isValidCardNumber = event.fields.number.isValid;
                    this.selectedCardType(
                        this.getMageCardType(event.cards[0].type, this.uiConfig.availableCardTypes));
                    this.validateCardType();
                } else {
                    this.isValidCardNumber = event.fields.number.isValid;
                    this.validateCardType();
                }
            }

            // Other field validations
            if (event.emittedBy === 'expirationDate') {
                this.isValidExpirationDate = event.fields.expirationDate.isValid;
            }
            if (event.emittedBy === 'cvv') {
                this.isValidCvvNumber = event.fields.cvv.isValid;
            }
        },

        /**
         * Triggers on Hosted Field card type changes
         * @param {Object} event
         * @returns {Boolean}
         */
        onCardTypeChange: function (event) {
            if (event.cards.length === 1) {
                this.selectedCardType(
                    this.getMageCardType(event.cards[0].type, this.uiConfig.availableCardTypes)
                );
            } else {
                this.selectedCardType(null);
            }
        },

        _waitForElm: function (selector) {
            return new Promise(resolve => {
                if (document.querySelector(selector)) {
                    return resolve(document.querySelector(selector));
                }

                const observer = new MutationObserver(() => {
                    if (document.querySelector(selector)) {
                        resolve(document.querySelector(selector));
                        observer.disconnect();
                    }
                });

                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            });
        },

        /**
         * @inheritDoc
         */
        getClientToken: function () {
            return this.clientToken;
        },

        /**
         * Trigger order placing
         */
        placeOrderClick: function () {
            if (this.validateFormFields() && additionalValidators.validate()) {
                this.handleNonce();
            }
        },

        /**
         * Get jQuery selector
         * @param {String} field
         * @returns {String}
         */
        getSelector: function (field) {
            return '#' + this.code + '_' + field;
        },

        /**
         * Get card icons
         *
         * @param {String} type
         * @returns {Object|Boolean}
         */
        getIcons: function (findType) {
            return this.icons.find(({ type }) => type === findType);
        },

        /**
         * Toggle invalid class on selector
         * @param selector
         * @param state
         * @returns {boolean}
         */
        validateField: function (selector, state) {
            var $selector = $(this.getSelector(selector)),
                invalidClass = 'braintree-hosted-fields-invalid';

            if (state === true) {
                $selector.removeClass(invalidClass);
                return true;
            }

            $selector.addClass(invalidClass);
            return false;
        },

        /**
         * Validate all fields
         * @returns {boolean}
         */
        validateFormFields: function () {
            return (this.validateCardType() && this.validateExpirationDate() && this.validateCvvNumber()) === true;
        },

        /**
         * Validate current credit card type
         * @returns {Boolean}
         */
        validateCardType: function () {
            return this.validateField(
                'cc_number',
                this.isValidCardNumber
            );
        },

        /**
         * Validate current expiry date
         * @returns {boolean}
         */
        validateExpirationDate: function () {
            return this.validateField(
                'expirationDate',
                this.isValidExpirationDate === true
            );
        },

        /**
         * Validate current CVV field
         * @returns {boolean}
         */
        validateCvvNumber: function () {
            return this.validateField(
                'cc_cid',
                this.isValidCvvNumber === true
            );
        },

        /**
         * Get image for CVV
         * @returns {String}
         */
        getCvvImageHtml: function () {
            return '<img src="' + this.cvvImage +
                '" alt="' + $t('Card Verification Number Visual Reference') +
                '" title="' + $t('Card Verification Number Visual Reference') +
                '" />';
        },

        /**
         * Prepare data to place order
         */
        handleNonce: function () {

            $('body').trigger('processStart');
            this.viewModel.errorMessage('');

            let state = $.Deferred(),
                addressBilling = uiRegistry.get('store-braintree-card-form.address'),
                shippingId = addressBilling.addressModal.viewModel.currentShippingId(),

                // If we have a shipping ID then get the selected address otherwise use the new address.
                currentAddress = shippingId
                    ? addressBilling.addressModal.viewModel.currentAddresses().find(function (address) {
                        return address.id === shippingId;
                    }) : {
                        firstname: document.getElementById('firstname').value,
                        lastname: document.getElementById('lastname').value,
                        telephone: document.getElementById('telephone').value,
                        street: [
                            document.getElementById('street_1').value,
                            document.getElementById('street_2').value
                        ],
                        city: document.getElementById('city').value,
                        region: {
                            region_code: document.getElementById('region_id').value
                        },
                        postcode: document.getElementById('zip').value,
                        country_id: document.getElementById('country').value
                    };

            const billingAddress = {
                givenName: currentAddress.firstname,
                surname: currentAddress.lastname,
                phoneNumber: currentAddress.telephone,
                streetAddress: currentAddress.street[0],
                extendedAddress: currentAddress.street[1],
                locality: currentAddress.city,
                region: currentAddress.region.region_code,
                postalCode: currentAddress.postcode,
                countryCodeAlpha2: currentAddress.country_id
            };

            this.hostedFieldsInstance
                .tokenize({
                    vault: true,
                    billingAddress
                }).then(function (payload) {
                    this.viewModel.paymentMethodNonce(payload.nonce);
                    let threeDSEnabled = this.viewModel.isThreeDSecureEnabled();

                    const callback = () => {
                        const nonce = this.viewModel.paymentMethodNonce();

                        this.clientConfig.onPaymentMethodReceived({ nonce });
                    };

                    if (threeDSEnabled) {
                        threeDSecure.create({
                            version: 2,
                            client: this.clientInstance
                        }, function (threeDSecureErr, threeDSecureInstance) {
                            if (threeDSecureErr) {
                                console.warn(threeDSecureErr);
                                return;
                            }

                            threeDSecureInstance.verifyCard({
                                amount: this.viewModel.total(),
                                nonce: payload.nonce,
                                bin: payload.details.bin,
                                cardAddChallengeRequested: true,
                                vault: true,

                                onLookupComplete: function (data, next) {
                                    next();
                                }
                            }, function (err, response) {
                                if (err) {
                                    $('body').trigger('processStop');
                                    this.viewModel.errorMessage($t('Please try again with another form of payment.'));
                                    return state.reject($t('Please try again with another form of payment.'));
                                }

                                let liability = {
                                    shifted: response.liabilityShifted,
                                    shiftPossible: response.liabilityShiftPossible
                                };

                                if (liability.shifted || !liability.shifted && !liability.shiftPossible) {
                                    this.viewModel.paymentMethodNonce(response.nonce);
                                    state.resolve();

                                    // Validation Passed
                                    callback();

                                } else {
                                    // eslint-disable-next-line max-len
                                    this.viewModel.errorMessage($t('We could not validate your payment method. Please try again with another form of payment.'));
                                    $('body').trigger('processStop');
                                    state.reject($t('Please try again with another form of payment.'));
                                }
                            }.bind(this));
                        }.bind(this));
                    } else {
                        callback();
                    }
                }.bind(this))
                .catch(function () {
                    $('body').trigger('processStop');
                });
        }
    });

    return Component.extend(uiC);
});
