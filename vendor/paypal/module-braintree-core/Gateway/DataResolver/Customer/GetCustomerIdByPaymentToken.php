<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Gateway\DataResolver\Customer;

use Braintree\TransactionSearch;
use InvalidArgumentException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use PayPal\Braintree\Gateway\Request\PaymentDataBuilder;
use PayPal\Braintree\Model\Adapter\BraintreeAdapter;
use Psr\Log\LoggerInterface;

class GetCustomerIdByPaymentToken implements GetCustomerIdByPaymentTokenInterface
{
    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var BraintreeAdapter
     */
    private BraintreeAdapter $adapter;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param SerializerInterface $serializer
     * @param BraintreeAdapter $adapter
     * @param LoggerInterface $logger
     */
    public function __construct(SerializerInterface $serializer, BraintreeAdapter $adapter, LoggerInterface $logger)
    {
        $this->serializer = $serializer;
        $this->adapter = $adapter;
        $this->logger = $logger;
    }

    /**
     * Get the Braintree Customer ID by payment token
     *
     * @param PaymentTokenInterface $paymentToken
     * @return string|null
     */
    public function execute(PaymentTokenInterface $paymentToken): ?string
    {
        try {
            $tokenDetails = $this->serializer->unserialize($paymentToken->getTokenDetails() ?: '{}');

            if (isset($tokenDetails[PaymentDataBuilder::CUSTOMER_ID])) {
                return $tokenDetails[PaymentDataBuilder::CUSTOMER_ID];
            }
        } catch (InvalidArgumentException $ex) {
            $this->logger->error('Failed to un-serialize PayPal token with error: ' . $ex->getMessage(), [
                'payment_token_entity_id' => $paymentToken->getEntityId(),
                'class' => GetCustomerIdByPaymentToken::class
            ]);

            return null;
        }

        $braintreeFilters = [TransactionSearch::paymentMethodToken()->is($paymentToken->getGatewayToken())];

        $braintreeTransaction = $this->adapter->search($braintreeFilters);

        // If no result or empty array, return null.
        if ($braintreeTransaction === null || $braintreeTransaction->maximumCount() === 0) {
            return null;
        }

        return $braintreeTransaction->firstItem()->customer['id'];
    }
}
