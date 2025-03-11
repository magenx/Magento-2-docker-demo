<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\BraintreeCustomerBalance\Plugin\Level23Processing\PayPal;

use Braintree\TransactionLineItem;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use PayPal\Braintree\Gateway\Config\PayPal\Config as PayPalConfig;
use PayPal\Braintree\Gateway\Data\Order\OrderAdapter;
use PayPal\Braintree\Gateway\Helper\SubjectReader;
use PayPal\Braintree\Gateway\Request\Level23ProcessingDataBuilder;

class AddCustomerBalancePlugin
{
    /**
     * @var SubjectReader
     */
    private SubjectReader $subjectReader;

    /**
     * @var quoteRepository
     */
    private QuoteRepository $quoteRepository;

    /**
     * @var PayPalConfig
     */
    private PayPalConfig $payPalConfig;

    /**
     * Constructor
     *
     * @param SubjectReader $subjectReader
     * @param QuoteRepository $quoteRepository
     * @param PayPalConfig $payPalConfig
     */
    public function __construct(
        SubjectReader $subjectReader,
        QuoteRepository $quoteRepository,
        PayPalConfig $payPalConfig
    ) {
        $this->subjectReader = $subjectReader;
        $this->quoteRepository = $quoteRepository;
        $this->payPalConfig = $payPalConfig;
    }

    /**
     * Add 'Store Credit' as Line Items for the PayPal transactions
     *
     * @param Level23ProcessingDataBuilder $subject
     * @param array $result
     * @param array $buildSubject
     * @return array
     * @throws NoSuchEntityException
     */
    public function afterBuild(Level23ProcessingDataBuilder $subject, array $result, array $buildSubject): array
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        /** @var OrderPaymentInterface $payment */
        $payment = $paymentDO->getPayment();
        /** @var OrderAdapter $order */
        $order = $paymentDO->getOrder();

        $isPayPal = $payment->getMethod() === 'braintree_paypal'
            || $payment->getMethod() === 'braintree_paypal_vault';
        $ppLineItems = $this->payPalConfig->canSendCartLineItemsForPayPal();

        if (isset($result[Level23ProcessingDataBuilder::KEY_LINE_ITEMS])
            && $ppLineItems
            && $isPayPal
        ) {
            $lineItems = $result[Level23ProcessingDataBuilder::KEY_LINE_ITEMS];

            /** Render quote from order to get store credit (customer balance) */
            $quote = $this->quoteRepository->get($order->getQuoteId());

            /**
             * Adds Store Credit as credit LineItems for the PayPal
             * transaction if store credit is greater than 0(Zero)
             * to manage the totals with server-side implementation
             */
            if ($quote->getBaseCustomerBalAmountUsed()) {
                $customerBalAmount = $subject->numberToString(
                    abs((float)$quote->getBaseCustomerBalAmountUsed()),
                    2
                );
                if ($customerBalAmount > 0) {
                    $storeCreditItems[] = [
                        'name' => 'Store Credit',
                        'kind' => TransactionLineItem::CREDIT,
                        'quantity' => 1.00,
                        'unitAmount' => $customerBalAmount,
                        'totalAmount' => $customerBalAmount
                    ];

                    $lineItems = array_merge($lineItems, $storeCreditItems);
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
