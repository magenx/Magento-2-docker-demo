/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'jquery-ui-modules/widget',
    'mage/translate'
], function ($) {
    'use strict';

    $.widget('mage.cards', {
        /**
         * Bind event handlers for adding cards.
         * @private
         */
        _create: function () {
            let options = this.options;
            let addCard = options.addCard;

            if (addCard) {
                $(document).on('click', addCard, this._addCard.bind(this));
            }
        },

        /**
         * Add a new card.
         * @private
         */
        _addCard: function () {
            window.location = this.options.addCardLocation;
        }
    });

    return $.mage.cards;
});
