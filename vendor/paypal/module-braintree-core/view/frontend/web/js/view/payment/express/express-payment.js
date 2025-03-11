/**
 * General express payment component that initializes braintree payment buttons
 */
define([
    'jquery',
    'uiComponent',
    'domReady!'
], function ($, Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'PayPal_Braintree/express/express-payment'
        },

        /**
         * Initializes regular properties of instance.
         *
         * @returns {Object} Chainable.
         */
        initConfig: function () {
            this._super();

            return this;
        }
    });
});
