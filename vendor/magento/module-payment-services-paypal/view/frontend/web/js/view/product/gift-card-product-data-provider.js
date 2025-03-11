/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'underscore',
    'Magento_PaymentServicesPaypal/js/view/product/product-data-provider'
], function ($, _, Component) {
    'use strict';

    return Component.extend({
        defaults: {
            priceBoxContextSelector: '.giftcard-amount',
            priceBoxSelector: '#giftcard-amount, #giftcard-amount-input'
        },

        /** @inheritdoc */
        initialize: function () {
            this._super();
            this.price = $(this.priceBoxSelector, this.priceBoxContextSelector).val();

            return this;
        },

        /**
         * Subscribe for price change.
         */
        priceSubscribe: function () {
            $(this.priceBoxSelector, this.priceBoxContextSelector).on('change', function (event) {
                this.price = event.target.value;
                this.updateAmount(this.getAmount());
            }.bind(this));
        },

        /**
         * Checks if product is grouped type.
         *
         * @return {Boolean}
         */
        isProductGiftCard: function () {
            return !!$(this.constructor.defaults.priceBoxContextSelector).length;
        }
    });
});
