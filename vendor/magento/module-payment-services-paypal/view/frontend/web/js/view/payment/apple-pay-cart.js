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
    'Magento_PaymentServicesPaypal/js/view/payment/methods/apple-pay',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/cart/totals-processor/default',
], function (_, $, utils, Component, $t, customerData, ResponseError, ApplePayButton, quote, totalsProcessor) {
    'use strict';

    return Component.extend({
        defaults: {
            sdkNamespace: 'paypalApplePay',
            sdkParamsKey: 'applepay',
            buttonContainerId: 'apple-pay-${ $.uid }',
            paymentActionError: $t('Something went wrong with your request. Please try again later.'),
            isErrorDisplayed: false
        },

        /**
         * @inheritdoc
         */
        initialize: function (config, element) {
            _.bindAll(this, 'initApplePayButton', 'onClick', 'afterOnAuthorize',  'afterCreateOrder', 'showPopup', 'cancelApplePay');
            config.uid = utils.uniqueid();
            this._super();
            this.element = element;
            this.element.id = this.buttonContainerId;

            this.getSdkParams()
                .then(this.initApplePayButton)
                .catch(console.log);

            // Reload quote totals in minicart to have the correct grand_total for the Apple Popup
            if (this.pageType === 'minicart') {
                totalsProcessor.estimateTotals().done(function (result) {
                    quote.setTotals(result);
                });
            }
            return this;
        },

        initApplePayButton: function () {
            this.applePayButton = new ApplePayButton({
                scriptParams: this.sdkParams,
                createOrderUrl: this.createOrderUrl,
                estimateShippingMethodsWhenLoggedInUrl: this.estimateShippingMethodsWhenLoggedInUrl,
                estimateShippingMethodsWhenGuestUrl: this.estimateShippingMethodsWhenGuestUrl,
                shippingInformationWhenLoggedInUrl: this.shippingInformationWhenLoggedInUrl,
                shippingInformationWhenGuestUrl: this.shippingInformationWhenGuestUrl,
                updatePaypalOrderUrl: this.updatePaypalOrderUrl,
                countriesUrl: this.countriesUrl,
                placeOrderUrl: this.placeOrderUrl,
                showPopup: this.showPopup,
                updateQuoteUrl: this.authorizeOrderUrl,
                onClick: this.onClick,
                afterCreateOrder: this.afterCreateOrder,
                catchCreateOrder: this.catchError,
                onError: this.catchError,
                buttonContainerId: this.buttonContainerId,
                afterOnAuthorize: this.afterOnAuthorize,
                shippingAddressRequired: !this.isVirtual,
                styles: this.styles,
                pageType: this.pageType,
            });

            $('#' + this.buttonContainerId).on('click', this.onClick);

            this.applePayButton.sdkLoaded
                .then(this.applePayButton.initAppleSDK);
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

        onClick: function () {
            this.isErrorDisplayed = false;

            this.applePayButton.showLoaderAsync(true).then(() => {
                const data = {
                    response: {
                        'paypal-order': {
                            currency_code: String(quote.totals().quote_currency_code),
                            amount: Number(quote.totals().grand_total).toString(),
                        }
                    }
                }
                this.applePayButton.showPopup(data);
            })
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
                return this.paypalOrderId;
            }

            throw new Error();
        },

        cancelApplePay: function (){
            customerData.invalidate(['cart']);
            window.location.reload();
        },

        showPopup: function (paymentData) {
            const paymentRequest = {
                countryCode: this.applePayButton.applePayConfig.countryCode,
                merchantCapabilities: this.applePayButton.applePayConfig.merchantCapabilities,
                supportedNetworks: this.applePayButton.applePayConfig.supportedNetworks,
                currencyCode: paymentData.response['paypal-order']['currency_code'],
                requiredShippingContactFields: ["name", "phone", "email", "postalAddress"],
                requiredBillingContactFields: ["postalAddress"],
                total: {
                    label: $t("Summary"),
                    type: "final",
                    amount: Number(paymentData.response['paypal-order']['amount']).toString(),
                }
            };

            // See https://developer.apple.com/documentation/apple_pay_on_the_web/applepaysession
            this.applePaySession = new ApplePaySession(this.applePayButton.applePayVersionNumber, paymentRequest);

            this.applePayButton.onApplePayValidateMerchant(this.applePaySession);
            this.applePayButton.onApplePayPaymentMethodSelected(this.applePaySession, paymentRequest.total);
            this.applePayButton.onApplePayCancel(this.applePaySession, this.cancelApplePay);
            this.applePayButton.onApplePayShippingContactSelected(this.applePaySession, quote.getQuoteId() , paymentRequest.total, quote.isVirtual());
            this.applePayButton.onApplePayShippingMethodSelectedInCartPage(this.applePaySession, quote.getQuoteId());
            this.applePayButton.onApplePayPaymentAuthorized(this.applePaySession);

            this.applePaySession.begin();
        },
    });
});
