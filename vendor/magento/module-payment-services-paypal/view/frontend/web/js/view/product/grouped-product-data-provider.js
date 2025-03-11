/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define(['jquery', 'underscore', 'uiClass'], function ($, _, Component) {
    'use strict';

    return Component.extend({
        defaults: {
            tableWrapperSelector: '.table-wrapper.grouped',
            qtyFieldSelector: '.input-text.qty',
            priceBoxSelector: '[data-role="priceBox"]',
            priceInfo: {}
        },

        /**
         * @inheritdoc
         */
        initialize: function () {
            this._super();
            $('tbody tr', this.tableWrapperSelector).each(function (index, element) {
                var priceBox = $(this.priceBoxSelector, element),
                    qtyElement = $(this.qtyFieldSelector, element),
                    productId = priceBox.data('productId'),
                    priceElement = $('#product-price-' + productId);

                this.priceInfo[productId] = {
                    qty: this.getQuantity(qtyElement),
                    price: priceElement.data('priceAmount')
                };
            }.bind(this));
            this.quantitySubscribe();

            return this;
        },

        /**
         * Subscribe for quantity changes.
         */
        quantitySubscribe: function () {
            $(this.qtyFieldSelector).on('change', function (event) {
                var qtyElement = $(event.target),
                    parent = qtyElement.parents('tr'),
                    priceBox = $(this.priceBoxSelector, parent),
                    productId = priceBox.data('productId');

                if (this.priceInfo[productId]) {
                    this.priceInfo[productId].qty = this.getQuantity(qtyElement);
                }

                this.updateAmount(this.getAmount());
            }.bind(this));
        },

        /**
         * Get product amount.
         *
         * @return {Number}
         */
        getAmount: function () {
            var amount = 0;

            _.each(this.priceInfo, function (info) {
                amount += info.price * info.qty;
            });

            return amount;
        },

        /**
         * Get product quantity.
         *
         * @param {HTMLElement} element
         * @return {Number}
         */
        getQuantity: function (element) {
            var qty = parseFloat(element.val());

            return !isNaN(qty) && qty ? qty : 0;
        },

        /**
         * Checks if product is grouped type.
         *
         * @return {Boolean}
         */
        isProductGrouped: function () {
            return !!$(this.constructor.defaults.tableWrapperSelector).length;
        },

        /**
         * Subscribe for price change.
         */
        priceSubscribe: function () {
        },

        /**
         * Trigger price update.
         */
        updatePrice: function () {
        }
    });
});
