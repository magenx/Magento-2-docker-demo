define([
    'underscore'
], function (_) {
    'use strict';

    /**
     * Replace single quote character to HTML entity string.
     *
     * @param {string} value
     * @return {string}
     */
    return function (value) {
        return _.isString(value) ? value.replace(/'/g, '&apos;') : '';
    };
});
