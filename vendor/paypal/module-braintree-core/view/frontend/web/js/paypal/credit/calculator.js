define([
    'underscore',
    'uiComponent'
], function (_, Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'PayPal_Braintree/credit/calculator',
            displaySummary: true, // "From X per month"
            displayInterestDetails: false, // Display the more in-depth summary of interest rates
            instalmentsFrom: 0,
            currentInstalment: {
                term: 0,
                monthlyPayment: 0,
                apr: 0,
                cost: 0,
                costIncInterest: 0
            },
            endpoint: null,
            instalments: [],
            visible: false,
            merchantName: ''
        },

        /**
         * initialize and observe the default variables
         *
         * @returns {*}
         */
        initObservable: function () {
            this._super();
            if (this.instalments.length > 0) {
                this.currentInstalment = this.instalments[0];
                this.instalmentsFrom = this.instalments[this.instalments.length - 1].monthlyPayment;
                this.visible = true;
            } else {
                this.loadInstalments();
            }

            this.observe(['instalments', 'currentInstalment', 'instalmentsFrom', 'visible']);
            return this;
        },

        /**
         * check current instalment
         *
         * @param term
         * @returns {boolean}
         */
        isCurrentInstalment: function (term) {
            return this.currentInstalment().term === term;
        },

        /**
         * set current instalment
         *
         * @param instalment
         */
        setCurrentInstalment: function (instalment) {
            this.currentInstalment(instalment);
        },

        /**
         * load instalments
         *
         * @returns {boolean}
         */
        loadInstalments: function () {
            if (!this.endpoint) {
                return false;
            }

            let self = this;

            require(['Magento_Checkout/js/model/quote', 'jquery'], function (quote, $) {
                if (typeof quote.totals()['base_grand_total'] === 'undefined') {
                    return false;
                }

                $.getJSON(self.endpoint, {amount: quote.totals()['base_grand_total']}, function (response) {
                    self.instalments(response);
                    self.setCurrentInstalment(response[0]);
                    self.visible(true);
                });
            });
        }
    });
});
