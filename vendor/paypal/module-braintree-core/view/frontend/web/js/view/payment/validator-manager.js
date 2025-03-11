/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/

define([
    'jquery',
    'Magento_Ui/js/model/messageList',
    'PayPal_Braintree/js/model/full-screen-loader'
], function (
    $,
    globalMessageList,
    defaultFullScreenLoader
) {
    'use strict';

    /**
     * New Validator handler implementation that can be used across website areas including checkout.
     */
    return {
        fullScreenLoader: defaultFullScreenLoader,
        validators: [],

        /**
         * Set the full screen loader implementation.
         *
         * @param fullScreenLoader
         */
        setFullScreenLoader: function (fullScreenLoader) {
            this.fullScreenLoader = fullScreenLoader;
        },

        /**
         * Get the full screen loader implementation.
         */
        getFullScreenLoader: function () {
            return this.fullScreenLoader;
        },

        /**
         * Add new validator if enabled.
         *
         * Always expect the validator to have the isEnabled method (property), if not skip.
         *
         * @param {Object} validator
         */
        add: function (validator) {
            if (!validator.hasOwnProperty('isEnabled')
                || typeof validator.isEnabled !== 'function'
                || !validator.isEnabled()
            ) {
                return;
            }

            this.validators.push(validator);
        },

        /**
         * Run pull of validators.
         *
         * @param {Object} context
         * @param {Function} callback
         * @param {Function} errorCallback
         */
        validate: function (context, callback, errorCallback) {
            let self = this,
                deferred;

            // no available validators
            if (!self.validators.length) {
                callback();

                return;
            }

            // get list of deferred validators
            deferred = $.map(self.validators, function (current) {
                return current.validate(context);
            });

            $.when.apply($, deferred)
                .done(function () {
                    callback();
                }).fail(function (error) {
                    errorCallback();
                    self.showError(error);
                });
        },

        /**
         * Show error message.
         *
         * @param {string} errorMessage
         */
        showError: function (errorMessage) {
            globalMessageList.addErrorMessage({
                message: errorMessage
            });

            this.getFullScreenLoader().stopLoader(true);
        }
    };
});
