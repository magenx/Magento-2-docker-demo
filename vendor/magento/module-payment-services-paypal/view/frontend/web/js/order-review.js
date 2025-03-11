/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'uiComponent',
    'Magento_Customer/js/customer-data'
], function ($, Component, customerData) {
    'use strict';

    return Component.extend({
        defaults: {
            element: null,
            placeOrderButtonSelector: '#review-button',
            shippingMethodFormSelector: '#shipping-method-form',
            shippingMethodInputSelector: '#shipping-method',
            updateContainerSelector: '#details-reload',
            waitLoadingContainer: '#review-please-wait',
            orderFormSelector: '#order-review-form',
            editShoppingCartSelector: '.magento-payments-review-items .edit',
            updateShippingMethodUrl: null,
            placeOrderUrl: null,
            canEditShippingMethod: false,
            isVirtual: false
        },

        /**
         * @inheritdoc
         */
        initialize: function (config, element) {
            this._super();
            this.element = element;

            $(this.placeOrderButtonSelector).on('click', this.submitOrder.bind(this));
            $(this.editShoppingCartSelector).on('click', this.invalidateCustomerData.bind(this));

            if (!this.isVirtual && this.canEditShippingMethod) {
                $(this.shippingMethodInputSelector).on('change', this.selectShippingMethod.bind(this));
                this.setPlaceOrderButtonActive(!!$(this.shippingMethodInputSelector).val());
            }
        },

        invalidateCustomerData: function () {
            customerData.invalidate(['cart']);
        },

        /**
         * Before request start
         */
        beforeRequestStart: function () {
            $(this.waitLoadingContainer).show();
        },

        /**
         * On request complete
         */
        onRequestComplete: function () {
            $(this.waitLoadingContainer).hide();
        },

        /**
         * Submit order
         */
        submitOrder: function () {
            if (this.validateForm()) {
                this.beforeRequestStart();
                $(this.orderFormSelector).trigger('submit');
                this.setPlaceOrderButtonActive(false);
            }
        },

        /**
         * Validate form
         */
        validateForm: function () {
            return $(this.element).validation().valid();
        },

        /**
         * Enable/disable order button
         *
         * @param {Boolean} isActive
         */
        setPlaceOrderButtonActive: function (isActive) {
            $(this.placeOrderButtonSelector).prop('disabled', !isActive).toggleClass('no-checkout', !isActive);
        },

        /**
         * Select shipping method
         */
        selectShippingMethod: function () {
            var shippingMethod,
                formData,
                responseCallback;

            if ($(this.waitLoadingContainer).is(':visible')) {
                return false;
            }
            shippingMethod = $(this.shippingMethodInputSelector).val();
            shippingMethod = shippingMethod.trim();
            this.setPlaceOrderButtonActive(false);

            if (shippingMethod) {
                formData = $(this.shippingMethodFormSelector).serialize() + '&isAjax=true';

                /**
                 * @param {Object} response
                 */
                responseCallback = function (response) {
                    if (typeof response.redirectUrl == 'undefined') {
                        $(this.updateContainerSelector).html(response.html);
                    } else {
                        window.location = response.redirectUrl;
                    }
                    this.setPlaceOrderButtonActive(true);
                    this.onRequestComplete();
                };
                $.ajax({
                    url: this.updateShippingMethodUrl,
                    type: 'post',
                    context: this,
                    beforeSend: this.beforeRequestStart,
                    data: formData,
                    success: responseCallback
                });
            }
        }
    });
});
