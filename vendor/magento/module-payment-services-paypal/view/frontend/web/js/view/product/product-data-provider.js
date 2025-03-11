/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define(['jquery', 'uiClass', 'priceBox'], function ($, Component) {
    'use strict';

    return Component.extend({
        defaults: {
            qtyFieldSelector: '#qty',
            priceBoxSelector: '.price-box',
            priceBoxContextSelector: '.product-info-main',
            finalPriceSelector: '[data-price-type="finalPrice"]',
            price: 0,
            qty: 0
        },

        /** @inheritdoc */
        initialize: function () {
            this._super();
            this.price = $(this.finalPriceSelector, this.priceBoxContextSelector).attr('data-price-amount');
            this.qty = $(this.qtyFieldSelector).val();
            this.quantitySubscribe();
            this.priceSubscribe();

            return this;
        },

        /**
         * Subscribe for quantity changes.
         */
        quantitySubscribe: function () {
            $(this.qtyFieldSelector).on('change', function () {
                this.qty = $(this.qtyFieldSelector).val();
                this.updateAmount(this.getAmount());
            }.bind(this));
        },

        /**
         * Subscribe for price change.
         */
        priceSubscribe: function () {
            $(this.priceBoxSelector, this.priceBoxContextSelector).on('updatePrice', function (event) {
                var prices = $(event.target).data('magePriceBox').cache.displayPrices;

                this.price = prices.finalPrice.amount;
                this.updateAmount(this.getAmount());
            }.bind(this));
        },

        /**
         * Trigger price update.
         */
        updatePrice: function () {
            $(this.priceBoxSelector).trigger('updatePrice');
        },

        /**
         * Get product amount.
         *
         * @return {Number}
         */
        getAmount: function () {
            return this.qty * this.price;
        }
    });
});
