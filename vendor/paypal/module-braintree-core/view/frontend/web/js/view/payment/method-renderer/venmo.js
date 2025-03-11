define(
    [
        'Magento_Checkout/js/view/payment/default',
        'braintree',
        'braintreeDataCollector',
        'braintreeVenmo',
        'PayPal_Braintree/js/form-builder',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/translate',
        'Magento_Vault/js/view/payment/vault-enabler',
        'underscore'
    ],
    function (
        Component,
        braintree,
        dataCollector,
        venmo,
        formBuilder,
        messageList,
        fullScreenLoader,
        additionalValidators,
        $t,
        VaultEnabler,
        _
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                deviceData: null,
                paymentMethodNonce: null,
                template: 'PayPal_Braintree/payment/venmo',
                venmoInstance: null,
                vaultEnabler: null
            },

            clickVenmoBtn: function () {
                var self = this;

                if (!additionalValidators.validate()) {
                    return false;
                }

                if (!this.venmoInstance) {
                    this.setErrorMsg($t('Venmo not initialized, please try reloading.'));
                    return;
                }

                this.venmoInstance.tokenize(function (tokenizeErr, payload) {
                    if (tokenizeErr) {
                        if (tokenizeErr.code === 'VENMO_CANCELED') {
                            self.setErrorMsg($t('Venmo app is not available or the payment flow was cancelled.'));
                        } else if (tokenizeErr.code === 'VENMO_APP_CANCELED') {
                            self.setErrorMsg($t('Venmo payment flow cancelled.'));
                        } else {
                            self.setErrorMsg(tokenizeErr.message);
                        }
                    } else {
                        self.handleVenmoSuccess(payload);
                    }
                });
            },

            collectDeviceData: function (clientInstance, callback) {
                var self = this;

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
            },

            getClientToken: function () {
                return window.checkoutConfig.payment[this.getCode()].clientToken;
            },

            getCode: function () {
                return 'braintree_venmo';
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

            getPaymentMarkSrc: function () {
                return window.checkoutConfig.payment[this.getCode()].paymentMarkSrc;
            },

            getTitle: function () {
                return 'Venmo';
            },

            handleVenmoSuccess: function (payload) {
                this.setPaymentMethodNonce(payload.nonce);
                this.placeOrder();
            },

            initialize: function () {
                var self = this;

                this._super();
                this.vaultEnabler = new VaultEnabler();
                this.vaultEnabler.setPaymentCode(this.getVaultCode());

                this.vaultEnabler.isActivePaymentTokenEnabler.subscribe(function () {
                    self.venmoInstance.teardown(function () {
                        self.initVenmo();
                    });

                });
                this.initVenmo();
                return this;
            },

            isAllowed: function () {
                return window.checkoutConfig.payment[this.getCode()].isAllowed;
            },

            setErrorMsg: function (message) {
                messageList.addErrorMessage({
                    message: message
                });
            },

            setPaymentMethodNonce: function (nonce) {
                this.paymentMethodNonce = nonce;
            },

            setVenmoInstance: function (instance) {
                this.venmoInstance = instance;
            },

            isVaultEnabled: function () {
                return this.vaultEnabler.isVaultEnabled();
            },

            /**
             * @returns {String}
             */
            getVaultCode: function () {
                return window.checkoutConfig.payment[this.getCode()].vaultCode;
            },

            getPaymentMethodUsage: function () {
                return this.vaultEnabler.isActivePaymentTokenEnabler()
                    ? 'multi_use'
                    : 'single_use';
            },

            initVenmo: function () {
                var self = this;

                braintree.create({
                    authorization: self.getClientToken()
                }, function (clientError, clientInstance) {
                    if (clientError) {
                        this.setErrorMsg($t('Unable to initialize Braintree Client.'));
                        return;
                    }

                    // Collect device data
                    self.collectDeviceData(clientInstance, function () {
                        // callback from collectDeviceData
                        venmo.create({
                            client: clientInstance,
                            allowDesktop: true,
                            allowDesktopWebLogin: true,
                            mobileWebFallBack: true,
                            paymentMethodUsage: self.getPaymentMethodUsage(),
                            allowNewBrowserTab: false
                        }, function (venmoErr, venmoInstance) {
                            if (venmoErr) {
                                self.setErrorMsg($t('Error initializing Venmo: %1').replace('%1', venmoErr));
                                return;
                            }

                            if (!venmoInstance.isBrowserSupported()) {
                                console.log('Browser does not support Venmo');
                                return;
                            }

                            self.setVenmoInstance(venmoInstance);
                        });
                    });
                });
            }
        });
    }
);
