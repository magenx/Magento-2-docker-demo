/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_PaymentServicesPaypal/js/view/payment/paypal-abstract',
    'Magento_PaymentServicesPaypal/js/view/payment/message',
    'Magento_PaymentServicesPaypal/js/view/product/product-data-provider',
    'Magento_PaymentServicesPaypal/js/view/product/grouped-product-data-provider',
    'Magento_PaymentServicesPaypal/js/view/product/bundle-product-data-provider',
    'Magento_PaymentServicesPaypal/js/view/product/gift-card-product-data-provider'
], function (
    Component,
    Message,
    ProductDataProvider,
    GroupedProductDataProvider,
    BundledProductDataProvider,
    GiftCardProductDataProvider
) {
    'use strict';

    return Component.extend({
        defaults: {
            sdkNamespace: 'paypalProduct',
            element: null,
            message: null
        },

        /**
         * @inheritdoc
         */
        initialize: function (config, element) {
            var providerOptions = {
                updateAmount: this.updateAmount.bind(this)
            };

            this.element = element;
            this._super();

            if (GroupedProductDataProvider.prototype.isProductGrouped()) {
                this.provider = new GroupedProductDataProvider(providerOptions);
            } else if (BundledProductDataProvider.prototype.isBundleProduct()) {
                this.provider = new BundledProductDataProvider(providerOptions);
            }  else if (GiftCardProductDataProvider.prototype.isProductGiftCard()) {
                this.provider = new GiftCardProductDataProvider(providerOptions);
            } else {
                this.provider = new ProductDataProvider(providerOptions);
            }
            this.getSdkParams()
                .then(this.initMessage.bind(this))
                .then(this.render.bind(this));
        },

        /**
         * Create instance of messages.
         */
        initMessage: function () {
            this.message = new Message({
                sdkNamespace: this.sdkNamespace,
                scriptParams: this.sdkParams,
                element: this.element,
                renderContainer: this.renderContainer,
                styles: this.styles,
                placement: this.placement,
                amount: this.provider.getAmount()
            });
        },

        /**
         * Update message amount.
         */
        updateAmount: function () {
            this.message && this.message.updateAmount(this.provider.getAmount());
        },

        /**
         * Render the message
         */
        render: function () {
            this.message.render();
            this.provider.updatePrice();
        }
    });
});
