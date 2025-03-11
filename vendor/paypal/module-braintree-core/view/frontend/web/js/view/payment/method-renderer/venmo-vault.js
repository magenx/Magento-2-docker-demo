/*browser:true*/

define([
    'ko',
    'jquery',
    'underscore',
    'Magento_Vault/js/view/payment/method-renderer/vault',
    'Magento_Ui/js/model/messageList',
    'PayPal_Braintree/js/view/payment/validator-handler',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Checkout/js/model/full-screen-loader'
], function (
    ko,
    $,
    _,
    VaultComponent,
    globalMessageList,
    validatorManager,
    additionalValidators,
    fullScreenLoader
) {
    'use strict';

    return VaultComponent.extend({
        defaults: {
            active: false,
            imports: {
                onActiveChange: 'active'
            },
            template: 'PayPal_Braintree/payment/venmo/vault',
            validatorManager: validatorManager,
            additionalData: {}
        },

        /**
         * @returns {exports}
         */
        initObservable: function () {
            this._super().observe(['active']);
            this.validatorManager.initialize();
            return this;
        },

        /**
         * Is payment option active?
         *
         * @returns {boolean}
         */
        isActive: function () {
            let active = this.getId() === this.isChecked();

            this.active(active);
            return active;
        },

        /**
         * Return the payment method code.
         *
         * @returns {string}
         */
        getCode: function () {
            return 'braintree_venmo_vault';
        },

        /**
         * Get venmo username
         *
         * @returns {String}
         */
        getVenmoUsername: function () {
            return this.details.username;
        },

        /**
         * Get the Venmo icon
         *
         * @param type
         * @return {*|boolean}
         */
        getVenmoIcon() {
            return window.checkoutConfig.payment.braintree_venmo.paymentMarkSrc;
        },

        /**
         * trigger Place order action.
         *
         * Set payment method nonce & place order.
         */
        triggerPlaceOrder: function () {
            this.getPaymentMethodNonce();
        },

        /**
         * Send request to get payment method nonce & places order.
         */
        getPaymentMethodNonce: function () {
            let self = this;

            fullScreenLoader.startLoader();
            $.getJSON(self.nonceUrl, {
                'public_hash': self.publicHash
            }).done(function (response) {
                fullScreenLoader.stopLoader();
                self.additionalData['payment_method_nonce'] = response.paymentMethodNonce;
                self.placeOrder();
            }).fail(function (response) {
                let error = JSON.parse(response.responseText);

                fullScreenLoader.stopLoader();
                globalMessageList.addErrorMessage({
                    message: error.message
                });
            });
        },

        /**
         * Get payment method data.
         *
         * @returns {Object}
         */
        getData: function () {
            let data = {
                'method': this.code,
                'additional_data': {
                    'public_hash': this.publicHash
                }
            };

            data['additional_data'] = _.extend(data['additional_data'], this.additionalData);

            return data;
        }
    });
});
