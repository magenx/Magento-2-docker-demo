/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* eslint-disable no-undef */
define([
    'underscore',
    'jquery',
    'mageUtils',
    'Magento_PaymentServicesPaypal/js/view/payment/paypal-abstract',
    'scriptLoader',
    'Magento_PaymentServicesPaypal/js/view/payment/methods/smart-buttons',
    'mage/translate',
    'Magento_Customer/js/customer-data',
    'Magento_PaymentServicesPaypal/js/view/errors/response-error'
], function (_, $, utils, Component, loadSdkScript, SmartButtons, $t, customerData, ResponseError) {
    'use strict';

    var refreshCustomerData = function (url) {
        // Trigger ajaxComplete event to update customer data
        customerData.onAjaxComplete(
            {},
            {
                type: 'POST',
                url: url,
            }
        );
    }

    return Component.extend({
        defaults: {
            sdkNamespace: 'paypalCart',
            buttonsContainerId: 'smart-buttons-${ $.uid }',
            element: null,
            paymentActionError: $t('Something went wrong with your request. Please try again later.'),
            isErrorDisplayed: false
        },

        /**
         * @inheritdoc
         */
        initialize: function (config, element) {
            _.bindAll(this, 'renderButtons', 'initSmartButtons', 'catchError', 'beforeCreateOrder', 'afterCreateOrder',
                'beforeOnAuthorize', 'afterOnAuthorize', 'onCancel');
            config.uid = utils.uniqueid();
            this._super();
            this.element = element;
            this.element.id = this.buttonsContainerId;
            this.getSdkParams()
                .then(this.initSmartButtons)
                .then(this.renderButtons)
                .catch(function (e) {
                    console.log(e);
                });

            return this;
        },

        /**
         * Create instance of smart buttons.
         */
        initSmartButtons: function () {
            this.buttons = new SmartButtons({
                sdkNamespace: this.sdkNamespace,
                scriptParams: this.sdkParams,
                styles: this.styles,
                createOrderUrl: this.createOrderUrl,
                authorizeOrderUrl: this.authorizeOrderUrl,
                beforeCreateOrder: this.beforeCreateOrder,
                afterCreateOrder: this.afterCreateOrder,
                catchCreateOrder: this.catchError,
                finallyCreateOrder: this.showLoader.bind(this, false),
                beforeOnAuthorize: this.beforeOnAuthorize,
                afterOnAuthorize: this.afterOnAuthorize,
                catchOnAuthorize: this.catchError,
                finallyOnAuthorize: this.showLoader.bind(this, false),
                onError: this.catchError,
                onCancel: this.onCancel
            });
        },

        /**
         * Render buttons
         */
        renderButtons: function () {
            this.buttons.sdkLoaded.then(function () {
                this.buttons && this.buttons.render('#' + this.buttonsContainerId);
            }.bind(this)).catch(function () {
                console.log('Error: Failed to load PayPal SDK script!');
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

        /**
         * Catch errors.
         *
         * @param {*} error
         */
        catchError: function (error) {
            var message = error instanceof ResponseError ? error.message : this.paymentActionError;

            this.showLoader(false);

            if (this.isErrorDisplayed) {
                return;
            }
            this.addMessage(message);
            this.isErrorDisplayed = true;
        },

        /**
         * Add message to customer data.
         *
         * @param {String} message
         * @param {String} [type]
         */
        addMessage: function (message, type) {
            type = type || 'error';
            customerData.set('messages', {
                messages: [{
                    type: type,
                    text: message
                }],
                'data_id': Math.floor(Date.now() / 1000)
            });
        },

        /**
         * Before create order.
         *
         * @return {Promise}
         */
        beforeCreateOrder: function () {
            this.isErrorDisplayed = false;
            this.showLoader(true);

            return Promise.resolve();
        },

        /**
         * After order id created.
         *
         * @param {Object} res
         * @return {*}
         */
        afterCreateOrder: function (res) {
            if (res.response['is_successful']) {
                refreshCustomerData(this.createOrderUrl);

                return res.response['paypal-order'].id;
            }

            throw new ResponseError(res.response.error);
        },

        /**
         * Before onAuthorize execute
         *
         * @param {Object} data
         * @return {Promise}
         */
        beforeOnAuthorize: function (data) {
            this.showLoader(true);

            return Promise.resolve(data);
        },

        /**
         * After onAuthorize execute
         *
         * @param {Object} res
         * @param {Object} actions
         * @return {*}
         */
        afterOnAuthorize: function (res, actions) {
            if (res.success) {
                return actions.redirect(res.redirectUrl);
            }

            throw new ResponseError(res.error);
        },

        /**
         * Redirect to cart on cancel.
         *
         * @param {Object} data
         * @param {Object} actions
         */
        onCancel: function (data, actions) {
            customerData.invalidate(['cart']);
            actions.redirect(this.cancelUrl);
        }
    });
});
