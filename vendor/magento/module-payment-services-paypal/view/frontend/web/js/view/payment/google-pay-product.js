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

    const HTTP_STATUS_OK = 200;

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
            scriptParams: {},
            buttonContainerId: 'google-pay-${ $.uid }',
            template: 'Magento_PaymentServicesPaypal/payment/google-pay',
            paymentsOrderId: null,
            paypalOrderId: null,
            sdkLoaded: null,
            sdkParamsKey: 'googlepay',
            paymentTypeIconTitle: $t('Pay with Google Pay'),
            requestProcessingError: $t('Something went wrong with your request. Please try again later.'),
            notEligibleErrorMessage: $t('This payment option is currently unavailable.'),
            productFormSelector: '#product_addtocart_form'
        },


        /**
         * @inheritdoc
         */
        initialize: function (config, element) {
            _.bindAll(this, 'initGooglePayButton', 'onClick', 'afterUpdateQuote',
                'catchError', 'beforeCreateOrder', 'afterOnAuthorize', 'onCancel');
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
                beforeCreateOrder: this.beforeCreateOrder,
                catchCreateOrder: this.catchError,
                onError: this.catchError,
                buttonContainerId: this.buttonContainerId,
                afterUpdateQuote: this.afterUpdateQuote,
                shippingAddressRequired: !this.isVirtual,
                styles: this.styles,
                afterOnAuthorize: this.afterOnAuthorize,
                onCancel: this.onCancel,
                mode: this.googlePayMode,
                getOrderDetailsUrl: this.getOrderDetailsUrl,
                threeDSMode: this.threeDSMode,
            });

            this.googlePayButton.sdkLoaded
                .then(this.googlePayButton.initGoogleSDK);
        },

        afterUpdateQuote: function (data) {
            window.location = data.redirectUrl;
            this.googlePayButton.showLoader(false);
        },

        onClick: function () {
            var $form = $(this.productFormSelector);

            if ($form.data('mageValidation')) {
                this.formValid = $form.validation('isValid');
            }

            if (this.formValid) {
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
            }
        },

        /**
         * Catch errors.
         *
         * @param {*} error
         */
        catchError: function (error) {
            console.log(error);
            this.googlePayButton.showLoader(false);

            if (this.isErrorDisplayed) {
                return;
            }

            if (error.hidden === undefined || !error.hidden) {
                this.addMessage(this.requestProcessingError);
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
         * Before create order.
         *
         * @return {String}
         */
        beforeCreateOrder: function () {
            this.isErrorDisplayed = false;

            if (this.formInvalid) {
                throw new Error('Form is Invalid');
            }

            let xhr = new XMLHttpRequest();
            xhr.open('POST', this.addToCartUrl, false);
            xhr.send(new FormData($(this.productFormSelector)[0]));

            if (xhr.status !== HTTP_STATUS_OK) {
                throw new Error('Request failed');
            } else {
                try {
                    let result = JSON.parse(xhr.responseText);

                    if (typeof result.success !== 'undefined') {
                        refreshCustomerData(this.addToCartUrl);
                        return result.success;
                    }
                } catch (parseError) {
                    throw new Error('Failed to parse response JSON: ' + parseError.message);
                }
            }
        },

        afterOnAuthorize: function (data) {
            window.location = data.redirectUrl;
            this.googlePayButton.showLoader(false);
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
