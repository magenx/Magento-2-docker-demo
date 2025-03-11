/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* eslint-disable no-undef */
define([
    'Magento_Checkout/js/view/payment/default',
    'jquery',
    'underscore',
    'mageUtils',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/full-screen-loader',
    'mage/translate',
    'Magento_PaymentServicesPaypal/js/view/payment/methods/apple-pay',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Checkout/js/action/set-billing-address',
    'Magento_Ui/js/model/messageList',
    'Magento_Customer/js/customer-data'
], function (
    Component,
    $,
    _,
    utils,
    quote,
    fullScreenLoader,
    $t,
    ApplePayButton,
    additionalValidators,
    setBillingAddressAction,
    globalMessageList,
    customerData
) {
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
            sdkNamespace: 'paypalApplePay',
            fundingSource: 'applepay',
            buttonContainerId: 'apple-pay-${ $.uid }',
            template: 'Magento_PaymentServicesPaypal/payment/apple-pay',
            isAvailable: false,
            isButtonRendered: false,
            grandTotalAmount: null,
            paymentsOrderId: null,
            paypalOrderId: null,
            paymentTypeIconTitle: $t('Pay with Apple Pay'),
            requestProcessingError: $t('Error happened when processing the request. Please try again later.'),
            notEligibleErrorMessage: $t('This payment option is currently unavailable.'),
            paymentTypeIconUrl: window.checkoutConfig.payment['payment_services_paypal_apple_pay'].paymentTypeIconUrl
        },

        /**
         * @inheritdoc
         */
        initialize: function (config) {
            _.bindAll(this, 'catchError', 'beforeCreateOrder', 'afterCreateOrder', 'placeOrder', 'onClick');
            config.uid = utils.uniqueid();
            this._super();
            this.initApplePayButton();

            return this;
        },

        /**
         * Initialize observables
         *
         * @returns {Component} Chainable.
         */
        initObservable: function () {
            this._super().observe('grandTotalAmount isAvailable isButtonRendered');
            this.grandTotalAmount(quote.totals()['base_grand_total']);

            return this;
        },

        /**
         * Create instance of smart buttons.
         */
        initApplePayButton: function () {
            this.applePayButton = new ApplePayButton({
                scriptParams: window.checkoutConfig.payment[this.getCode()].sdkParams,
                createOrderUrl: window.checkoutConfig.payment[this.getCode()].createOrderUrl,
                estimateShippingMethodsWhenLoggedInUrl: window.checkoutConfig.payment[this.getCode()].estimateShippingMethodsWhenLoggedInUrl,
                estimateShippingMethodsWhenGuestUrl: window.checkoutConfig.payment[this.getCode()].estimateShippingMethodsWhenGuestUrl,
                shippingInformationWhenLoggedInUrl: window.checkoutConfig.payment[this.getCode()].shippingInformationWhenLoggedInUrl,
                shippingInformationWhenGuestUrl: window.checkoutConfig.payment[this.getCode()].shippingInformationWhenGuestUrl,
                updatePayPalOrderUrl: window.checkoutConfig.payment[this.getCode()].updatePayPalOrderUrl,
                countriesUrl: window.checkoutConfig.payment[this.getCode()].countriesUrl,
                onClick: this.onClick,
                beforeCreateOrder: this.beforeCreateOrder,
                afterCreateOrder: this.afterCreateOrder,
                catchCreateOrder: this.catchError,
                onError: this.catchError,
                buttonContainerId: this.buttonContainerId,
                onApprove: this.placeOrder,
                styles: window.checkoutConfig.payment[this.getCode()].buttonStyles
            });
        },

        /**
         * Get method code
         *
         * @return {String}
         */
        getCode: function () {
            return 'payment_services_paypal_apple_pay';
        },

        /**
         * Get method data
         *
         * @return {Object}
         */
        getData: function () {
            return {
                'method': this.item.method,
                'additional_data': {
                    'payments_order_id': this.paymentsOrderId,
                    'paypal_order_id': this.paypalOrderId,
                    'payment_source': this.fundingSource
                }
            };
        },

        /**
         * Render buttons
         */
        afterRender: function () {
            this.applePayButton.sdkLoaded
                .then(this.applePayButton.initAppleSDK)
                .then(function () {
                        this.isAvailable(true);
                        this.isButtonRendered(true);
                    }.bind(this)
                ).catch(function () {
                this.isAvailable(false);
            }.bind(this)).finally(function () {
                this.isButtonRendered(true);
            }.bind(this));
        },

        /**
         * Enable/disable buttons.
         *
         * @param {Object} data
         * @param {Object} actions
         */
        onInit: function (data, actions) {
            if (!this.isPlaceOrderActionAllowed()) {
                actions.disable();
            }

            this.isPlaceOrderActionAllowed.subscribe(function (isAllowed) {
                if (isAllowed) {
                    actions.enable();
                } else {
                    actions.disable();
                }
            });
        },

        /**
         * Validate form onClick
         *
         * @param {Object} data
         * @param {Object} actions
         * @return {*}
         */
        onClick: function (data, actions) {
            this.applePayButton.showLoaderAsync(true)
                .then(() => {
                    this.applePayButton.createOrder();
                })
                .then(() => {
                    refreshCustomerData(window.checkoutConfig.payment[this.getCode()].createOrderUrl);
                })
                .catch(error => {
                    this.catchError(error);
                });
        },

        /**
         * Before order created.
         *
         * @return {Promise}
         */
        beforeCreateOrder: function () {
            if (this.validate() && this.isPlaceOrderActionAllowed() && additionalValidators.validate()) {
                return new Promise(function (resolve, reject) {
                    setBillingAddressAction(globalMessageList).done(resolve.bind(null, null)).fail(reject);
                });
            } else {
                throw {message: 'before create order validation failed', hidden: true};
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

                this.applePayButton.showPopup(data, quote);

                return this.paypalOrderId;
            }

            throw new Error();
        },

        /**
         * Catch error.
         *
         * @param {Error} error
         */
        catchError: function (error) {
            this.messageContainer.addErrorMessage({
                message: this.requestProcessingError
            });
            console.log('Error: ', error.message);
        }
    });
});
