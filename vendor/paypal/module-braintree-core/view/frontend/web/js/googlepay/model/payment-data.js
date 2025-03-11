define([
    'underscore',
    'ko'
], function (_, ko) {
    'use strict';

    let paymentMethodData = ko.observable(null),
        email = ko.observable(null),
        shippingAddress = ko.observable(null);

    return {
        paymentMethodData: paymentMethodData,
        email: email,
        shippingAddress: shippingAddress,

        /**
         * Get Google Pay payment method data details.
         *
         * @return {?Object}
         */
        getPaymentMethodData: function () {
            return this.paymentMethodData();
        },

        /**
         * Set Google Pay payment method data details.
         *
         * @param {?Object} value
         * @return {void}
         */
        setPaymentMethodData: function (value) {
            this.paymentMethodData(_.isObject(value) ? value : null);
        },

        /**
         * Get Google Pay email.
         *
         * @return {?string}
         */
        getEmail: function () {
            return this.email();
        },

        /**
         * Set Google Pay email.
         *
         * @param {?string} value
         * @return {void}
         */
        setEmail: function (value) {
            this.email(_.isString(value) ? value : null);
        },

        /**
         * Get Google Pay shipping address.
         *
         * @return {?Object}
         */
        getShippingAddress: function () {
            return shippingAddress();
        },

        /**
         * Set Google Pay shipping address.
         *
         * @param {?Object} value
         * @return {void}
         */
        setShippingAddress: function (value) {
            this.shippingAddress(_.isObject(value) ? value : null);
        },

        /**
         * Reset data to default.
         */
        resetDefaultData: function () {
            this.setPaymentMethodData(null);
            this.setEmail(null);
            this.setShippingAddress(null);
        }
    };
});
