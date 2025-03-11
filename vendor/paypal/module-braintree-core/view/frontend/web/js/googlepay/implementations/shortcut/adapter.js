/*browser:true*/
define([
    'underscore',
    'jquery',
    'mage/translate',
    'braintree',
    'PayPal_Braintree/js/model/full-screen-loader',
    'Magento_Ui/js/model/messageList'
], function (
    _,
    $,
    $t,
    client,
    defaultFullScreenLoader,
    globalMessageList
) {
    'use strict';

    /**
     * Braintree Client adapter implementation generic enough to be used for GooglePay button payments.
     *
     * This can be used in other pages along with the basket page, but not in the checkout page when placing an order.
     */
    return {
        fullScreenLoader: defaultFullScreenLoader,
        apiClient: null,
        config: {},
        checkout: null,
        clientInstance: null,
        code: 'braintree',

        /**
         * {Object}
         */
        events: {
            onClick: null,
            onCancel: null,
            onError: null
        },

        /**
         * Get Braintree api client.
         *
         * @returns {Object}
         */
        getApiClient: function () {
            return this.clientInstance;
        },

        /**
         * Set configuration.
         *
         * @param {Object} config
         */
        setConfig: function (config) {
            this.config = config;

            if (_.has(this.config, 'code')) {
                this.code = this.config.code;
            }
        },

        /**
         * Get payment name.
         *
         * @returns {string}
         */
        getCode: function () {
            return this.code;
        },

        /**
         * Get client token
         *
         * @returns {string|*}
         */
        getClientToken: function () {
            return this.config.hasOwnProperty('clientToken') ? this.config.clientToken : null;
        },

        /**
         * @returns {string}
         */
        getEnvironment: function () {
            return this.config.hasOwnProperty('environment') ? this.config.environment : null;
        },

        /**
         * Set fullscreen loader implementation allowing to use custom.
         *
         * @param {Function} fullScreenLoader
         */
        setFullScreenLoader: function (fullScreenLoader) {
            this.fullScreenLoader = fullScreenLoader;
        },

        /**
         * Get the full screen loader implementation.
         *
         * @return {Object}
         */
        getFullScreenLoader: function () {
            return this.fullScreenLoader;
        },

        /**
         * Show error message
         *
         * @param {string} errorMessage
         */
        showError: function (errorMessage) {
            globalMessageList.addErrorMessage({
                message: errorMessage
            });

            this.getFullScreenLoader().stopLoader(true);
        },

        /**
         * Setup Braintree SDK.
         *
         * @param {Function|null} callback
         */
        setup: function (callback = null) {
            if (!this.getClientToken()) {
                this.showError($t('Sorry, but something went wrong.'));
                return;
            }

            if (this.clientInstance) {
                if (typeof this.config.onReady === 'function') {
                    this.config.onReady(this);
                }

                if (typeof callback === 'function') {
                    callback(this.clientInstance);
                }

                return;
            }

            client.create({
                authorization: this.getClientToken()
            }, function (clientErr, clientInstance) {
                if (clientErr) {
                    console.error('Braintree Setup Error', clientErr);
                    return this.showError('Sorry, but something went wrong. Please contact the store owner.');
                }

                this.clientInstance = clientInstance;

                if (typeof this.config.onReady === 'function') {
                    this.config.onReady(this);
                }

                if (typeof callback === 'function') {
                    callback(this.clientInstance);
                }
            }.bind(this));
        }
    };
});
