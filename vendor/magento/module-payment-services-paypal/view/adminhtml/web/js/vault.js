/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'mage/translate',
    'uiComponent',
    'Magento_PaymentServicesPaypal/js/view/errors/response-error',
    'Magento_Ui/js/modal/alert'
], function ($, $t, Class, ResponseError, alert) {
    'use strict';

    return Class.extend({
        defaults: {
            $orderForm: null,
            orderForm: 'edit_form',
            $container: null,
            paypalOrderIdSelector: '[name="payment[paypal_order_id]"]',
            mpOrderIdFieldSelector: '[name="payment[payments_order_id]"]',
            generalErrorMessage: $t('An error occurred. Refresh the page and try again.'),
            paymentMethodValidationError: $t('Your payment was not successful. Try again.')
        },

        /**
         * Set list of observable attributes
         * @returns {exports.initObservable}
         */
        initObservable: function () {
            this.$orderForm = $('#' + this.orderForm);
            this._super();
            this.initEventHandlers();
            return this;
        },

        /**
         * Get vault payment method code
         *
         * @returns {*}
         */
        getCode: function () {
            return this.code;
        },

        /**
         * Listen to vault token changes
         */
        initEventHandlers: function () {
            // eslint-disable-next-line no-undef
            if (this.code === order.paymentMethod) {
                this.selectPaymentMethod();
            }
            $('#' + this.container).find('[name="payment[token_switcher]"]')
                .on('click', this.selectPaymentMethod.bind(this));
            this.initChangePaymentMethodHandler();
        },

        /**
         * Initialize the "changePaymentMethod" event handler.
         */
        initChangePaymentMethodHandler: function () {
            // NOTE: There already exists a handler for "changePaymentMethod.${code}" in the "Magento_Vault" core
            //   ... module. Add the "payment_services" sub-namespace not to override it.
            var eventName = 'changePaymentMethod.' + this.code + '.payment_services'
            this.$orderForm.off(eventName).on(eventName, this.onChangePaymentMethod.bind(this));
        },

        /**
         * Reset the 'beforeSubmitOrder' event listener when the user switches to another payment method.
         *
         * @param {Object} event
         * @param {String} method
         */
        onChangePaymentMethod: function (event, method) {
            if (method !== this.code) {
                this.disableEventListeners();
            }
        },

        /**
         * Select current payment token
         */
        selectPaymentMethod: function () {
            this.disableEventListeners();
            this.enableEventListeners();
        },

        /**
         * Enable form event listeners
         */
        enableEventListeners: function () {
            this.$orderForm.on('beforeSubmitOrder.' + this.getCode(), this.submitOrder.bind(this));
        },

        /**
         * Disable form event listeners
         */
        disableEventListeners: function () {
            this.$orderForm.off('beforeSubmitOrder.' + this.getCode());
        },

        /**
         * Trigger the order placement process
         *
         * @param e
         * @returns {boolean}
         */
        submitOrder: function (e) {
          this.createOrder()
              .then(function (order) {
                  this.onOrderSuccess(order);
              }.bind(this))
              .then(this.setPaymentDetails.bind(this))
              .then(this.placeOrder.bind(this))
              .catch(this.onError.bind(this));
          e.stopImmediatePropagation();
          return false;
        },

        /**
         * Create PayPal order
         *
         * @returns {Promise<any>}
         */
        createOrder: function () {
            $('body').trigger('processStart');

            var orderData = new FormData();
            orderData.append('payment_source', 'vault');

            return fetch(this.createOrderUrl, {
                method: 'POST',
                headers: {},
                credentials: 'same-origin',
                body: orderData
            }).then(function (res) {
                return res.json();
            }).then(function (data) {
                if (data.response['is_successful']) {
                    return data.response['paypal-order'];
                }
            });
        },

        /**
         * Set public hash on payment data
         */
        setPaymentDetails: function () {
            this.$orderForm.find('[name="payment[public_hash]"]').val(this.publicHash);
        },

        /**
         * Kick off Commerce order flow
         */
        placeOrder: function () {
          this.$orderForm.trigger('realOrder');
        },

        /**
         * Populate order info on template upon order creation success
         *
         * @param order
         */
        onOrderSuccess: function (order) {
            this.containerEl = $('#' + this.container);
            $(this.paypalOrderIdSelector).prop('disabled', true);
            $(this.mpOrderIdFieldSelector).prop('disabled', true);
            this.containerEl.find(this.paypalOrderIdSelector).val(order.id);
            this.containerEl.find(this.paypalOrderIdSelector).prop('disabled', false);
            this.containerEl.find(this.mpOrderIdFieldSelector).val(order['mp_order_id']);
            this.containerEl.find(this.mpOrderIdFieldSelector).prop('disabled', false);
        },

        /**
         * Log error message
         * @param error
         */
        onError: function (error) {
            let message = this.generalErrorMessage;

            if (error instanceof ResponseError) {
                message = error.message;
            } else if (error['debug_id']) {
                message = this.paymentMethodValidationError;
            }
            $('body').trigger('processStop');
            alert({
                content: message
            });
            console.log(error['debug_id'] ? 'Error' + JSON.stringify(error) : error.toString());
        }
    });
});
