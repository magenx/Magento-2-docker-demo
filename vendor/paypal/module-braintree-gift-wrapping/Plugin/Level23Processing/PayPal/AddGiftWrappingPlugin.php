<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\BraintreeGiftWrapping\Plugin\Level23Processing\PayPal;

use Braintree\TransactionLineItem;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use PayPal\Braintree\Gateway\Config\PayPal\Config as PayPalConfig;
use PayPal\Braintree\Gateway\Data\Order\OrderAdapter;
use PayPal\Braintree\Gateway\Helper\SubjectReader;
use PayPal\Braintree\Gateway\Request\Level23ProcessingDataBuilder;

class AddGiftWrappingPlugin
{
    /**
     * @var SubjectReader
     */
    private SubjectReader $subjectReader;

    /**
     * @var PayPalConfig
     */
    private PayPalConfig $payPalConfig;

    /**
     * Constructor
     *
     * @param SubjectReader $subjectReader
     * @param PayPalConfig $payPalConfig
     */
    public function __construct(
        SubjectReader $subjectReader,
        PayPalConfig $payPalConfig
    ) {
        $this->subjectReader = $subjectReader;
        $this->payPalConfig = $payPalConfig;
    }

    /**
     * Add 'Gift Wrapping' as line items for the PayPal transactions
     *
     * @param Level23ProcessingDataBuilder $subject
     * @param array $result
     * @param array $buildSubject
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function afterBuild(Level23ProcessingDataBuilder $subject, array $result, array $buildSubject): array
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        /** @var OrderPaymentInterface $payment */
        $payment = $paymentDO->getPayment();
        /** @var OrderAdapter $order */
        $order = $paymentDO->getOrder();

        $sendCartLineItems = $this->payPalConfig->canSendCartLineItemsForPayPal();
        $isPpPaymentMethod = $payment->getMethod() === 'braintree_paypal_vault' ||
            $payment->getMethod() === 'braintree_paypal';

        if (isset($result[Level23ProcessingDataBuilder::KEY_LINE_ITEMS])
            && $isPpPaymentMethod
            && $sendCartLineItems
        ) {
            $lineItems = $result[Level23ProcessingDataBuilder::KEY_LINE_ITEMS];

            /** Get Order Extension Attributes */
            $extensionAttributes = $order->getExtensionAttributes();
            if ($extensionAttributes) {
                /**
                 * Adds Gift Wrapping for Order as LineItems for the PayPal
                 * transaction if it is greater than 0(Zero) to manage
                 * the totals with server-side implementation
                 */
                if (method_exists($extensionAttributes, 'getGwBasePrice')) {
                    $gwBasePrice = $subject->numberToString($extensionAttributes->getGwBasePrice(), 2);
                    if ($gwBasePrice > 0) {
                        $gwBasePriceItems[] = [
                            'name' => 'Gift Wrapping for Order',
                            'kind' => TransactionLineItem::DEBIT,
                            'quantity' => 1.00,
                            'unitAmount' => $gwBasePrice,
                            'totalAmount' => $gwBasePrice
                        ];

                        $lineItems = array_merge($lineItems, $gwBasePriceItems);
                    }
                }

                /**
                 * Adds Gift Wrapping for items as LineItems for the PayPal
                 * transaction if it is greater than 0(Zero) to manage
                 * the totals with server-side implementation
                 */
                if (method_exists($extensionAttributes, 'getGwItemsBasePrice')) {
                    $gwItemsBasePrice = $subject->numberToString($extensionAttributes->getGwItemsBasePrice(), 2);
                    if ($gwItemsBasePrice > 0) {
                        $giftWrapItems[] = [
                            'name' => 'Gift Wrapping for Items',
                            'kind' => TransactionLineItem::DEBIT,
                            'quantity' => 1.00,
                            'unitAmount' => $gwItemsBasePrice,
                            'totalAmount' => $gwItemsBasePrice
                        ];

                        $lineItems = array_merge($lineItems, $giftWrapItems);
                    }
                }

                /**
                 * Adds Gift Wrapping Printed Card as LineItems for the PayPal
                 * transaction if it is greater than 0(Zero) to manage
                 * the totals with server-side implementation
                 */
                if (method_exists($extensionAttributes, 'getGwCardBasePrice')) {
                    $gwCardBasePrice = $subject->numberToString($extensionAttributes->getGwCardBasePrice(), 2);
                    if ($gwCardBasePrice > 0) {
                        $giftWrapCardItems[] = [
                            'name' => 'Printed Card',
                            'kind' => TransactionLineItem::DEBIT,
                            'quantity' => 1.00,
                            'unitAmount' => $gwCardBasePrice,
                            'totalAmount' => $gwCardBasePrice
                        ];

                        $lineItems = array_merge($lineItems, $giftWrapCardItems);
                    }
                }
            }

            if (count($lineItems) < 250) {
                $result[Level23ProcessingDataBuilder::KEY_LINE_ITEMS] = $lineItems;
            } else {
                unset($result[Level23ProcessingDataBuilder::KEY_LINE_ITEMS]);
            }
        }

        return $result;
    }
}
