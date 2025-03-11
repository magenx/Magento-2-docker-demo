define([
    'jquery',
    'rjsResolver'
], function ($, resolver) {
    'use strict';

    /**
     * New full screen loader (spinner) implementation with configurable container.
     * It can be used across website areas including checkout.
     */
    return {
        container: 'body',

        /**
         * Set the container element with its ID to replace default container.
         *
         * Only allow to set elements by ID (as expected to be unique.
         * If the container has already been changed, do not allow changing again as this be set once per component.
         *
         * @param {string} value
         */
        setContainer: function (value) {
            if (this.container !== 'body' || !value.startsWith('#')) {
                return;
            }

            this.container = value;
        },

        /**
         * Get the container element.
         *
         * @return {string}
         */
        getContainer: function () {
            return this.container;
        },

        /**
         * Start full page loader action
         */
        startLoader: function () {
            $(this.getContainer()).trigger('processStart');
        },

        /**
         * Stop full page loader action
         *
         * @param {Boolean} [forceStop]
         */
        stopLoader: function (forceStop) {
            let $elem = $(this.getContainer()),
                stop = $elem.trigger.bind($elem, 'processStop'); //eslint-disable-line jquery-no-bind-unbind

            forceStop ? stop() : resolver(stop);
        }
    };
});
