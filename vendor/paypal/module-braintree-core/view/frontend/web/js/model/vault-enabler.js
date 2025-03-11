define([
    'Magento_Vault/js/view/payment/vault-enabler'
], function (VaultEnabler) {
    'use strict';

    return {
        // Initialise a single VaultEnabler instance.
        vaultEnabler: new VaultEnabler(),

        /**
         * @returns {VaultEnabler}
         */
        getVaultEnabler() {
            return this.vaultEnabler;
        },

        /**
         * Set the payment code against the vault enabler.
         *
         * @param {string} code
         */
        setPaymentCode(code) {
            this.vaultEnabler.setPaymentCode(code);
        },

        /**
         * Returns the vault enabled state.
         *
         * @returns {boolean}
         */
        isVaultEnabled() {
            return this.vaultEnabler.isVaultEnabled();
        },

        /**
         * Returns the active payment token enabler state.
         *
         * @returns {boolean}
         */
        isActivePaymentTokenEnabler() {
            return this.vaultEnabler.isActivePaymentTokenEnabler();
        },

        /**
         * @param {Object} data
         */
        visitAdditionalData(data) {
            this.vaultEnabler.visitAdditionalData(data);
        }
    };
});
