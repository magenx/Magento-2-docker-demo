/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_PaymentServicesPaypal/js/view/payment/paypal-abstract',
    'Magento_PaymentServicesPaypal/js/view/payment/message',
    'Magento_Customer/js/customer-data'
], function (Component, Message, customerData) {
    'use strict';

    return Component.extend({
        defaults: {
            sdkNamespace: 'paypalCart',
            element: null,
            message: null
        },

        /**
         * @inheritdoc
         */
        initialize: function (config, element) {
            var cartData = customerData.get('cart');

            this.element = element;
            this._super();
            this.getSdkParams()
                .then(this.initMessage.bind(this))
                .then(function () {
                    cartData.subscribe(function (updatedCart) {
                        this.message.updateAmount(updatedCart.subtotalAmount);
                    }, this);
                }.bind(this))
                .then(this.render.bind(this));

        },

        /**
         * Create instance of messages.
         */
        initMessage: function () {
            var cartData = customerData.get('cart');

            this.message = new Message({
                sdkNamespace: this.sdkNamespace,
                scriptParams: this.sdkParams,
                element: this.element,
                renderContainer: this.renderContainer,
                styles: this.styles,
                placement: this.placement,
                amount: cartData().subtotalAmount
            });
        },

        /**
         * Render message
         */
        render: function () {
            this.message.render();
        }
    });
});
