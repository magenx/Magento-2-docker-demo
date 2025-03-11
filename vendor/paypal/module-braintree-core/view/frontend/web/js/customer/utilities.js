/* eslint-disable max-depth */
define([
    'jquery',
    'ko',
    'mage/url',
    'mage/translate',
    'mage/calendar'
], function ($, ko, urlBuilder) {
    'use strict';
    return {

        viewModel: {
            activeVM: ko.observableArray(null),
            pausedVM: ko.observableArray(null),
            pendingVM: ko.observableArray(null),

            updatedOrderEntityId: ko.observable(null),
            showBanner: ko.observable(false),
            errorModal: {
                visible: ko.observable(false),
                message: ko.observable(null)
            }
        },
        sectionCounter: 0,

        makeCall: function (url, verb, callback, viewModel) {
            var self = this;

            $.ajax({
                url: urlBuilder.build(url),
                contentType: 'application/json',
                global: true,
                type: verb,
                showLoader: true,
                cache: false,
                success: function (response) {
                    callback(response);
                    self.handleSuccess(self.viewModel.updatedOrderEntityId());
                },
                error: function (xhr, status, error) {
                    $('body').trigger('processStop');
                    if (viewModel) {
                        self.hideModal(viewModel);
                    }

                    self.handleError(xhr, status, error);
                }
            });
        },

        //Get entity ID from response json back from update
        //After section updates, run this to find the updatedOrderEntityId value and add the class
        //Find a div with that ID and add a class to it
        handleSuccess: function (entity_id) {
            var self = this,
                successClass = 'updated-success',
                orderItem = $('.repeat-order-id-' + entity_id),
                parentContainer = orderItem.closest('.repeat-order');

            // If there is another success message on an item then reset
            if ($('.updated-success').length) {
                $(this).removeClass(successClass);
            }

            orderItem.addClass(successClass);
            parentContainer.addClass(successClass);

            //Remove the class
            //Set the entityid value back to null, so the success message is only shown once
            setTimeout(function () {
                orderItem.removeClass(successClass);
                parentContainer.removeClass(successClass);
                self.viewModel.updatedOrderEntityId(null);
            }, 20000);
        },

        hideModal: function (viewModel) {
            return viewModel.visible(false);
        },

        //Get a message relating to a particular response and show it on the page in a modal
        handleError: function (xhr) {
            this.viewModel.errorModal.message(JSON.parse(xhr.responseText).message);
            this.viewModel.errorModal.visible(true);
        },

        formatDate: function (value, format) {
            if (!format || typeof format == 'undefined') {
                format = 'MM dd, yy';
            }

            return $.datepicker.formatDate(format, new Date(value));
        },

        formatCurrency: function (value) {
            //If the price is a negative number, make it a positive number. eg -1.25 becomes 1.25
            var formattedValue = Math.abs(value);

            //Set number to 2 decimal places as it is a price
            formattedValue = formattedValue.toFixed(2);

            return formattedValue;
        },

        formatWeeks: function (value) {
            var dayValue = value / 7;

            return dayValue === 1 ? $.mage.__('Week') : dayValue + ' ' + $.mage.__('Weeks');
        },

        //Used for payment details as they are formatted as a string of json:
        //eg '{"type":"VI","maskedCC":"1111","expirationDate":"11\/2022"}'
        parseStringtoJSON: function (string) {
            var result = JSON.parse(string);

            return result;
        },

        //Find the image url in the product.media_gallery_entries array
        getImgUrl: function (data) {
            var imgUrl,
                i = 0,
                j = 0,
                dataLength = data.length;

            for (i = 0; i < dataLength; i++) {

                for (j = 0; j < data[i].types.length; j++) {
                    if (data[i].types[j] === 'thumbnail') {
                        imgUrl = data[i].file;

                        break;
                    }
                }
            }

            return '/media/catalog/product' + imgUrl;
        },

        //Find the image label to be used as an alt tag in the product.media_gallery_entries array
        getImgAltTag: function (data) {
            var altTag,
                i = 0,
                j = 0,
                dataLength = data.length;

            for (i = 0; i < dataLength; i++) {

                for (j = 0; j < data[i].types.length; j++) {
                    altTag = this.updateAltTag(data[i].types[j]);

                    if (data[i].types[j] === 'thumbnail') {

                        if (data[i].label === null || data[i].label === '') {
                            altTag = ' ';
                        }
                        else {
                            altTag = data[i].label;
                        }

                        break;
                    }
                }
            }

            return altTag;
        }
    };
});
