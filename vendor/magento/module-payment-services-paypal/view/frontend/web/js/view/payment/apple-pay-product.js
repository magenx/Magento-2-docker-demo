/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* eslint-disable no-undef */
define([
    'jquery',
    'underscore',
    'mageUtils',
    'Magento_PaymentServicesPaypal/js/view/payment/paypal-abstract',
    'mage/translate',
    'Magento_Customer/js/customer-data',
    'Magento_PaymentServicesPaypal/js/view/errors/response-error',
    'Magento_PaymentServicesPaypal/js/view/payment/methods/apple-pay',
], function ($, _, utils, Component, $t, customerData, ResponseError, ApplePayButton) {
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
            sdkNamespace: 'paypalApplePay',
            scriptParams: {},
            buttonContainerId: 'apple-pay-${ $.uid }',
            template: 'Magento_PaymentServicesPaypal/payment/apple-pay',
            paymentsOrderId: null,
            paypalOrderId: null,
            sdkLoaded: null,
            quoteIdForRest: null,
            quoteId: null,
            sdkParamsKey: 'applepay',
            paymentTypeIconTitle: $t('Pay with Apple Pay'),
            notEligibleErrorMessage: $t('This payment option is currently unavailable.'),
            productFormSelector: '#product_addtocart_form'
        },


        /**
         * @inheritdoc
         */
        initialize: function (config, element) {
            _.bindAll(this, 'initApplePayButton', 'onClick', 'afterUpdateQuote', 'beforeCreateOrder',
                'afterCreateOrder', 'afterOnAuthorize', 'onCancel');
            config.uid = utils.uniqueid();
            this._super();
            this.element = element;
            this.element.id = this.buttonContainerId;
            this.getSdkParams()
                .then(this.initApplePayButton)
                .catch(console.log);

            return this;
        },

        initApplePayButton: function () {
            this.applePayButton = new ApplePayButton({
                scriptParams: this.sdkParams,
                createOrderUrl: this.createOrderUrl,
                updateQuoteUrl: this.authorizeOrderUrl,
                shippingInformationWhenGuestUrl: this.shippingInformationWhenGuestUrl,
                shippingInformationWhenLoggedInUrl: this.shippingInformationWhenLoggedInUrl,
                estimateShippingMethodsWhenGuestUrl: this.estimateShippingMethodsWhenGuestUrl,
                estimateShippingMethodsWhenLoggedInUrl: this.estimateShippingMethodsWhenLoggedInUrl,
                updatePayPalOrderUrl: this.updatePayPalOrderUrl,
                setQuoteAsInactiveUrl: this.setQuoteAsInactiveUrl,
                countriesUrl: this.countriesUrl,
                placeOrderUrl: this.placeOrderUrl,
                onClick: this.onClick,
                beforeCreateOrder: this.beforeCreateOrder,
                afterCreateOrder: this.afterCreateOrder,
                catchCreateOrder: this.catchError,
                onError: this.catchError,
                buttonContainerId: this.buttonContainerId,
                afterUpdateQuote: this.afterUpdateQuote,
                shippingAddressRequired: !this.isVirtual,
                styles: this.styles,
                afterOnAuthorize: this.afterOnAuthorize,
                onCancel: this.onCancel,
                pageType: this.pageType,
            });

            $('#' + this.buttonContainerId).on('click', this.onClick);

            this.applePayButton.sdkLoaded
                .then(this.applePayButton.initAppleSDK);
        },

        afterUpdateQuote: function (data) {
            window.location = data.redirectUrl;
            this.applePayButton.showLoader(false);
        },

        onClick: function () {

            var $form = $(this.productFormSelector);

            if ($form.data('mageValidation')) {
                this.formValid = $form.validation('isValid');
            }

            if (this.formValid) {
                this.applePayButton.showLoaderAsync(true)
                    .then(() => {
                        return this.applePayButton.createOrder();
                    })
                    .then(() => {
                        refreshCustomerData(this.createOrderUrl);
                    })
                    .catch(error => {
                        this.applePayButton.catchError(error);
                    });
            }
        },

        setQuoteInactive: function () {
            // Set Quote as inactive to avoid having multiple active quotes for the customer
            $.ajax({
                type: 'POST',
                url: this.setQuoteAsInactiveUrl,
            });
        },

        showPopup: function (paymentData) {

            const paymentRequest = {
                countryCode: this.applePayButton.applePayConfig.countryCode,
                merchantCapabilities: this.applePayButton.applePayConfig.merchantCapabilities,
                supportedNetworks: this.applePayButton.applePayConfig.supportedNetworks,
                currencyCode: String(paymentData['currencyCode']),
                requiredShippingContactFields: ["name", "phone", "email", "postalAddress"],
                requiredBillingContactFields: ["postalAddress"],
                total: {
                    label: $t("Summary"),
                    type: "final",
                    amount: Number(paymentData['totalPrice']).toString(),
                }
            };

            // See https://developer.apple.com/documentation/apple_pay_on_the_web/applepaysession
            this.applePaySession = new ApplePaySession(this.applePayButton.applePayVersionNumber, paymentRequest);
            this.applePayButton.onApplePayValidateMerchant(this.applePaySession);
            this.applePayButton.onApplePayPaymentMethodSelected(this.applePaySession, paymentRequest.total);
            this.applePayButton.onApplePayCancel(this.applePaySession, this.setQuoteInactive.bind(this));
            this.applePayButton.onApplePayShippingContactSelected(this.applePaySession, this.quoteIdForRest, paymentRequest.total, null);
            this.applePayButton.onApplePayShippingMethodSelected(this.applePaySession, this.quoteId, this.quoteIdForRest, this.paypalOrderId);
            this.applePayButton.onApplePayPaymentAuthorized(this.applePaySession);

            this.applePaySession.begin();
        },

        /**
         * Before create order.
         *
         * @return {String}
         */
        beforeCreateOrder: function () {
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
                        this.quoteIdForRest = result.success.quoteIdMask;
                        this.quoteId = result.success.quoteId;
                        return result.success;
                    }
                } catch (parseError) {
                    throw new Error('Failed to parse response JSON: ' + parseError.message);
                }
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

        afterOnAuthorize: function (data) {

            this.applePayButton.showLoaderAsync(true)
                .then(() => {
                    $.ajax({
                        type: 'POST',
                        url: this.placeOrderUrl,
                    }).then(result => {
                        customerData.invalidate(['cart']);
                        document.open();
                        document.write(result);
                        document.close();
                    });
                })
                .catch(error => {
                    this.catchError(error);
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
