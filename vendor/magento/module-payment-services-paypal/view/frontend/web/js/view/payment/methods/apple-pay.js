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
    'Magento_Customer/js/customer-data',
    'Magento_Customer/js/model/customer',
    'mage/translate',
], function ($, _, Component, loadSdkScript, scriptLoader, customerData, customer, $t) {
    'use strict';

    const HTTP_STATUS_CREATED = 201;

    const APPLE_PAY_VERSION_NUMBER = 4; // See https://developer.apple.com/documentation/apple_pay_on_the_web/apple_pay_js_api/creating_an_apple_pay_session
    const appleSDKSrc = 'https://applepay.cdn-apple.com/jsapi/v1/apple-pay-sdk.js';

    /**
     * Create order request.
     *
     * @param {String} url
     * @param {Object} payPalOrderData
     * @param {FormData} orderData
     * @return {Object}
     */
    var performCreateOrder = function (url, payPalOrderData, orderData) {

            orderData = orderData || new FormData();
            orderData.append('form_key', $.mage.cookies.get('form_key'));
            orderData.append('payment_source', payPalOrderData['paymentSource']);

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
            paymentSource: 'applepay',
            createOrderUrl: '',
            placeOrderUrl: '',
            updateQuoteUrl: '',
            estimateShippingMethodsWhenLoggedInUrl: '',
            estimateShippingMethodsWhenGuestUrl: '',
            shippingInformationWhenLoggedInUrl: '',
            shippingInformationWhenGuestUrl: '',
            updatePaypalOrderUrl: '',
            setQuoteAsInactiveUrl: '',
            countriesUrl: '',
            instance: null,
            scriptParams: {},
            allowedPaymentMethods: null,
            merchantInfo: null,
            buttonContainerId: null,
            paypalOrderId: null,
            eligible: false,
            applePayInstance: null,
            applePayConfig: null,
            appleSession: null,
            applePayVersionNumber: APPLE_PAY_VERSION_NUMBER,
            countryCode: null,
            regionCode: null,
            regionId: null,
            postalCode: null,
            requestProcessingError: $t('Something went wrong with your request. Please try again later.'),
        },

        /** @inheritdoc */
        initialize: function () {
            _.bindAll(this, 'createOrder', 'onApprove', 'onError', 'initAppleSDK', 'performAuthorization',
                'onClick', 'beforeOnAuthorize', 'afterOnAuthorize', 'onCancel');
            this._super();
            this.sdkLoaded = Promise.all([this.loadPayPalSDK(), this.loadAppleSDK()]);

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

        loadAppleSDK: function () {
            return scriptLoader.loadCustom({url: appleSDKSrc})
                .catch(function (e) {
                    this.onError(e);
                });
        },

        isCustomerLoggedIn: function () {
            return customer.isLoggedIn();
        },

        initAppleSDK: function () {
            if (!window.ApplePaySession) {
                console.error('This device does not support Apple Pay');
                return;
            }

            if (!ApplePaySession.canMakePayments()) {
                console.error('This device is not capable of making Apple Pay payments');
            }

            this.applePayInstance = this.paypal.Applepay();
            return this.applePayInstance.config()
                .then(applePayConfig => {
                    this.applePayConfig = applePayConfig;
                    this.renderApplePayButton();
                })
                .catch(applepayConfigError => {
                    console.error('Error while fetching Apple Pay configuration.');
                });
        },

        onApplePayValidateMerchant: function (applePaySession) {
            applePaySession.onvalidatemerchant = (event) => {
                this.applePayInstance
                    .validateMerchant({
                        validationUrl: event.validationURL,
                    })
                    .then((payload) => {
                        applePaySession.completeMerchantValidation(payload.merchantSession);
                    })
                    .catch((error) => {
                        applePaySession.abort();
                        this.isErrorDisplayed = false;
                        this.catchError(error)
                    });
            };
        },

        onApplePayPaymentAuthorized: function (applePaySession, paypalOrderId = null) {
            applePaySession.onpaymentauthorized = async (event) => {
                try {
                    await this.applePayInstance.confirmOrder({
                        orderId: paypalOrderId !== null ? paypalOrderId : this.paypalOrderId,
                        token: event.payment.token,
                        billingContact: event.payment.billingContact,
                        shippingContact: event.payment.shippingContact
                    });

                    applePaySession.completePayment({
                        status: window.ApplePaySession.STATUS_SUCCESS,
                    });

                    await this.onApprove();
                } catch (error) {
                    applePaySession.completePayment({
                        status: window.ApplePaySession.STATUS_FAILURE,
                    });
                    this.isErrorDisplayed = false;
                    this.catchError(error)
                }
            };
        },

        onApplePayPaymentMethodSelected: function (applePaySession, total) {
            applePaySession.onpaymentmethodselected = (event) => {
                applePaySession.completePaymentMethodSelection({
                    newTotal: total,
                });
            };
        },

        onApplePayShippingContactSelected: function (applePaySession, quoteId, total, isVirtual) {
            applePaySession.onshippingcontactselected = (event) => {

                const shippingMethods = [];

                let estimateShippingMethodURL = (this.isCustomerLoggedIn())
                    ? this.estimateShippingMethodsWhenLoggedInUrl
                    : this.estimateShippingMethodsWhenGuestUrl.replace(':cartId', quoteId);

                if (this.pageType === 'product') {
                    // Product Page: we need to use guest cart quote because it is created outside the checkout process
                    estimateShippingMethodURL = this.estimateShippingMethodsWhenGuestUrl.replace(':cartId', quoteId);
                }

                this.countryCode = event.shippingContact.countryCode;
                this.regionCode = event.shippingContact.administrativeArea;
                this.postalCode = event.shippingContact.postalCode;

                this.getRegionIdByCode(this.regionCode, this.countryCode)
                    .then((regionId) => {
                        this.regionId = regionId;
                    })
                    .catch((error)=>  {
                        // If the Apple region Code doesn't match to the one in Commerce
                        // we set the regionId to null to still apply the taxes of the country
                        // to continue with the checkout
                        console.log(error);
                        this.regionId = null;
                    });

                $.ajax({
                    type: 'POST',
                    url: estimateShippingMethodURL,
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    data: JSON.stringify({
                        address: {
                            country_id: event.shippingContact.countryCode,
                            postcode: event.shippingContact.postalCode,
                            city: event.shippingContact.locality
                        }
                    })
                }).then(estimateShippingMethods => {

                    estimateShippingMethods.forEach(method => {
                        shippingMethods.push({
                            label: method.method_title,
                            detail: method.carrier_title,
                            amount: method.amount.toString(),
                            identifier: method.carrier_code + '_' + method.method_code,
                        });
                    });

                    applePaySession.completeShippingContactSelection({
                        newShippingMethods: shippingMethods,
                        newTotal: total,
                    })

                }, error => {
                    this.isErrorDisplayed = false;
                    applePaySession.abort();
                    this.catchError(error);
                });

                if (isVirtual) {
                    // If Quote is virtual, no need to select shipping
                    // We can already create the order
                    this.createOrder();
                }
            }
        },

        onApplePayShippingMethodSelectedInCartPage: function (applePaySession, quoteId) {
            this.onApplePayShippingMethodSelected(applePaySession, quoteId, null,null);
        },

        onApplePayShippingMethodSelected: function (applePaySession, quoteId, quoteMaskedId, paypalOrderId) {
           applePaySession.onshippingmethodselected = (event) => {

                let shippingInformationURL = (this.isCustomerLoggedIn())
                    ? this.shippingInformationWhenLoggedInUrl
                    : this.shippingInformationWhenGuestUrl.replace(':quoteId', quoteId);

                if (this.pageType === 'product') {
                    // Product Page: we need to use quoteMaskedId as the quote is created outside the checkout process
                    shippingInformationURL = this.shippingInformationWhenGuestUrl.replace(':quoteId', quoteMaskedId);
                }

                $.ajax({
                    type: 'POST',
                    url: shippingInformationURL,
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    data: JSON.stringify({
                        addressInformation: {
                            shipping_address: {"country_id": this.countryCode, "region_id": this.regionId, "postcode": this.postalCode},
                            shipping_method_code: event.shippingMethod.identifier.split('_')[1],
                            shipping_carrier_code: event.shippingMethod.identifier.split('_')[0],
                            extension_attributes: {}
                        }
                    })
                }).then(result => {

                    let items = [];

                    result['totals']['items'].forEach(item => {
                        items.push({
                            label: item.name + ' ( x ' + item.qty + ' )',
                            type: "final",
                            amount: item.row_total
                        });
                    });

                    items.push({
                        label: "Shipping",
                        type: "final",
                        amount: result['totals']['shipping_amount'],
                    });

                    if (result['totals']['tax_amount'] !== 0) {
                        items.push({
                            label: "Tax",
                            type: "final",
                            amount: result['totals']['tax_amount'],
                        });
                    }

                    applePaySession.completeShippingMethodSelection({
                        newLineItems: items,
                        newTotal: {
                            label: "Summary",
                            type: "final",
                            amount: result['totals']['grand_total'],
                        },
                    });

                    if (this.pageType === 'product') {
                        // In the product page, the paypal order has been created on the onClick handler
                        // so we just need to update the amount with the shipping selected
                        this.updatePaypalOrder();
                    } else {
                        // In Cart and Minicart, we need to create the Paypal Order
                        this.createOrder();
                    }

                }).catch(error => {
                    applePaySession.abort();
                    this.isErrorDisplayed = false;
                    this.catchError(error);
                });
            };
        },

        updatePaypalOrder: function() {

            // Update PayPal Order Amount as the shipping method has been selected and the price changed
            // Without the update, the payment is failing as the order amount could be different
            $.ajax({
                type: 'POST',
                url: this.updatePayPalOrderUrl,
            }).catch(error => {
                this.catchError(error);
            });
        },

        onApplePayCancel: function (applePaySession, callback) {
            applePaySession.oncancel = () => {
                if (typeof callback === 'function') {
                    callback();
                }

                this.showLoader(false);
            }
        },

        getApplePaymentRequestLineItems: function (quote){
            let items = [];

            quote.getItems().forEach(item => {
                items.push({
                    label: item.name + ' ( x ' + item.qty + ' )',
                    type: "final",
                    amount: item.price * item.qty,
                });
            });

            items.push({
                label: $t("Shipping"),
                type: "final",
                amount: quote.getTotals()()['shipping_amount'],
            });

            if (quote.getTotals()['tax_amount'] !== 0) {
                items.push({
                    label: $t("Tax"),
                    type: "final",
                    amount: quote.getTotals()()['tax_amount'],
                });
            }

            return items;
        },

        showPopup: function (paymentData, quote) {
            const paymentRequest = {
                countryCode: this.applePayConfig.countryCode,
                merchantCapabilities: this.applePayConfig.merchantCapabilities,
                supportedNetworks: this.applePayConfig.supportedNetworks,
                currencyCode: paymentData.response['paypal-order']['currency_code'],
                lineItems: this.getApplePaymentRequestLineItems(quote),
                requiredBillingContactFields: ["postalAddress"],
                shippingContact: {
                    countryCode: quote.shippingAddress().countryId,
                    postalCode: quote.shippingAddress().postcode,
                    locality: quote.shippingAddress().city,
                    administrativeArea: quote.shippingAddress().regionCode,
                    familyName: quote.shippingAddress().lastname,
                    givenName: quote.shippingAddress().firstname,
                    addressLines: quote.shippingAddress().street,
                },
                total: {
                    label: $t("Summary"),
                    type: "final",
                    amount: Number(paymentData.response['paypal-order']['amount']).toString(),
                }
            };

            // See https://developer.apple.com/documentation/apple_pay_on_the_web/applepaysession
            this.applePaySession = new ApplePaySession(APPLE_PAY_VERSION_NUMBER, paymentRequest);
            this.onApplePayValidateMerchant(this.applePaySession);
            this.onApplePayPaymentAuthorized(this.applePaySession, paymentData.response['paypal-order']['id']);
            this.onApplePayCancel(this.applePaySession);

            this.applePaySession.begin();
        },

        onCancel: function () {
            window.location = data.redirectUrl;
            this.showLoader(false);
        },

        renderApplePayButton: function () {
            if (this.applePayConfig.isEligible) {
                const buttonStyle = this.mapButtonStyle();
                const buttonType = this.mapButtonType();
                const height = this.styles.height > 0
                    ? this.styles.height + "px"
                    : "40px";

                document.getElementById(this.buttonContainerId).innerHTML = `
                <apple-pay-button
                    id="btn-appl"
                    buttonstyle="${buttonStyle}"
                    type="${buttonType}"
                    locale="${window.LOCALE}"
                    style=" --apple-pay-button-width: 100%; --apple-pay-button-height: ${height}"
                >`;
                document.getElementById("btn-appl").addEventListener("click", this.onClick);
            }
        },

        mapButtonStyle: function () {
            return this.styles.color === 'white' ? 'white' : 'black';
        },

        mapButtonType: function () {
            switch (this.styles.label) {
                case 'paypal':
                case 'installment':
                    return 'plain';
                case 'checkout':
                    return 'check-out';
                case 'buynow':
                    return 'buy';
                default:
                    return 'pay';
            }
        },

        enableButton: function () {
            $('#' + this.buttonContainerId).find('button').prop('disabled', false);
        },

        disableButton: function () {
            $('#' + this.buttonContainerId).find('button').prop('disabled', true);
        },

        performAuthorization: function (paymentData) {},

        onClick: function () {},

        /**
         * Calls before create order.
         */
        beforeCreateOrder: function () {},

        /**
         * Create order.
         *
         * @return {String}
         */
        createOrder: function () {
            let data = {'paymentSource': this.paymentSource};

            try {
                this.beforeCreateOrder();
                let orderData = performCreateOrder(this.createOrderUrl, data, null);
                this.paypalOrderId = this.afterCreateOrder(orderData);
                return this.paypalOrderId;
            } catch (error) {
                this.isErrorDisplayed = false;
                this.catchError(error);

                // Propagate the error to be caught in the promise chain
                return Promise.reject(error);
            }
        },

        /**
         * After order created.
         *
         * @param {Object} data
         * @return {String}
         */
        afterCreateOrder: function (data) {},

        /**
         * Catch error on order creation.
         */
        catchCreateOrder: function () {},

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

        /**
         *  Get region ID by region code and country code
         *
         * @param regionCode
         * @param countryCode
         * @returns {null}
         */
        getRegionIdByCode: function (regionCode, countryCode) {
            return new Promise(function (resolve, reject) {
                $.ajax({
                    url: this.countriesUrl.replace(':countryCode', countryCode),
                    method: 'GET',
                    success: function (response) {
                        if (response.available_regions && response.available_regions.length) {
                            var matchedRegion = response.available_regions.find(function (region) {
                                return region.code === regionCode;
                            });

                            if (matchedRegion) {
                                resolve(matchedRegion.id);
                            } else {
                                reject('Region not found');
                            }
                        } else {
                            reject('No regions available for country: ' + countryCode);
                        }
                    },
                    error: function () {
                        reject('Error fetching regions for country: ' + countryCode);
                    }
                });
            }.bind(this));
        },

        /**
         * Catch errors.
         *
         * @param {*} error
         */
        catchError: function (error) {
            this.showLoader(false);

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

            // Need to set a slight delay to avoid refresh from core
            setTimeout(function () {
                customerData.set('messages', {
                    messages: [{
                        text: message,
                        type: type
                    }]
                });
            }, 1000);
        },

    });
});
