define(
    [
        'Magento_Checkout/js/view/payment/default',
        'ko',
        'jquery',
        'braintree',
        'braintreeDataCollector',
        'braintreeAch',
        'PayPal_Braintree/js/form-builder',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/action/select-billing-address',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/quote',
        'mage/translate',
        'Magento_Vault/js/view/payment/vault-enabler',
        'underscore'
    ],
    function (
        Component,
        ko,
        $,
        braintree,
        dataCollector,
        ach,
        formBuilder,
        messageList,
        selectBillingAddress,
        fullScreenLoader,
        quote,
        $t,
        VaultEnabler,
        _
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                deviceData: null,
                paymentMethodNonce: null,
                template: 'PayPal_Braintree/payment/ach',
                achInstance: null,
                routingNumber: ko.observable(''),
                accountNumber: ko.observable(''),
                accountType: ko.observable('checking'),
                ownershipType: ko.observable('personal'),
                firstName: ko.observable(''),
                lastName: ko.observable(''),
                businessName: ko.observable(''),
                hasAuthorization: ko.observable(false),
                business: ko.observable(false), // for ownership type
                personal: ko.observable(true), // for ownership type
                vaultEnabler: null
            },

            clickAchBtn: function () {
                if (!this.validateForm('#' + this.getCode() + '-form')) {
                    return;
                }

                fullScreenLoader.startLoader();

                let self = this,

                    billingAddress = quote.billingAddress(),

                    regionCode,

                    bankDetails = {
                        routingNumber: self.routingNumber(),
                        accountNumber: self.accountNumber(),
                        accountType: self.accountType(),
                        ownershipType: self.ownershipType(),
                        billingAddress: {
                            streetAddress: billingAddress.street[0],
                            extendedAddress: billingAddress.street[1],
                            locality: billingAddress.city,
                            region: billingAddress.regionCode,
                            postalCode: billingAddress.postcode
                        }
                    },

                    mandateText = document.getElementById(self.isVaultActive()
                        ? 'braintree-ach-mandate-vault'
                        : 'braintree-ach-mandate'
                    ).textContent;

                if (bankDetails.ownershipType === 'personal') {
                    bankDetails.firstName = self.firstName();
                    bankDetails.lastName = self.lastName();
                } else {
                    bankDetails.businessName = self.businessName();
                }

                // if no region code is available, lets find one!
                if (typeof billingAddress.regionCode === 'undefined') {
                    $.get('/rest/V1/directory/countries/' + billingAddress.countryId).done(function (data) {
                        if (typeof data.available_regions !== 'undefined') {
                            data.available_regions.forEach(function (availableRegion) {
                                if (availableRegion.id === billingAddress.regionId) {
                                    regionCode = availableRegion.code;
                                    bankDetails.billingAddress.region = regionCode;
                                    self.tokenizeAch(bankDetails, mandateText);
                                }
                            });
                        } else {
                            fullScreenLoader.stopLoader();
                            self.tokenizeAch(bankDetails, mandateText);
                        }
                    }).fail(function () {
                        fullScreenLoader.stopLoader();
                    });
                } else {
                    self.tokenizeAch(bankDetails, mandateText);
                }
            },

            tokenizeAch: function (bankDetails, mandateText) {
                let self = this;

                self.achInstance.tokenize({
                    bankDetails: bankDetails,
                    mandateText: mandateText
                }, function (tokenizeErr, tokenizedPayload) {
                    if (tokenizeErr) {
                        let error = 'There was an error with the provided bank details. Please check and try again.';

                        self.setErrorMsg($t(error));
                        self.hasAuthorization(false);
                        fullScreenLoader.stopLoader();
                    } else {
                        fullScreenLoader.stopLoader();
                        self.handleAchSuccess(tokenizedPayload);
                    }
                });
            },

            getClientToken: function () {
                return window.checkoutConfig.payment[this.getCode()].clientToken;
            },

            getCode: function () {
                return 'braintree_ach_direct_debit';
            },

            getStoreName: function () {
                return window.checkoutConfig.payment[this.getCode()].storeName;
            },

            getData: function () {
                let data = {
                    'method': this.getCode(),
                    'additional_data': {
                        'payment_method_nonce': this.paymentMethodNonce,
                        'device_data': this.deviceData
                    }
                };

                data['additional_data'] = _.extend(data['additional_data'], this.additionalData);
                this.vaultEnabler.visitAdditionalData(data);

                return data;
            },

            getTitle: function () {
                return 'ACH Direct Debit';
            },

            handleAchSuccess: function (payload) {
                this.setPaymentMethodNonce(payload.nonce);
                this.placeOrder();
            },

            initialize: function () {
                this._super();
                this.vaultEnabler = new VaultEnabler();
                this.vaultEnabler.setPaymentCode(this.getVaultCode());

                let self = this;

                this.vaultEnabler.isActivePaymentTokenEnabler.subscribe(function () {
                    self.achInstance.teardown(function () {
                        self.initAch();
                    });
                });

                this.initAch();

                return this;
            },

            isAllowed: function () {
                return window.checkoutConfig.payment[this.getCode()].isAllowed;
            },

            /**
             * Change the account type.
             *
             * @param data
             * @param event
             */
            changeOwnershipType: function (data, event) {
                let self = this;

                if (event.currentTarget.value === 'business') {
                    self.business(true);
                    self.personal(false);
                } else {
                    self.business(false);
                    self.personal(true);
                }
            },

            /**
             * Is Business type account.
             *
             * @returns {Boolean}
             */
            isBusiness: function () {
                return this.business;
            },

            /**
             * Is Personal type account.
             *
             * @returns {Boolean}
             */
            isPersonal: function () {
                return this.personal;
            },

            /**
             * Get the account holder name input.
             *
             * @returns {String}
             */
            getAccountHolderName: function () {
                if (this.firstName() !== '' || this.lastName() !== '') {
                    return this.firstName() + ' ' + this.lastName();
                }

                return 'XXXX';
            },

            /**
             * Get the account number input.
             *
             * @returns {String}
             */
            getAccountNumber: function () {
                return this.accountNumber() !== '' ? this.accountNumber() : 'XXXX';
            },

            /**
             * Get the Bank Routing Number input.
             *
             * @returns {String}
             */
            getRoutingNumber: function () {
                return this.routingNumber() !== '' ? this.routingNumber() : 'XXXX';
            },

            /**
             * Get the quote totals value.
             *
             * @returns {String}
             */
            getGrandTotal: function () {
                let totals = quote.getTotals()();

                if (totals) {
                    return totals['grand_total'];
                }

                return quote['grand_total'];
            },

            /**
             * Get the current date in US format (ACH is US only).
             *
             * @returns {String}
             */
            getCurrentDate: function () {
                const today = new Date();

                return today.toLocaleDateString('en-US');
            },

            setErrorMsg: function (message) {
                messageList.addErrorMessage({
                    message: message
                });
            },

            setPaymentMethodNonce: function (nonce) {
                this.paymentMethodNonce = nonce;
            },

            /**
             * Set the ACH instance.
             *
             * @param {*} instance
             */
            setAchInstance: function (instance) {
                this.achInstance = instance;
            },

            /**
             * Validate ACH form.
             *
             * @param {*} form
             * @returns {*|jQuery}
             */
            validateForm: function (form) {
                return $(form).validation() && $(form).validation('isValid');
            },

            /**
             * Get ACH's should vault checkbox element ID.
             *
             * @returns {String}
             */
            getVaultCheckboxId: function () {
                return this.getCode() + '_enable_vault';
            },

            /**
             * Check whether Vault is enabled.
             *
             * @returns {Boolean}
             */
            isVaultEnabled: function () {
                return this.vaultEnabler.isVaultEnabled();
            },

            /**
             * Is Vault enabled & vaulting payment active (checked)
             *
             * @returns {Boolean}
             */
            isVaultActive: function () {
                return this.isVaultEnabled() && this.vaultEnabler.isActivePaymentTokenEnabler();
            },

            /**
             * Get ACH vault payment method code.
             *
             * @returns {String}
             */
            getVaultCode: function () {
                return window.checkoutConfig.payment[this.getCode()]['vaultCode'];
            },

            /**
             * Initialize ACH component.
             *
             * @returns {void}
             */
            initAch: function () {
                let self = this;

                braintree.create({
                    authorization: self.getClientToken()
                }, function (clientError, clientInstance) {
                    if (clientError) {
                        this.setErrorMsg($t('Unable to initialize Braintree Client.'));
                        return;
                    }

                    /* Collect device data */
                    self.collectDeviceData(clientInstance, function () {
                        /* callback from collectDeviceData */
                        ach.create({
                            client: clientInstance
                        }, function (achErr, achInstance) {
                            if (achErr) {
                                self.setErrorMsg($t('Error initializing ACH: %1').replace('%1', achErr));
                                return;
                            }

                            self.setAchInstance(achInstance);
                        });
                    });
                });
            },

            /**
             * Collect device data.
             *
             * @param clientInstance
             * @param {Function} callback
             * @returns {void}
             */
            collectDeviceData: function (clientInstance, callback) {
                let self = this;

                dataCollector.create({
                    client: clientInstance,
                    paypal: true
                }, function (dataCollectorErr, dataCollectorInstance) {
                    if (dataCollectorErr) {
                        return;
                    }

                    self.deviceData = dataCollectorInstance.deviceData;
                    callback();
                });
            }
        });
    }
);
