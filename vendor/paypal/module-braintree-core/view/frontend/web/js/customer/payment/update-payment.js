define([
    'uiComponent',
    'jquery',
    'ko',
    'uiRegistry'
], function (Component, $, ko, registry) {
    'use strict';
    return Component.extend({
        defaults: {
            addNewCardVM: {
                visible: ko.observable(false)
            },
            addNewPayPalVM: {
                visible: ko.observable(false)
            },
            errorModalVM: {
                visible: ko.observable(false),
                message: ko.observable(null),
                header: ko.observable('Error')
            }
        },

        /**
         * Add the repeat order block to the frontend
         */
        initialize: function () {
            this.modifyKnockoutRemovalBehaviour();

            this._super();
        },

        /**
         * By moving the billing address form inside of Knockout it
         * conflicts with the mage.directoryRegionUpdater widget.
         *
         * Knockout will run a cleanData method that removes jQuery events so removes the region updater events
         * so User's will no longer see the correct region fields.
         */
        modifyKnockoutRemovalBehaviour: function () {
            const normalFunction = ko.utils.domNodeDisposal.cleanExternalData;

            ko.utils.domNodeDisposal.cleanExternalData = function (node) {
                if (node.id === 'country') {
                    return;
                }

                normalFunction(node);
            };
        },

        showAddCardModal: function () {
            this.addNewCardVM.visible(true);

            $('html, body').animate({scrollTop: 0}, 400);
        },

        hideAddCardModal: function () {
            this.addNewCardVM.visible(false);
        },

        showAddPayPalModal: function () {
            var braintreePaypal = registry.get('new-form-braintree-paypal');

            braintreePaypal.setup();
            this.addNewPayPalVM.visible(true);
        },

        hideAddPayPalModal: function () {
            var braintreePaypal = registry.get('new-form-braintree-paypal');

            braintreePaypal.teardownPayPalInstance();
            this.addNewPayPalVM.visible(false);
        }
    });
});
