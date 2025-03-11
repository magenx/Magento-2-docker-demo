/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* eslint-disable no-undef */
define([
    'jquery',
    'underscore',
    'uiComponent',
    'scriptLoader',
    'Magento_PaymentServicesPaypal/js/lib/script-loader',
    'mage/translate',
    'mage/cookies'
], function ($, _, Component, loadSdkScript, scriptLoader, $t) {
    'use strict';

    const HTTP_STATUS_CREATED = 201;

    const googleSDKSrc = 'https://pay.google.com/gp/p/js/pay.js',
        baseRequest = {
            apiVersion: 2,
            apiVersionMinor: 0,
            callbackIntents: ['PAYMENT_AUTHORIZATION'],
            emailRequired: true,
            shippingAddressParameters: {phoneNumberRequired: true}
        };

    /**
     * Create order request.
     *
     * @param {String} url
     * @param {Object} payPalOrderData
     * @param {FormData} orderData
     * @param {String | false} threeDSMode
     * @return {Object}
     */
    var performCreateOrder = function (url, payPalOrderData, orderData, threeDSMode) {

            orderData = orderData || new FormData();
            orderData.append('form_key', $.mage.cookies.get('form_key'));
            orderData.append('payment_source', payPalOrderData['paymentSource']);

            if (threeDSMode) {
                orderData.append('three_ds_mode', threeDSMode);
            }

            let xhr = new XMLHttpRequest();
            xhr.open('POST', url, false);
            xhr.send(orderData);

            if (xhr.status !== HTTP_STATUS_CREATED) {
                throw new Error('Request failed');
            } else {
                return JSON.parse(xhr.responseText);
            }
        },

        /**
         * Payment authorization request.
         *
         * @return {Promise<Object>}
         */
        performOnAuthorize = function (url, data) {
            var orderData = new FormData();

            orderData.append('form_key', $.mage.cookies.get('form_key'));
            orderData.append('paypal_order_id', data.orderID);

            return fetch(url, {
                method: 'POST',
                headers: {},
                body: orderData,
                credentials: 'same-origin'
            }).then(function (response) {
                return response.json();
            });
        };

    return Component.extend({
        defaults: {
            sdkNamespace: 'paypal',
            paypal: null,
            paymentSource: 'googlepay',
            createOrderUrl: '',
            updateQuoteUrl: '',
            instance: null,
            scriptParams: {},
            allowedPaymentMethods: null,
            merchantInfo: null,
            buttonContainerId: null,
            paypalOrderId: null,
            eligible: false,
            mode: 'TEST',
            shippingAddressRequired: true,
            getOrderDetailsUrl: '',
            threeDSMode: '',
        },

        /** @inheritdoc */
        initialize: function () {
            _.bindAll(this, 'createOrder', 'onApprove', 'onError', 'initGoogleSDK', 'performAuthorization',
                'onClick', 'beforeOnAuthorize', 'afterOnAuthorize', 'onCancel');
            this._super();
            this.sdkLoaded = Promise.all([this.loadPayPalSDK(), this.loadGoogleSDK()]);

            return this;
        },

        loadPayPalSDK: function () {
            return loadSdkScript(this.scriptParams, this.sdkNamespace)
                .then(function (sdkScript) {
                    this.paypal = sdkScript;
                }.bind(this))
                .catch(function (e) {
                    this.onError(e);
                });
        },

        loadGoogleSDK: function () {
            return scriptLoader.loadCustom({url: googleSDKSrc})
                .catch(function (e) {
                    this.onError(e);
                });
        },

        initGoogleSDK: function () {
            return this.getGooglePayConfig()
                .then(config =>
                    this.getGooglePaymentsClient()
                        .isReadyToPay(this.getGoogleIsReadyToPayRequest(config.allowedPaymentMethods))
                        .then(function (response) {
                            if (response.result) {
                                this.renderGooglePayButton();
                            }
                        }.bind(this))
                ).catch(function (e) {
                    this.onError({
                        hidden: true,
                        error: e
                    });
                }.bind(this));
        },

        getGooglePaymentsClient: function () {
            if (this.instance === null) {
                this.instance = new google.payments.api.PaymentsClient({
                    environment: this.mode,
                    paymentDataCallbacks: {
                        onPaymentAuthorized: this.performAuthorization
                    }
                });
            }
            return this.instance;
        },

        showPopup: function (paymentData) {
            this.getGooglePaymentDataRequest(paymentData)
                .then((data) => {
                    this.getGooglePaymentsClient()
                        .loadPaymentData(data)
                        .catch(this.onCancel);
                }).catch((error) => this.onError(error));
        },

        onCancel: function () {
            this.showLoader(false);
        },

        getGooglePaymentDataRequest: async function (transactionInfo) {
            const paymentDataRequest = Object.assign({}, baseRequest),
                {allowedPaymentMethods, merchantInfo} = await this.getGooglePayConfig();

            paymentDataRequest.allowedPaymentMethods = allowedPaymentMethods;
            paymentDataRequest.transactionInfo = transactionInfo;
            paymentDataRequest.merchantInfo = merchantInfo;
            paymentDataRequest.shippingAddressRequired = this.shippingAddressRequired;

            return paymentDataRequest;
        },

        getGooglePayConfig: async function () {
            if (this.allowedPaymentMethods === null || this.merchantInfo === null) {
                const googlePayConfig = await this.paypal.Googlepay().config();

                this.allowedPaymentMethods = googlePayConfig.allowedPaymentMethods;
                this.merchantInfo = googlePayConfig.merchantInfo;
            }

            return {
                allowedPaymentMethods: this.allowedPaymentMethods,
                merchantInfo: this.merchantInfo
            };
        },

        getGoogleIsReadyToPayRequest: function (allowedPaymentMethods) {
            return Object.assign({}, baseRequest, {
                allowedPaymentMethods: allowedPaymentMethods
            });
        },

        renderGooglePayButton: function () {
            const buttonContainer = $('#' + this.buttonContainerId);

            let buttonProps = {
                onClick: this.onClick,
                buttonColor: this.styles.button_color,
                buttonType: this.styles.button_type
            };

            buttonProps.buttonSizeMode = 'fill';

            if (this.styles.button_custom_height) {
                buttonContainer.height(this.styles.button_custom_height);
            }

            buttonContainer.append(
                this.getGooglePaymentsClient().createButton(buttonProps)
            );
            this.eligible = true;
        },

        enableButton: function () {
            $('#' + this.buttonContainerId).find('button').prop('disabled', false);
        },

        disableButton: function () {
            $('#' + this.buttonContainerId).find('button').prop('disabled', true);
        },

        performAuthorization: function (paymentData) {
            return new Promise(function (resolve) {
                this.processPayment(paymentData)
                    .then(resolve)
                    .catch(function () {
                        this.onError(new Error('couldn\'t process payment'));
                        resolve({transactionState: 'ERROR'});
                    }.bind(this));
            }.bind(this));
        },

        processPayment: async function (paymentData) {
            try {
                const {status} = await this.paypal.Googlepay().confirmOrder({
                    orderId: this.paypalOrderId,
                    paymentMethodData: paymentData.paymentMethodData,
                    shippingAddress: paymentData.shippingAddress,
                    email: paymentData.email
                });

                if (status === 'APPROVED') {
                    this.onApprove(paymentData);
                    return {transactionState: 'SUCCESS'};
                }

                if (status === 'PAYER_ACTION_REQUIRED') {
                    this.paypal.Googlepay().initiatePayerAction({orderId: this.paypalOrderId}).then(
                        async () => {
                            return fetch(`${this.getOrderDetailsUrl}`, {
                                method: 'GET'
                            }).then((res) => {
                                return res.json();
                            }).then((data) => {
                                if (data.response['is_successful'] && data.response['paypal-order']) {
                                    const order = data.response['paypal-order'];
                                    let authenticationResult = order.payment_source_details.card.authentication_result;
                                    if (authenticationResult
                                        && (
                                            authenticationResult.liability_shift === 'POSSIBLE'
                                            || authenticationResult.liability_shift === "YES"
                                            || authenticationResult.liability_shift === undefined
                                        )
                                    ) {
                                        this.onApprove(paymentData);
                                        return {transactionState: 'SUCCESS'};
                                    } else {
                                        this.onError(new Error('couldn\'t approve order'));
                                        return {transactionState: 'ERROR'};
                                    }
                                }
                            }).catch((error) => {
                                console.log("ERROR: ", error)
                            })
                        }
                    ).catch((error) => {
                        console.log("ERROR: ", error)
                    });
                    return;
                }

                this.onError(new Error('couldn\'t approve order'));
                return {transactionState: 'ERROR'};
            } catch (err) {
                this.onError(err);
                return {
                    transactionState: 'ERROR',
                    error: {
                        message: err.message
                    }
                };
            }
        },

        onClick: function () {
        },

        /**
         * Calls before create order.
         */
        beforeCreateOrder: function () {
        },

        /**
         * Create order.
         *
         * @return {String}
         */
        createOrder: function () {
            let data = {'paymentSource': this.paymentSource};

            try {
                this.beforeCreateOrder();
                let orderData = performCreateOrder(this.createOrderUrl, data, null, this.threeDSMode);
                this.paypalOrderId = this.afterCreateOrder(orderData);
                return this.paypalOrderId;
            } catch (error) {
                return this.catchCreateOrder(error);
            }
        },

        /**
         * After order created.
         *
         * @param {Object} data
         * @return {String}
         */
        afterCreateOrder: function (data) {
            if (data.response['paypal-order'] && data.response['paypal-order']['mp_order_id']) {
                this.paymentsOrderId = data.response['paypal-order']['mp_order_id'];
                this.paypalOrderId = data.response['paypal-order'].id;

                this.showPopup({
                    displayItems: [],
                    currencyCode: data.response['paypal-order']['currency_code'],
                    totalPriceStatus: 'FINAL',
                    totalPrice: Number(data.response['paypal-order']['amount']).toString(),
                    totalPriceLabel: $t('Total')
                });

                return this.paypalOrderId;
            }

            throw new Error();
        },

        /**
         * Catch error on order creation.
         */
        catchCreateOrder: function () {
        },

        /**
         * On payment approve.
         *
         * @param {Object} data
         * @param {Object} actions
         * @return {Promise}
         */
        onApprove: function () {
            const data = {orderID: this.paypalOrderId};

            return this.beforeOnAuthorize()
                .then(performOnAuthorize.bind(this, this.updateQuoteUrl, data))
                .then(this.afterOnAuthorize)
                .catch(this.onError);
        },

        beforeOnAuthorize: function () {
            return Promise.resolve();
        },

        afterOnAuthorize: function () {
            return Promise.resolve();
        },

        /**
         * Calls when error happened on paypal side.
         *
         * @param {Error} error
         */
        onError: function (error) {
            console.log('Error: ', error.message);
        },

        isEligible: function () {
            return this.eligible;
        },

        /**
         * Async Show/hide loader
         *
         * @param {Boolean} show
         */
        showLoaderAsync: function (show) {
            return new Promise(function (resolve, reject) {
                var event = show ? 'processStart' : 'processStop';
                $('body').trigger(event);

                // Set minimum time for loader to show
                setTimeout(() => {
                    resolve();
                }, 10);
            });
        },

        /**
         * Show/hide loader.
         *
         * @param {Boolean} show
         */
        showLoader: function (show) {
            var event = show ? 'processStart' : 'processStop';

            $('body').trigger(event);
        },
    });
});
