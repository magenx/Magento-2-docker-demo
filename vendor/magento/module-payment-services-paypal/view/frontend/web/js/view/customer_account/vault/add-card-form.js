/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* eslint-disable no-undef */
define([
    "jquery",
    "underscore",
    "uiComponent",
    "scriptLoader",
    'Magento_PaymentServicesPaypal/js/lib/script-loader',
    'mage/translate',
    'Magento_Customer/js/customer-data',
], function ($, _, Component, loadSdkScript, scriptLoader, $t, customerData) {
    "use strict";

    const cardFormSubmitButton = document.getElementById("paymentservices-add-card-button");
    const paymentServicesAddVaultCardContainer = document.getElementById("paymentservices_add_vault_card");
    const cardFormAddress = $(".form-address-edit")

    return Component.extend({
        defaults: {
            template: "Magento_PaymentServicesPaypal/customer_account/vault/add-card-form",
            componentParams: null,
            paymentsSdk: null,
            paymentsSdkInitPromise: null,
            invalidFields: [],
            emptyErrorMessage: $t('This is a required field.'),
            fields: {
                number: {
                    errorMessage: $t('Please enter a valid credit card number.'),
                },
                expirationDate: {
                    errorMessage: $t('Incorrect credit card expiration date.'),
                },
                cvv: {
                    errorMessage: $t('Please enter a valid credit card security code.'),
                }
            }
        },

        /**
         * @inheritdoc
         */
        initialize: function () {
            this._super()
                .observe('invalidFields');

            this.initPaymentsSDK(this.componentParams);

            return this;
        },

        initPaymentsSDK: function ({ paymentsSDKUrl, storeViewCode, oauthToken, graphQLEndpointUrl }) {
            this.showLoader(true);
            this.paymentsSdkInitPromise = scriptLoader
                .loadCustom({url: paymentsSDKUrl})
                .then(async () => {
                    const sdkConfig = {
                        storeViewCode: storeViewCode
                    }

                    if (oauthToken) {
                        sdkConfig.getCustomerToken = () => oauthToken;
                    }

                    if (graphQLEndpointUrl) {
                        sdkConfig.apiUrl = graphQLEndpointUrl;
                    }

                    this.paymentsSdk = new PaymentServicesSDK(sdkConfig)
                    await this.paymentsSdk.Vault.init()
                });
        },

        /**
         * @inheritdoc
         */
        afterRender: function () {
            this.paymentsSdkInitPromise.then(async () => {

                this.showLoader(false);

                if (!this.paymentsSdk.Vault.CreditCard.isAvailable()) {
                    paymentServicesAddVaultCardContainer.innerHTML = this.displayMessage($t("CreditCard Vault is not available."), 'error');
                    return;
                }

                await this.paymentsSdk.Vault.CreditCard.render({
                    styles: {
                        input: {
                            'font-family': '"Open Sans","Helvetica Neue",Helvetica,Arial,sans-serif',
                            'font-size': '14px',
                            'font-weight': '400',
                            'border': '1px solid #c2c2c2',
                            'border-radius': '1px',
                            'padding': '0 9px',
                            'height': '32px',
                            'width': '100%',
                            'box-sizing': 'border-box',
                        },
                        ':focus': {
                            color: '#333'
                        },
                        '.valid': {
                            color: '#333'
                        },
                        '.invalid': {
                            'color': '#ed8380',
                            'box-shadow': 'none'
                        },
                        body: {
                            'margin': '0',
                            'padding': '0',
                        },
                        // Remove card icon
                        "input.card-field-number.display-icon + .card-icon": {
                            display: "none !important",
                            height: "0",
                        },
                        // Unindent card number
                        "input.card-field-number.display-icon": {
                            padding: "0 9px !important",
                        },
                    },
                    fields: {
                        description: {selector: "#card-vault-container #vault-card-description"},
                        number: {selector: "#card-vault-container #vault-card-number"},
                        expirationDate: {selector: "#card-vault-container #vault-expiration-date"},
                        cvv: {selector: "#card-vault-container #vault-cvv"},
                    },
                    onError: (error) => {
                        this.addMessage($t("Error while vaulting the card, please try again."), "error");
                        console.error(error.message)
                        this.showLoader(false);
                    },
                    onCancel: () => {
                        console.debug("user cancelled the card vaulting process");
                        this.showLoader(false);
                    },
                    getBillingAddress: this.getBillingAddress.bind(this),
                    onSuccess: () => {
                        this.showLoader(false);
                        paymentServicesAddVaultCardContainer.innerHTML = this.displayMessage($t('Card vaulted successfully. Please wait to be redirected to the Stored Payment Methods page.'), 'success');

                        // Redirect to saved card list page
                        setTimeout(() => {
                            window.location.replace(this.componentParams.savedCardListUrl);
                        }, 3000);
                    },
                    onValidityChange: this.onValidityChange.bind(this),
                }).then((creditCardVault) => {
                    cardFormSubmitButton.addEventListener("click", () => {

                        // Display errors for card fields
                        if (!creditCardVault.isFormValid()) {
                            var fields = creditCardVault.getFormState();
                            Object.keys(fields).forEach((field) => {
                                this.onValidityChange(fields, field);
                            })
                            creditCardVault.submit();
                        }

                        // Validate the address form
                        if (!cardFormAddress.validation() || !cardFormAddress.validation('isValid')) {
                            this.showLoader(false);
                            return;
                        }

                        if (creditCardVault.isFormValid()) {
                            this.showLoader(true);
                            creditCardVault.submit();
                        }
                    });
                }).catch((e) => {
                    this.showLoader(false);
                    this.addMessage($t("Error rendering Payment SDK, please reload the page and try again."), "error");
                    console.error('Error rendering Payments SDK', e);
                });
            }).catch((error) => {
                this.showLoader(false);
                paymentServicesAddVaultCardContainer.innerHTML = this.displayMessage($t("Error initializing Payment SDK, please reload the page and try again."), 'error');
                console.error("Error initializing Payment SDK:", error);
            });
        },

        /**
         * Provide the billing address from the form
         */
        getBillingAddress: function () {
            const regionInput = cardFormAddress.find("[name='region']").val();
            const regionIdDropdown = cardFormAddress.find("[name='region_id']");
            const regionIdValue = regionIdDropdown.val();
            const regionIdLabel = regionIdDropdown.val() ? regionIdDropdown.find(`option[value='${regionIdValue}']`).text() : "";

            return {
                firstName: cardFormAddress.find("[name='firstname']").val(),
                lastName: cardFormAddress.find("[name='lastname']").val(),
                streetAddress: cardFormAddress.find("[name='street[0]']").val(),
                extendedAddress: cardFormAddress.find("[name='street[1]']").val(),
                region: regionIdDropdown.prop('disabled') ? regionInput : regionIdLabel,
                locality: cardFormAddress.find("[name='city']").val(),
                postalCode: cardFormAddress.find("[name='postcode']").val(),
                countryCodeAlpha2: cardFormAddress.find("[name='country_id']").val(),
            };
        },

        /**
         * Show/hide loader.
         *
         * @param {Boolean} show
         */
        showLoader: function (show) {
            var event = show ? 'processStart' : 'processStop';

            $('body').trigger(event);
        },

        /**
         * Add message to customer data.
         *
         * @param {String} message
         * @param {String} [type]
         */
        addMessage: function (message, type) {
            type = type || 'error';
            customerData.set('messages', {
                messages: [{
                    type: type,
                    text: message
                }],
                'data_id': Math.floor(Date.now() / 1000)
            });
        },

        onValidityChange: function (fields, emittedBy) {
            var valid = _.every(fields, function (field) {
                return field.isValid
            });

            var invalidFields = this.invalidFields().filter(field => field.name !== emittedBy);

            if (!valid) {
                if (fields[emittedBy] && !fields[emittedBy].isValid) {
                    invalidFields.push({
                        name: emittedBy,
                        message: fields[emittedBy].isEmpty ? this.emptyErrorMessage : this.fields[emittedBy].errorMessage
                    });
                }
                this.invalidFields(invalidFields)
            } else {
                this.invalidFields([]);
            }
        },

        isFieldValid: function (fieldName) {
            return !this.invalidFields.findWhere({
                name: fieldName
            });
        },

        getFieldErrorMessage: function (fieldName) {
            return !this.isFieldValid(fieldName) ? this.invalidFields.findWhere({
                name: fieldName
            }).message : '';
        },

        displayMessage: function (text, type) {
            return `<div class="${type} message"><div>${$t(text)}</div></div>`;
        },
    });
});
