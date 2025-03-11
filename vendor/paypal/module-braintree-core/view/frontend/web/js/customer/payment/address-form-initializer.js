define([
    'jquery',
    'uiComponent',
    'ko',
    'PayPal_Braintree/js/customer/modals/address-modal',
    'mageUtils',
    'Magento_Checkout/js/model/payment/additional-validators'
], function (
    $,
    Component,
    ko,
    addressModal,
    utils,
    additionalValidators
) {
    'use strict';
    return Component.extend({

        defaults: {
            template: 'Paypal_Braintree/customer/payment/address-wrapper',
            addressModal: addressModal,
            deliveryIntervals: ko.observableArray(null),
            currentlySelectedInterval: ko.observable(null),
            minDatePickerValue: 1,
            standardDeliveryDays: 1,
            baseUrl: ko.observable(),
            updatedOrderEntityId: null,
            countryId: 'GB',
            submitBtnSelector: '#braintree_submit',
            phoneNumberMaxLength: ko.observable(11),
            phoneNumberMinLength: ko.observable(2),
            phoneNumberMaxLengthErrorVisible: ko.observable(false),
            phoneNumberMinLengthErrorVisible: ko.observable(false)
        },

        initialize: function () {
            this._super();
            let self = this;

            additionalValidators.registerValidator({
                validate: function () {
                    const $form = $('#form-validate');

                    $form.validation();
                    return $form.validation('isValid');
                }
            });

            fetch('/graphql', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    query: `{
                        countries {
                            full_name_locale,
                            two_letter_abbreviation
                        }
                    }`
                })
            }).then(response => response.json()).then(response => {
                const countries = response.data.countries || [];

                countries.forEach(country => {
                    self.addressModal.viewModel.countries.push({
                        countryCode: country.two_letter_abbreviation,
                        countryName: country.full_name_locale
                    });
                });
            });
        },

        toggleSubmit: function (disable) {
            var submitBtn = $(this.submitBtnSelector);

            if (submitBtn.length) {
                submitBtn.attr('disabled', disable);
            }
        },

        showAddressModal: function (entity_id, groupedOrdersLength, shippingId, countryId) {
            this.addressModal.viewModel.selectExistingVisible(true);
            this.addressModal.viewModel.currentCountryId(this.countryId);
            this.addressModal.showAddressModal(entity_id, groupedOrdersLength, shippingId, countryId);
            let addressLength = this.addressModal.viewModel.currentAddresses().length;

            this.addressModal.viewModel.newAddressFormVisible(addressLength === 0);
            this.showLookupForm();
            this.toggleSubmit(true);
        },

        showNewAddressForm: function () {
            this.addressModal.toggleNewAddAddressForm(true);
            if (this.addressModal.toggleNewAddAddressForm) {
                document.getElementById('form-validate').style.display = 'block';
            }
            this.addressModal.viewModel.isLookup(false);

            this.toggleSubmit(false);
        },

        showLookupForm: function () {
            this.addressModal.toggleNewAddAddressForm(true);
        },

        showExistingSelector: function () {
            this.addressModal.toggleNewAddAddressForm(false);
            if (this.addressModal.toggleNewAddAddressForm) {
                document.getElementById('form-validate').style.display = 'none';
            }

            this.toggleSubmit(false);
        },

        /**
         * @param {*} postCode
         * @param {*} countryId
         * @param {Array} postCodesPatterns
         * @return {Boolean}
         */
        validatePostCode: function (postCode, countryId) {
            var pattern, regex,
                patterns = window.checkout.postCodes[countryId];

            this.validatedPostCodeExample = [];

            if (!utils.isEmpty(postCode) && !utils.isEmpty(patterns)) {
                for (pattern in patterns) {
                    if (patterns.hasOwnProperty(pattern)) { //eslint-disable-line max-depth
                        this.validatedPostCodeExample.push(patterns[pattern].example);
                        regex = new RegExp(patterns[pattern].pattern);

                        if (regex.test(postCode)) { //eslint-disable-line max-depth
                            return true;
                        }
                    }
                }

                return false;
            }

            return true;
        },

        postcodeValidation: function (postcodeElement) {
            var countryId = this.countryId,
                validationResult,
                warnMessage,
                warnElement = $('.warning-postcode');

            if (postcodeElement == null || postcodeElement.val() == null) {
                return true;
            }

            warnElement.hide();
            warnElement.text('');

            validationResult = this.validatePostCode(postcodeElement.val(), countryId, []);

            if (!validationResult) {
                warnMessage = 'Please enter a valid post code.';

                warnElement.show();
                warnElement.text(warnMessage);

                if (warnMessage) {
                    warnElement.prev().addClass('input-postcode-error');
                }
            } else {
                warnElement.prev().removeClass('input-postcode-error');
            }

            return validationResult;
        }
    });
});
