define([
    'jquery',
    'underscore',
    'PayPal_Braintree/js/helper/format-amount',
    'PayPal_Braintree/js/helper/replace-unsupported-characters',
    'Magento_Checkout/js/model/quote'
], function (
    $,
    _,
    formatAmount,
    replaceUnsupportedCharacters,
    quote
) {
    'use strict';

    /**
     * Get line items
     *
     * @returns {Array}
     */
    return function () {
        let lineItems = [];

        /**
         * Line items array
         *
         * {Array}
         */
        var lineItemsArray = [
            'name',
            'kind',
            'quantity',
            'unitAmount',
            'productCode',
            'description'
        ];

        if (window.checkoutConfig.payment['braintree_paypal'].canSendLineItems) {
            let giftWrappingItems = 0, giftWrappingOrder = 0, storeCredit = 0, giftCardAccount = 0,
                giftWrappingPrintedCard = 0,
                baseDiscountAmount = formatAmount(Math.abs(quote.totals()['base_discount_amount']).toString()),
                baseTaxAmount = formatAmount(quote.totals()['base_tax_amount']);

            $.each(quote.totals()['total_segments'], function (segmentsKey, segmentsItem) {
                if (segmentsItem['code'] === 'customerbalance') {
                    storeCredit = formatAmount(Math.abs(segmentsItem['value']).toString());
                }
                if (segmentsItem['code'] === 'giftcardaccount') {
                    giftCardAccount = formatAmount(Math.abs(segmentsItem['value']).toString());
                }
                if (segmentsItem['code'] === 'giftwrapping') {
                    let extensionAttributes = segmentsItem['extension_attributes'];

                    giftWrappingOrder = extensionAttributes['gw_base_price'];
                    giftWrappingItems = extensionAttributes['gw_items_base_price'];
                    giftWrappingPrintedCard = extensionAttributes['gw_card_base_price'];
                }
            });

            $.each(quote.getItems(), function (quoteItemKey, quoteItem) {
                if (quoteItem.parent_item_id !== null || quoteItem.price === 0.0) {
                    return true;
                }

                let floatQty = parseFloat(quoteItem.qty),
                    itemName = replaceUnsupportedCharacters(quoteItem.name),
                    itemSku = replaceUnsupportedCharacters(quoteItem.sku),
                    hasQty = floatQty > Math.floor(floatQty) && floatQty < Math.ceil(floatQty),
                    description = hasQty
                        ? 'Item quantity is ' + floatQty.toFixed(2) + ' and per unit amount is '
                            + parseFloat(quoteItem.price).toFixed(2)
                        : '',
                    itemUnitAmount = hasQty
                        ? parseFloat(floatQty * parseFloat(quoteItem.price)) : parseFloat(quoteItem.price),
                    itemQty = hasQty ? parseFloat('1') : floatQty,
                    lineItemValues = [
                        itemName,
                        'debit',
                        itemQty.toFixed(2),
                        itemUnitAmount.toFixed(2),
                        itemSku,
                        description
                    ],

                    mappedLineItems = $.map(lineItemsArray, function (itemElement, itemIndex) {
                        return [[
                            lineItemsArray[itemIndex],
                            lineItemValues[itemIndex]
                        ]];
                    });

                lineItems[quoteItemKey] = Object.fromEntries(mappedLineItems);
            });

            /**
             * Adds credit (refund or discount) kind as LineItems for the
             * PayPal transaction if discount amount is greater than 0(Zero)
             * as discountAmount lineItem field is not being used by PayPal.
             *
             * developer.paypal.com/braintree/docs/reference/response/transaction-line-item/php#discount_amount
             */
            if (baseDiscountAmount > 0) {
                let discountLineItem = {
                    'name': 'Discount',
                    'kind': 'credit',
                    'quantity': 1.00,
                    'unitAmount': baseDiscountAmount
                };

                lineItems = $.merge(lineItems, [discountLineItem]);
            }

            /**
             * Adds shipping as LineItems for the PayPal transaction
             * if shipping amount is greater than 0(Zero) to manage
             * the totals with client-side implementation as there is
             * no any field exist in the client-side implementation
             * to send the shipping amount to the Braintree.
             */
            if (quote.totals()['base_shipping_amount'] > 0) {
                let shippingLineItem = {
                    'name': 'Shipping',
                    'kind': 'debit',
                    'quantity': 1.00,
                    'unitAmount': quote.totals()['base_shipping_amount']
                };

                lineItems = $.merge(lineItems, [shippingLineItem]);
            }

            if (baseTaxAmount > 0) {
                let taxLineItem = {
                    'name': 'Tax',
                    'kind': 'debit',
                    'quantity': 1.00,
                    'unitAmount': baseTaxAmount
                };

                lineItems = $.merge(lineItems, [taxLineItem]);
            }

            /**
             * Adds credit (Store Credit) kind as LineItems for the
             * PayPal transaction if store credit is greater than 0(Zero)
             * to manage the totals with client-side implementation
             */
            if (storeCredit > 0) {
                let storeCreditItem = {
                    'name': 'Store Credit',
                    'kind': 'credit',
                    'quantity': 1.00,
                    'unitAmount': storeCredit
                };

                lineItems = $.merge(lineItems, [storeCreditItem]);
            }

            /**
             * Adds Gift Wrapping for items as LineItems for the PayPal
             * transaction if it is greater than 0(Zero) to manage
             * the totals with client-side implementation
             */
            if (giftWrappingItems > 0) {
                let gwItems = {
                    'name': 'Gift Wrapping for Items',
                    'kind': 'debit',
                    'quantity': 1.00,
                    'unitAmount': giftWrappingItems
                };

                lineItems = $.merge(lineItems, [gwItems]);
            }

            /**
             * Adds Gift Wrapping for order as LineItems for the PayPal
             * transaction if it is greater than 0(Zero) to manage
             * the totals with client-side implementation
             */
            if (giftWrappingOrder > 0) {
                let gwOrderItem = {
                    'name': 'Gift Wrapping for Order',
                    'kind': 'debit',
                    'quantity': 1.00,
                    'unitAmount': giftWrappingOrder
                };

                lineItems = $.merge(lineItems, [gwOrderItem]);
            }

            /**
             * Adds Gift Wrapping Printed Card as LineItems for the PayPal
             * transaction if it is greater than 0(Zero) to manage
             * the totals with client-side implementation
             */
            if (giftWrappingPrintedCard > 0) {
                let gwPrintedCard = {
                    'name': 'Printed Card',
                    'kind': 'debit',
                    'quantity': 1.00,
                    'unitAmount': giftWrappingPrintedCard
                };

                lineItems = $.merge(lineItems, [gwPrintedCard]);
            }

            /**
             * Adds Gift Cards as credit LineItems for the PayPal
             * transaction if it is greater than 0(Zero) to manage
             * the totals with client-side implementation
             */
            if (giftCardAccount > 0) {
                let giftCardItem = {
                    'name': 'Gift Cards',
                    'kind': 'credit',
                    'quantity': 1.00,
                    'unitAmount': giftCardAccount
                };

                lineItems = $.merge(lineItems, [giftCardItem]);
            }

            if (lineItems.length >= 250) {
                lineItems = [];
            }
        }
        return lineItems;
    };
});
