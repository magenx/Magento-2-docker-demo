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
            priceBoxContextSelector: '#bundleSummary',
            slideSelector: '#bundle-slide'
        },

        /** @inheritdoc */
        initialize: function () {
            this._super();

            // Need to track bundle product slide to trigger amount update to make message visible.
            $(this.slideSelector).on('click', function () {
                setTimeout(function () {
                    this.updateAmount(this.getAmount());
                }.bind(this), 300);
            }.bind(this));
            this.updateAmount(this.getAmount());

            return this;
        },

        /**
         * Checks if product is grouped type.
         *
         * @return {Boolean}
         */
        isBundleProduct: function () {
            return !!$(this.constructor.defaults.priceBoxContextSelector).length;
        }
    });
});
