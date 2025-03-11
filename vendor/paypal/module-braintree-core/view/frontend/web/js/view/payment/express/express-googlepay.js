/**
 * Express GooglePay button component
 */

define([
    'jquery',
    'underscore',
    'uiComponent',
    'mage/url',
    'PayPal_Braintree/js/googlepay/button',
    'PayPal_Braintree/js/googlepay/api',
    'domReady!'
], function ($, _, Component, url, button, buttonApi) {
    'use strict';

    const config = _.get(window.checkoutConfig.payment, 'braintree_googlepay', {});

    return Component.extend({

        defaults: {
            template: 'PayPal_Braintree/express/express-googlepay',
            id: 'braintree-googlepay-express-payment',
            isActive: !_.isEmpty(config),
            clientToken: _.get(config, 'clientToken', null),
            merchantId: _.get(config, 'merchantId', null),
            currencyCode: window.checkoutConfig.quoteData.base_currency_code,
            actionSuccess: url.build('braintree/googlepay/review/'),
            amount: window.checkoutConfig.quoteData.base_grand_total,
            environment: _.get(config, 'environment', 'TEST'),
            cardTypes: _.get(config, 'cardTypes', []),
            btnColor: _.get(config, 'btnColor', ''),
            threeDSecure: null
        },

        /**
         * Is the payment method active.
         *
         * @return {boolean}
         */
        isMethodActive: function () {
            return this.isActive;
        },

        /**
         * Get the 3D Secure config object.
         *
         * @return {
        *   {
        *      thresholdAmount: (number|*),
        *      specificCountries: ([]|*),
        *      challengeRequested: (boolean|*),
        *      enabled: boolean
        *   } ||
        *   {
        *      thresholdAmount: number,
        *      specificCountries: *[],
        *      challengeRequested: boolean,
        *      enabled: boolean
        *   }
        * }
         */
        get3DSecureConfig: function () {
            let secureConfig = _.get(window.checkoutConfig.payment, 'three_d_secure', {});

            if (_.isEmpty(secureConfig)) {
                return {
                    'enabled': false,
                    'challengeRequested': false,
                    'thresholdAmount': 0.0,
                    'specificCountries': [],
                    'ipAddress': ''
                };
            }

            return {
                'enabled': true,
                'challengeRequested': secureConfig.challengeRequested ,
                'thresholdAmount': secureConfig.thresholdAmount,
                'specificCountries': secureConfig.specificCountries,
                'ipAddress': secureConfig.ipAddress
            };
        },

        /**
         * Initialize Google Pay express.
         */
        initGooglePayExpress: function () {
            if (!this.isMethodActive()) {
                return;
            }

            this.threeDSecure = this.get3DSecureConfig();

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
        },

        /**
         * @returns {Object}
         */
        initialize: function () {
            this._super();

            return this;
        }
    });
});
