/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'underscore'
], function (_) {
    'use strict';

    return function (CheckoutAgreements) {
        return CheckoutAgreements.extend({
            /**
             * Replace checkboxes unique id with Braintree PayPal Vault unique id using the index.
             * First call the original & extend in order to prevent unwanted behaviour.
             *
             * @param {Object} context - the ko context
             * @param {Number} agreementId
             */
            getCheckboxId: function (context, agreementId) {
                let result = this._super(context, agreementId),

                    /* Fetch corresponding payment method from parent context */
                    paymentMethodRenderer = context.$parents[1],
                    paymentMethodName;

                /* We only want to check for Braintree PayPal Vault methods with set properties     */
                if (!paymentMethodRenderer ||
                    _.get(paymentMethodRenderer, ['code'], null) !== 'braintree_paypal_vault' ||
                    !_.has(paymentMethodRenderer, 'index')
                ) {
                    return result;
                }

                paymentMethodName = paymentMethodRenderer.index;

                /* Now check that the relevant index `braintree_paypal_vault_X` has global variables set */
                if (!this.hasVaultInCheckoutConfig(paymentMethodName)) {
                    return result;
                }

                /* If yes, use unique name */
                return 'agreement_' + paymentMethodName + '_' + agreementId;
            },

            /**
             * Check whether the current method renderer index has configuration in the checkout global.
             *
             * @param {String} vaultIndex
             * @return {Boolean}
             */
            hasVaultInCheckoutConfig: function (vaultIndex) {
                return _.has(window.checkoutConfig.payment, ['vault', vaultIndex]);
            }
        });
    };
});
