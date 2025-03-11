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
    'mage/cookies'
], function ($, _, Component, loadSdkScript) {
    'use strict';

    /**
     * Create order request.
     *
     * @param {String} url
     * @param {Object} payPalOrderData
     * @param {FormData} orderData
     * @return {Promise<Object>}
     */
    var performCreateOrder = function (url, payPalOrderData, orderData) {
            orderData = orderData || new FormData();
            orderData.append('form_key', $.mage.cookies.get('form_key'));
            orderData.append('payment_source', payPalOrderData['paymentSource']);

            return fetch(url, {
                method: 'POST',
                headers: {},
                body: orderData || new FormData(),
                credentials: 'same-origin'
            }).then(function (response) {
                return response.json();
            });
        },

        /**
         * Payment authorization request.
         *
         * @return {Promise<Object>}
         */
        performAuthorization = function (url, data) {
            var orderData = new FormData();

            orderData.append('form_key', $.mage.cookies.get('form_key'));
            orderData.append('paypal_order_id', data.orderID);
            orderData.append('paypal_payer_id', data.payerID);

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
            paymentSource: '',
            creatOrderUrl: '',
            authorizeOrderUrl: '',
            style: {},
            paymentRequest: {
                applepay: {
                    requiredShippingContactFields: []
                }
            },
            element: null,
            instance: null
        },

        /** @inheritdoc */
        initialize: function () {
            _.bindAll(this, 'createOrder', 'onApprove', 'onError', 'onCancel');
            this._super();
            this.sdkLoaded = loadSdkScript(this.scriptParams, this.sdkNamespace).then(function (sdkScript) {
                this.paypal = sdkScript;
            }.bind(this));

            return this;
        },

        /**
         * In the case where the button color is not supported by Apple (black or white)
         * Map the button color to black (same behavior as PayPal SDK script)
         *
         * @param buttonStyles
         * @returns {(*&{color: string})|*}
         */
        mapButtonColorForApplePay: function (buttonStyles) {
            var buttonColor = buttonStyles.color;

            if (buttonColor === 'black' || buttonColor === 'white') {
                return buttonStyles;
            }
            return {
                ...buttonStyles,
                color: 'black'
            };
        },

        /**
         * Render Smart Buttons.
         *
         * @param {HTMLElement} element
         * @return {*}
         */
        render: function (element) {
            var buttonsConfig;

            if (typeof this.paypal === 'undefined' || !this.paypal.Buttons) {
                return null;
            }

            if (element) {
                this.element = element;
            }

            buttonsConfig = {
                element: this.element,
                paymentRequest: this.paymentRequest,
                style: this.styles,
                onClick: this.onClick,
                createOrder: this.createOrder,
                onApprove: this.onApprove,
                onError: this.onError,
                onCancel: this.onCancel,
                onInit: this.onInit
            };

            if (this.onShippingChange) {
                buttonsConfig.onShippingChange = this.onShippingChange.bind(this);
            }
            if (this.fundingSource) {
                buttonsConfig.fundingSource = this.fundingSource;
                if (this.fundingSource === 'applepay') {
                    buttonsConfig.style = this.mapButtonColorForApplePay(this.styles);
                }
            }

            this.instance = this.paypal.Buttons(buttonsConfig);

            if (this.instance.isEligible()) {
                this.instance.render(this.element);
            }

            return this.instance;
        },

        /**
         * Calls when smart buttons initializing
         */
        onInit: function () {
        },

        /**
         * Calls when user click paypal button.
         */
        onClick: function () {
        },

        /**
         * Calls before create order.
         *
         * @return {Promise}
         */
        beforeCreateOrder: function () {
            return Promise.resolve();
        },

        /**
         * Create order.
         *
         * @return {Promise}
         */
        createOrder: function (data) {
            this.paymentSource = data['paymentSource'];

            return this.beforeCreateOrder()
                .then(performCreateOrder.bind(this, this.createOrderUrl, data))
                .then(function (orderData) {
                    return this.afterCreateOrder(orderData);
                }.bind(this)).catch(function (error) {
                    return this.catchCreateOrder(error);
                }.bind(this)).finally(function (error) {
                    return this.finallyCreateOrder(error);
                }.bind(this));
        },

        /**
         * After order created.
         *
         * @param {Object} data
         * @return {*}
         */
        afterCreateOrder: function (data) {
            return data.orderId;
        },

        /**
         * Catch error on order creation.
         */
        catchCreateOrder: function () {
        },

        /**
         * Finally for order creation.
         *
         */
        finallyCreateOrder: function () {
        },

        /**
         * Before authorization call.
         *
         * @return {Promise}
         */
        beforeOnAuthorize: function (data) {
            return Promise.resolve(data);
        },

        /**
         * On payment approve.
         *
         * @param {Object} data
         * @param {Object} actions
         * @return {Promise}
         */
        onApprove: function (data, actions) {
            return this.beforeOnAuthorize(data, actions)
                .then(performAuthorization.bind(this, this.authorizeOrderUrl))
                .then(function (authData) {
                    return this.afterOnAuthorize(authData, actions);
                }.bind(this)).catch(function (error) {
                    return this.catchOnAuthorize(error);
                }.bind(this)).finally(function (error) {
                    return this.finallyOnAuthorize(error);
                }.bind(this));
        },

        /**
         * Calls after successful payment authorization.
         *
         * @param {Object} authData
         * @return {*}
         */
        afterOnAuthorize: function (authData) {
            return authData;
        },

        /**
         * Catch payment authorization errors.
         */
        catchOnAuthorize: function () {
        },

        /**
         * Finally for payment authorization.
         */
        finallyOnAuthorize: function () {
        },

        /**
         * Calls when shipping address chenges..
         *
         * @param {Object} data
         */
        onShippingChange: undefined,

        /**
         * Calls when error happened on paypal side.
         *
         * @param {Error} error
         */
        onError: function (error) {
            console.log('Error: ', error.message);
        },

        /**
         * Calls when user canceled payment.
         */
        onCancel: function () {}
    });
});
