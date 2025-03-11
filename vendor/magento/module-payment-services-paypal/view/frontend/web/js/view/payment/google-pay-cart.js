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
    'mage/translate',
    'Magento_Customer/js/customer-data',
    'Magento_PaymentServicesPaypal/js/view/errors/response-error',
    'Magento_PaymentServicesPaypal/js/view/payment/methods/google-pay'
], function (_, $, utils, Component, $t, customerData, ResponseError, GooglePayButton) {
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
            sdkNamespace: 'paypalGooglePay',
            sdkParamsKey: 'googlepay',
            buttonContainerId: 'google-pay-${ $.uid }',
            paymentActionError: $t('Something went wrong with your request. Please try again later.'),
            isErrorDisplayed: false
        },

        /**
         * @inheritdoc
         */
        initialize: function (config, element) {
            _.bindAll(this, 'initGooglePayButton', 'onClick',
                'afterOnAuthorize', 'catchError', 'onCancel');
            config.uid = utils.uniqueid();
            this._super();
            this.element = element;
            this.element.id = this.buttonContainerId;
            this.getSdkParams()
                .then(this.initGooglePayButton)
                .catch(console.log);

            return this;
        },

        initGooglePayButton: function () {
            this.googlePayButton = new GooglePayButton({
                scriptParams: this.sdkParams,
                createOrderUrl: this.createOrderUrl,
                updateQuoteUrl: this.authorizeOrderUrl,
                onClick: this.onClick,
                catchCreateOrder: this.catchError,
                onError: this.catchError,
                buttonContainerId: this.buttonContainerId,
                afterOnAuthorize: this.afterOnAuthorize,
                shippingAddressRequired: !this.isVirtual,
                styles: this.styles,
                onCancel: this.onCancel,
                mode: this.googlePayMode,
                getOrderDetailsUrl: this.getOrderDetailsUrl,
                threeDSMode: this.threeDSMode,
            });

            this.googlePayButton.sdkLoaded
                .then(this.googlePayButton.initGoogleSDK);
        },

        afterOnAuthorize: function (data) {
            window.location = data.redirectUrl;
            this.googlePayButton.showLoader(false);
        },

        onClick: function () {
            this.isErrorDisplayed = false;

            this.googlePayButton.showLoaderAsync(true)
                .then(() => {
                    return this.googlePayButton.createOrder();
                })
                .then(() => {
                    refreshCustomerData(this.createOrderUrl);
                })
                .catch(error => {
                    this.catchError(error);
                });
        },

        /**
         * Catch errors.
         *
         * @param {*} error
         */
        catchError: function (error) {
            var message = error instanceof ResponseError ? error.message : this.paymentActionError;

            console.log(error);

            this.googlePayButton.showLoader(false);

            if (this.isErrorDisplayed) {
                return;
            }

            if (error.hidden === undefined || !error.hidden) {
                this.addMessage(message);
            }

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
         * Redirect to cart on cancel.
         *
         * @param {Object} data
         * @param {Object} actions
         */
        onCancel: function () {
            customerData.invalidate(['cart']);
            window.location = this.cancelUrl;
        }
    });
});
