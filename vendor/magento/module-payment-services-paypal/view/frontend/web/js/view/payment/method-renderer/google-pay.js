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
    'mage/translate',
    'Magento_PaymentServicesPaypal/js/view/payment/methods/google-pay',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Checkout/js/action/set-billing-address',
    'Magento_Ui/js/model/messageList',
    'uiRegistry',
    'Magento_Customer/js/customer-data'
], function (
    Component,
    $,
    _,
    utils,
    quote,
    $t,
    GooglePayButton,
    additionalValidators,
    setBillingAddressAction,
    globalMessageList,
    registry,
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
            buttonContainerId: 'google-pay-${ $.uid }',
            template: 'Magento_PaymentServicesPaypal/payment/google-pay',
            isAvailable: false,
            isButtonRendered: false,
            paymentsOrderId: null,
            paypalOrderId: null,
            paymentTypeIconTitle: $t('Pay with Google Pay'),
            requestProcessingError: $t('Error happened when processing the request. Please try again later.'),
            notEligibleErrorMessage: $t('This payment option is currently unavailable.'),
            paymentTypeIconUrl:  window.checkoutConfig.payment['payment_services_paypal_google_pay'].paymentTypeIconUrl,
            fundingSource: window.checkoutConfig.payment['payment_services_paypal_google_pay'].paymentSource
        },

        /**
         * @inheritdoc
         */
        initialize: function (config) {
            _.bindAll(this, 'catchError', 'beforeCreateOrder', 'afterCreateOrder', 'placeOrder', 'onClick');
            config.uid = utils.uniqueid();
            this._super();
            this.initGooglePayButton();

            return this;
        },

        /**
         * Initialize observables
         *
         * @returns {Component} Chainable.
         */
        initObservable: function () {
            this._super().observe('isAvailable isButtonRendered');

            return this;
        },

        initGooglePayButton: function () {
            this.googlePayButton = new GooglePayButton({
                scriptParams: window.checkoutConfig.payment[this.getCode()].sdkParams,
                createOrderUrl: window.checkoutConfig.payment[this.getCode()].createOrderUrl,
                onClick: this.onClick,
                beforeCreateOrder: this.beforeCreateOrder,
                afterCreateOrder: this.afterCreateOrder,
                catchCreateOrder: this.catchError,
                onError: this.catchError,
                buttonContainerId: this.buttonContainerId,
                onApprove: this.placeOrder,
                styles: window.checkoutConfig.payment[this.getCode()].styles,
                mode: window.checkoutConfig.payment[this.getCode()].mode,
                shippingAddressRequired: false,
                getOrderDetailsUrl: window.checkoutConfig.payment[this.getCode()].getOrderDetailsUrl,
                threeDSMode: window.checkoutConfig.payment[this.getCode()].threeDSMode,
            });

            if (!this.isPlaceOrderActionAllowed()) {
                this.googlePayButton.disableButton();
            }

            this.isPlaceOrderActionAllowed.subscribe(function (isAllowed) {
                if (isAllowed) {
                    this.googlePayButton.enableButton();
                } else {
                    this.googlePayButton.disableButton();
                }
            }.bind(this));
        },

        /**
         * Get method code
         *
         * @return {String}
         */
        getCode: function () {
            return 'payment_services_paypal_google_pay';
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

        onClick: function () {
            this.googlePayButton.showLoaderAsync(true)
                .then(() => {
                    return this.googlePayButton.createOrder();
                })
                .then(() => {
                    refreshCustomerData(window.checkoutConfig.payment[this.getCode()].createOrderUrl);
                })
                .catch(error => {
                    this.catchError(error);
                });
        },

        /**
         * Render buttons
         */
        afterRender: function () {
            this.googlePayButton.sdkLoaded
                .then(function () {
                    this.googlePayButton.initGoogleSDK()
                        .then(function () {
                            this.isAvailable(this.googlePayButton.isEligible());
                        }.bind(this)).catch(function () {
                            this.isAvailable(false);
                        }.bind(this)).finally(function () {
                            this.isButtonRendered(true);
                        }.bind(this)
                    );
                }.bind(this));
        },

        /**
         * Before order created.
         *
         * @return {Promise}
         */
        beforeCreateOrder: function () {
            if (this.validate() && this.isPlaceOrderActionAllowed() && additionalValidators.validate()) {
                setBillingAddressAction(globalMessageList)
                    .fail(function () {
                        throw {message: 'Failed to set billing address'};
                    });
            } else {
                throw {message: 'before create order validation failed', hidden: true};
            }
        },

        /**
         * @inheritdoc
         */
        validate: function () {
            var isShippingValid = true,
                source, shippingAddress;

            if (!this._super()) {
                return false;
            }
            source = registry.get('checkoutProvider');
            shippingAddress = registry.get('index = shippingAddress');

            if (source && shippingAddress) {
                source.set('params.invalid', false);
                if (quote.billingAddress() === null) {
                    this.triggerBillingValidation(source);
                }

                // skip shipping validation if quote is virtual or in-store pickup
                if (!quote.isVirtual() && !quote.shippingMethod()['method_code'] === 'pickup') {
                    isShippingValid = shippingAddress.validateShippingInformation();
                }

                return isShippingValid && !source.get('params.invalid');
            }

            return true;
        },

        /**
         * Trigger billing address validation
         *
         * @param {Object} source
         */
        triggerBillingValidation: function (source) {
            var dataScope = `billingAddress${ window.checkoutConfig.displayBillingOnPaymentMethod ?
                this.getCode() : 'shared'}`;

            source.trigger(`${ dataScope }.data.validate`);

            if (source.get(`${dataScope}.custom_attributes`)) {
                source.trigger(`${dataScope}.custom_attributes.data.validate`);
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

                let displayItems = [],
                    subTotal = this.getTotalsSegment('subtotal'),
                    shippingTotal = this.getTotalsSegment('shipping'),
                    taxTotal = this.getTotalsSegment('tax'),
                    discountTotal = this.getTotalsSegment('discount');

                if (subTotal != null) {
                    displayItems.push(
                        {
                            label: $t('Subtotal'),
                            type: 'SUBTOTAL',
                            price: subTotal.value.toString()
                        }
                    );
                }
                if (taxTotal != null) {
                    displayItems.push(
                        {
                            label: $t('Tax'),
                            type: 'TAX',
                            price: taxTotal.value.toString()
                        }
                    );
                }

                if (shippingTotal != null) {
                    displayItems.push(
                        {
                            label: $t('Shipping'),
                            type: 'LINE_ITEM',
                            price: shippingTotal.value.toString()
                        }
                    );
                }

                if (discountTotal != null) {
                    displayItems.push(
                        {
                            label: $t('Discount'),
                            type: 'LINE_ITEM',
                            price: discountTotal.value.toString()
                        }
                    );
                }

                this.googlePayButton.showPopup({
                    displayItems: displayItems,
                    currencyCode: quote.totals()['base_currency_code'].toString(),
                    totalPriceStatus: 'FINAL',
                    totalPrice: quote.totals()['base_grand_total'].toString(),
                    totalPriceLabel: $t('Total')
                });

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
            if (error.hidden === undefined || !error.hidden) {
                this.messageContainer.addErrorMessage({
                    message: this.requestProcessingError
                });
            }

            this.googlePayButton.showLoader(false);

            console.log('Error: ', error);
        },

        getTotalsSegment: function (code) {
            var segment = null;

            if (!('total_segments' in quote.totals())) {
                return null;
            }

            quote.totals()['total_segments'].forEach(function (s) {
                if (s.code === code) {
                    segment = s;
                }
            });

            return segment;
        }
    });
});
