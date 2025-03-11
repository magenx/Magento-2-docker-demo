/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* eslint-disable no-undef */
define([
    'underscore',
    'uiComponent',
    'mage/translate',
    'Magento_PaymentServicesPaypal/js/view/errors/response-error',
    'scriptLoader'
], function (_, Class, $t, ResponseError, loadSdkScript) {
    'use strict';

    return Class.extend({
        defaults: {
            sdkNamespace: 'paypal',
            paypal: null,
            formId: 'hosted-fields-form',
            instance: undefined,
            createOrderUrl: null,
            orderCreateErrorMessage: {
                default: $t('Failed to place order. Try again or refresh the page if that does not resolve the issue.'), // eslint-disable-line max-len,
                //TODO: Update messages
                'POSTAL_CODE_REQUIRED': $t('Postal code is required.'),
                'CITY_REQUIRED': $t('City is required.')
            },
            styles: {
                input: {
                    color: '#ccc',
                    'font-family': '"Open Sans","Helvetica Neue",Helvetica,Arial,sans-serif',
                    'font-size': '16px',
                    'font-weight': '400'
                },
                ':focus': {
                    color: '#333'
                },
                '.valid': {
                    color: '#333'
                }
            },
            fields: {
                number: {
                    class: 'number',
                    selector: '#${ $.formId } .${ $.fields.number.class }',
                    placeholder: ''
                },
                expirationDate: {
                    class: 'expiration-date',
                    selector: '#${ $.formId } .${ $.fields.expirationDate.class }',
                    placeholder: 'MM/YY'
                },
                cvv: {
                    class: 'cvv',
                    selector: '#${ $.formId } .${ $.fields.cvv.class }',
                    placeholder: ''
                }
            },
            scriptParams: [],
            sdkLoaded: null,
            shouldCardBeVaulted: false
        },

        /** @inheritdoc */
        initialize: function (config) {
            _.bindAll(this, 'createOrder');

            if (config.fields) {
                this.constructor.defaults.fields = config.fields;
            }
            this._super();
            this.sdkLoaded = loadSdkScript(this.scriptParams, this.sdkNamespace).then(function (sdkScript) {
                this.paypal = sdkScript;
            }.bind(this));
            return this;
        },

        /** @inheritdoc */
        initObservable: function () {
            this._super()
                .observe('shouldCardBeVaulted');

            return this;
        },

        /**
         * Check if eligible
         *
         * @return {Boolean}
         */
        isEligible: function () {
            return typeof this.paypal !== 'undefined' &&
                this.paypal.HostedFields &&
                this.paypal.HostedFields.isEligible();
        },

        /**
         * Render fields.
         *
         * @return {*}
         */
        render: function () {
            return this.paypal.HostedFields.render({
                createOrder: this.createOrder,
                styles: this.styles,
                fields: this.fields
            }).then(function (instance) {
                this.instance = instance;

                return instance;
            }.bind(this));
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
         * Create order in payment service / PayPal
         *
         * @returns {Promise<any>}
         */
        createOrder: function () {
            return this.beforeCreateOrder()
                .then(function () {
                    const shouldCardBeVaulted = this.shouldCardBeVaulted(),
                        orderData = new FormData();

                    orderData.append('payment_source', this.paymentSource);

                    return fetch(`${this.createOrderUrl}?vault=${shouldCardBeVaulted}`, {
                        method: 'POST',
                        headers: {},
                        body: orderData
                    });
                }.bind(this)).then(function (res) {
                    return res.json();
                }).then(function (data) {
                    if (data.response['is_successful']) {
                        this.onOrderSuccess(data.response['paypal-order']);
                    } else {
                        throw new ResponseError(
                            this.orderCreateErrorMessage[data.response.message] || this.orderCreateErrorMessage.default
                        );
                    }

                    return data.response['paypal-order'].id;
                }.bind(this)).catch(function (error) {
                    if (error instanceof ResponseError) {
                        throw error;
                    }
                    throw new ResponseError(this.orderCreateErrorMessage.default);
                }.bind(this));
        },

        /**
         * Customizable handler for order creation.
         */
        onOrderSuccess: function () {}
    });
});
