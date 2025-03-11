/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'underscore',
    'mage/translate',
    'uiComponent',
    'Magento_Ui/js/lib/view/utils/dom-observer',
    'Magento_PaymentServicesPaypal/js/view/payment/methods/hosted-fields',
    'Magento_PaymentServicesPaypal/js/view/errors/response-error',
    'Magento_Ui/js/modal/alert',
    'domReady!'
], function ($, _, $t, Component, domObserver, HostedFields, ResponseError, alert) {
    'use strict';

    return Component.extend({
        defaults: {
            orderFormSelector: '#edit_form',
            messageSelector: '.message',
            cardContainerSelector: '.card-container',
            billingAddressSelectorPrefix: '#order-billing_address_',
            mpOrderIdFieldSelector: '.payment-services-hosted-fields-form #mp-order-id',
            paypalOrderIdSelector: '.payment-services-hosted-fields-form #paypal-order-id',
            styles: {
                '.valid': {
                    'color': 'green'
                },
                '.invalid': {
                    'color': 'red'
                }
            },
            fields: {
                number: {
                    selector: '#card-number',
                    placeholder: '4111 1111 1111 1111'
                },
                cvv: {
                    selector: '#cvv',
                    placeholder: '123'
                },
                expirationDate: {
                    selector: '#expiration-date',
                    label: $t('Expiration Date'),
                    placeholder: 'MM/YY'
                }

            },
            hostedFields: null,
            generalErrorMessage: $t('An error occurred. Refresh the page and try again.'),
            paymentMethodValidationError: $t('Your payment was not successful. Try again.'),
            notEligibleErrorMessage: $t('This payment option is currently unavailable.'),
            shouldCardBeVaulted: false,
            paymentSource: '',
            areHostedFieldsInitialized: false
        },

        /** @inheritdoc */
        initialize: function (config, element) {
            this.element = element;
            _.bindAll(this, 'getPaymentData', 'onOrderSuccess', 'onSuccess', 'onError', 'submitForm',
                'onChangePaymentMethod');
            this._super();
            this.initFormListeners();
            // eslint-disable-next-line no-undef
            if (this.code === order.paymentMethod) {
                this.orderForm.trigger('changePaymentMethod.' + this.code, this.code);
            }

            return this;
        },

        /**
         * Initialize form submit listeners.
         */
        initFormListeners: function () {
            this.orderForm = $(this.orderFormSelector);
            this.orderForm.off('changePaymentMethod.' + this.code)
                .on('changePaymentMethod.' + this.code, this.onChangePaymentMethod);
        },

        /**
         * Reinitialize submitOrder event.
         *
         * @param {Object} event
         * @param {String} method
         */
        onChangePaymentMethod: function (event, method) {
            this.orderForm.off('beforeSubmitOrder.' + this.code);
            if (method === this.code) {
                !this.areHostedFieldsInitialized && this.initHostedFields();
                this.orderForm.on('beforeSubmitOrder.' + this.code, this.submitForm);
            }
        },

        /**
         * Initialize Hosted Fields.
         */
        initHostedFields: function () {
            $('body').trigger('processStart');
            this.hostedFields = new HostedFields({
                fields: this.fields,
                scriptParams: this.scriptParams,
                onOrderSuccess: this.onOrderSuccess,
                createOrderUrl: this.createOrderUrl,
                shouldCardBeVaulted: this.shouldCardBeVaulted,
                paymentSource: this.paymentSource
            });
            this.render();
        },

        /**
         * Render the Hosted Fields and set event listeners
         */
        render: function () {
            this.hostedFields.sdkLoaded.then(function () {
                if (this.hostedFields.isEligible()) {
                    this.hostedFields.render()
                        .then(function (hostedFields) {
                            this.showFields(true);
                            this.afterHostedFieldsRender(hostedFields);
                            this.areHostedFieldsInitialized = true;
                            $('body').trigger('processStop');
                        }.bind(this));
                } else {
                    throw new Error('Hosted fields is not available');
                }
            }.bind(this)).catch(function () {
                this.showFields(false);
                this.displayEligibilityMessage(true);
                $('body').trigger('processStop');
            }.bind(this));
        },

        /**
         * Display eligibility message.
         *
         * @param {Boolean} show
         */
        displayEligibilityMessage: function (show) {
            var element = $(this.element).find(this.messageSelector);

            element.html(this.notEligibleErrorMessage);
            show ? element.show() : element.hide();
        },

        /**
         * Show/hide fields.
         *
         * @param {Boolean} show
         */
        showFields: function (show) {
            var element = $(this.element).find(this.cardContainerSelector);

            show ? element.show() : element.hide();
        },

        /**
         * Bind events after hostedFields rendered.
         *
         * @param {Object} hostedFields
         */
        afterHostedFieldsRender: function (hostedFields) {
            hostedFields.on('inputSubmitRequest', function () {
                this.orderForm.trigger('submitOrder');
            }.bind(this));
        },

        /**
         * Form submit handler
         *
         * @param {Object} e
         */
        submitForm: function (e) {
            if (this.orderForm.valid()) {
                this.hostedFields.instance.submit(this.getPaymentData())
                    .then(this.onSuccess)
                    .catch(this.onError);
            } else {
                $('body').trigger('processStop');
            }
            e.stopImmediatePropagation();

            return false;
        },

        /**
         * Get address field value.
         *
         * @param {String} selector
         * @return {*|String|jQuery}
         */
        getAddressValue: function (selector) {
            return $(this.billingAddressSelectorPrefix + selector).val();
        },

        /**
         * Get payment related data.
         *
         * @return {Object}
         */
        getPaymentData: function () {
            return {
                cardholderName: this.getAddressValue('firstname') + ' ' +
                    this.getAddressValue('lastname'),
                billingAddress: {
                    streetAddress: this.getAddressValue('street0'),
                    extendedAddress: this.getAddressValue('street1'),
                    region: $(this.billingAddressSelectorPrefix + 'region_id option:selected').text(),
                    locality: this.getAddressValue('city'),
                    postalCode: this.getAddressValue('postcode'),
                    countryCodeAlpha2: this.getAddressValue('country_id')
                }
            };
        },

        /**
         * Success callback for transaction.
         */
        onSuccess: function () {
            this.orderForm.trigger('realOrder');
        },

        /**
         * Log error message.
         *
         * @param {Object} error
         */
        onError: function (error) {
            var message = this.generalErrorMessage;

            if (error instanceof ResponseError) {
                message = error.message;
                this.reRender();
            } else if (error['debug_id']) {
                message = this.paymentMethodValidationError;
            }
            $('body').trigger('processStop');
            alert({
                content: message
            });
            console.log(error['debug_id'] ? 'Error' + JSON.stringify(error) : error.toString());
        },

        /**
         * Re-render hosted fields in case of order creation error.
         */
        reRender: function () {
            this.hostedFields.instance.teardown().then(function () {
                this.hostedFields.destroy();
                this.initHostedFields();
            }.bind(this));
        },

        /**
         * Set the payment services order ID and PayPal order ID.
         *
         * @param {Object} order
         */
        onOrderSuccess: function (order) {
            $(this.mpOrderIdFieldSelector).val(order['mp_order_id']);
            $(this.paypalOrderIdSelector).val(order.id);
        }
    });
});
