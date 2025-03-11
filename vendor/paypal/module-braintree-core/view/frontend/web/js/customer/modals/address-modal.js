define([
    'jquery',
    'ko',
    'PayPal_Braintree/js/customer/utilities',
    'mageUtils'
], function ($, ko, utilities, utils) {
    'use strict';

    return {
        viewModel: {
            visible: ko.observable(false),
            newAddressFormVisible: ko.observable(false),
            selectExistingVisible: ko.observable(false),
            currentAddresses: ko.observableArray([]),
            initialSubscriptionAddressId: ko.observable(null),
            currentShippingId: ko.observable(null),
            currentEntityId: ko.observable(null),
            currentOrderIsGrouped: ko.observable(false),
            useForSelected: ko.observable(false),
            saveAddressDisabled: ko.observable(true),
            confirmationVisibleType: ko.observable(null),
            defaultForAllAddressId: ko.observable(null),
            isLookup: ko.observable(true),
            newAddress: {
                firstName: document.getElementById('firstname').value,
                lastName: document.getElementById('lastname').value,
                street: document.getElementById('street_1').value,
                street2: document.getElementById('street_2').value,
                city: document.getElementById('city').value,
                postcode: document.getElementById('zip').value,
                country: document.getElementById('country').value,
                telephone: document.getElementById('telephone').value,
                region: document.getElementById('region_id').value
            },
            currentCountryId: ko.observable(null),
            countries: ko.observableArray(null)
        },

        fetchAllAddressUrl: 'rest/V1/repeat-orders/user-addresses',
        assignAddressUrl: 'rest/V1/repeat-orders/grouped/set-shipping-address/',
        useForAllUrl: 'rest/V1/repeat-orders/set-shipping-address-for-all/',
        addAddressUrl: 'rest/V1/repeat-orders/add-shipping-address/',
        validatedPostCodeExample: [],

        showAddressModal: function (entity_id, groupedOrdersLength, shippingId, countryId) {
            var self = this;

            this.viewModel.visible(true);
            this.viewModel.selectExistingVisible(true);
            this.viewModel.currentEntityId(entity_id);
            this.viewModel.initialSubscriptionAddressId(shippingId);
            this.viewModel.currentOrderIsGrouped(groupedOrdersLength > 1);
            this.viewModel.useForSelected(false);
            this.clearAddressField();

            fetch('/graphql', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    query: `{
                customer {
                    addresses {
                        id,
                        street,
                        country_id,
                        region {
                            region_code
                        },
                        telephone,
                        postcode,
                        firstname,
                        lastname,
                        city
                    }
                }
              }`
                })
            }).then(response => response.json()).then(response => {
                const addresses = response.data.customer?.addresses || [];

                self.viewModel.currentAddresses.removeAll();

                //Get addresses from response and put them in an observable array
                //The template looks at the array and builds the <select> dropdown form
                for (let i = 0; i < addresses.length; i++) {
                    const address = {
                        id: addresses[i].id,
                        firstname: addresses[i].firstname,
                        lastname: addresses[i].lastname,
                        region: {
                            region_code: addresses[i].region.region_code
                        },
                        telephone: addresses[i].telephone,
                        postcode: addresses[i].postcode,
                        country_id: addresses[i].country_id,
                        city: addresses[i].city,
                        street: addresses[i].street.join(', ')
                    };

                    self.viewModel.currentAddresses.push(address);
                }

                self.viewModel.currentShippingId(shippingId);
                self.viewModel.currentCountryId(countryId);
                self.viewModel.saveAddressDisabled(true);
            });
        },

        hideAddressModal: function () {
            this.viewModel.newAddressFormVisible(false);
            this.viewModel.selectExistingVisible(false);
            this.viewModel.confirmationVisibleType(null);
            this.viewModel.visible(false);

            // Remove previous clickToAddress initialization block
            $('#cc_c2a').remove();
        },

        showConfirmChangeAddress: function (event, context, type) {
            if (type === 'new') {
                let form = $(event.target).closest('form');

                if (!(form.validation() && form.validation('isValid'))) {
                    return false;
                }
            }

            this.viewModel.newAddressFormVisible(false);
            this.viewModel.selectExistingVisible(false);
            this.viewModel.confirmationVisibleType(type);
        },

        /**
     * @param {*} postCode
     * @param {*} countryId
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
            var countryId = $('select[name="country"]:visible').val(),
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
        },

        toggleNewAddAddressForm: function (show) {
            this.viewModel.newAddressFormVisible(show);
            this.viewModel.selectExistingVisible(!show);
            this.viewModel.isLookup(show);
            this.clearAddressField();
        },

        clearAddressField: function () {
            $('#cc_c2a').remove();
            this.viewModel.currentShippingId(null);
            this.viewModel.newAddress.street = null;
            this.viewModel.newAddress.street = null;
            this.viewModel.newAddress.street2 = null;
            this.viewModel.newAddress.city = null;
            this.viewModel.newAddress.postcode = null;
            this.viewModel.newAddress.telephone = null;
            this.viewModel.newAddress.country = this.viewModel.currentCountryId();
        },

        updateCurrentAddressId: function () {
            var current = this.viewModel.currentShippingId(),
                initial = this.viewModel.initialSubscriptionAddressId();

            this.viewModel.saveAddressDisabled(current === initial);
            this.viewModel.useForSelected(false);
        },

        onUseForAllChange: function (type, event) {
            if (event && event.target) {
                this.viewModel.useForSelected(event.target.checked);
            }

            if (type === 'existing') {
                let currentId = this.viewModel.currentShippingId(),
                    initialId = this.viewModel.initialSubscriptionAddressId(),
                    defaultForAll = this.viewModel.defaultForAllAddressId();

                if (currentId === initialId && defaultForAll === currentId && this.viewModel.useForSelected()) {
                    this.viewModel.saveAddressDisabled(!this.viewModel.saveAddressDisabled());
                }
            }

            return true;
        },

        submitChanges: function (event) {
            if (this.viewModel.confirmationVisibleType() === 'new') {
                return this.submitNewAddress(event);
            }

            return this.submitExistingAddress();
        },

        submitExistingAddress: function () {
            var self = this,
                entityId = this.viewModel.currentEntityId(),
                addressId = this.viewModel.currentShippingId(),
                useForAll = this.viewModel.useForSelected(),
                url = (useForAll ? this.useForAllUrl : this.assignAddressUrl + entityId + '/') + addressId,
                verb = useForAll ? 'POST' : 'PUT';

            utilities.makeCall(url, verb, function () {
                self.hideAddressModal();
                utilities.viewModel.updatedOrderEntityId(entityId);
            }, self.viewModel);

            return true;
        },

        submitNewAddress: function () {
            var self = this,
                entityId = this.viewModel.currentEntityId,
                firstName = this.viewModel.newAddress.firstName,
                lastName = this.viewModel.newAddress.lastName,
                street = this.viewModel.newAddress.street,
                city = this.viewModel.newAddress.city,
                postcode = this.viewModel.newAddress.postcode,
                country = this.viewModel.currentCountryId,
                telephone = this.viewModel.newAddress.telephone,
                url = this.addAddressUrl +
          '?entityId=' + entityId +
          '&firstName=' + firstName +
          '&lastName=' + lastName +
          '&street=' + street +
          '&postcode=' + postcode +
          '&city=' + city +
          '&countryId=' + country +
          '&telephone=' + telephone;

            utilities.makeCall(
                url,
                'POST',
                function (response) {
                    self.viewModel.currentShippingId(response.id);
                    self.submitExistingAddress();
                }, self.viewModel);

            return true;
        }
    };
});
