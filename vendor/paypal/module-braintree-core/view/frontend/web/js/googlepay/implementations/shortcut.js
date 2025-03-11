/**
 * Braintree Google Pay mini cart payment method integration.
 **/
define(
    [
        'uiComponent',
        'PayPal_Braintree/js/googlepay/button',
        'PayPal_Braintree/js/googlepay/api',
        'domReady!'
    ],
    function (
        Component,
        button,
        buttonApi
    ) {
        'use strict';

        return Component.extend({

            defaults: {
                id: null,
                clientToken: null,
                merchantId: null,
                currencyCode: null,
                actionSuccess: null,
                amount: null,
                environment: 'TEST',
                cardType: [],
                btnColor: 0,
                threeDSecure: null
            },

            /**
             * @returns {Object}
             */
            initialize: function () {
                this._super();

                /* Add client token & environment to 3DS Config */
                this.threeDSecure.clientToken = this.clientToken;
                this.threeDSecure.environment = this.environment;

                let api = new buttonApi();

                api.setEnvironment(this.environment);
                api.setCurrencyCode(this.currencyCode);
                api.setClientToken(this.clientToken);
                api.setMerchantId(this.merchantId);
                api.setActionSuccess(this.actionSuccess);
                api.setAmount(this.amount);
                api.setCardTypes(this.cardTypes);
                api.setBtnColor(this.btnColor);
                api.setThreeDSecureValidatorConfig(this.threeDSecure);

                // Attach the button
                button.init(
                    document.getElementById(this.id),
                    api
                );

                return this;
            }
        });
    }
);
