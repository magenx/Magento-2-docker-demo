<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Plugin;

use Exception;
use PayPal\Braintree\Model\Adapter\BraintreeAdapter;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Psr\Log\LoggerInterface;

class DeleteStoredPaymentPlugin
{
    private const BT_PAYMENT_METHOD_CODES = [
        'braintree',
        'braintree_paypal',
        'braintree_applepay',
        'braintree_googlepay',
        'braintree_venmo',
        'braintree_ach_direct_debit'
    ];

    /**
     * @var BraintreeAdapter
     */
    private BraintreeAdapter $braintreeAdapter;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * DeleteStoredPaymentPlugin constructor
     *
     * @param BraintreeAdapter $braintreeAdapter
     * @param LoggerInterface $logger
     */
    public function __construct(
        BraintreeAdapter $braintreeAdapter,
        LoggerInterface $logger
    ) {
        $this->braintreeAdapter = $braintreeAdapter;
        $this->logger = $logger;
    }

    /**
     * Delete the payment method token from the Braintree before deleting it from Magento
     *
     * @param PaymentTokenRepositoryInterface $subject
     * @param PaymentTokenInterface $paymentToken
     * @return null
     */
    public function beforeDelete(
        PaymentTokenRepositoryInterface $subject,
        PaymentTokenInterface $paymentToken
    ) {
        try {
            if (!in_array($paymentToken->getPaymentMethodCode(), self::BT_PAYMENT_METHOD_CODES)) {
                return null;
            }

            $token = $paymentToken->getGatewayToken();
            $this->braintreeAdapter->deletePaymentMethod($token);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return null;
    }
}
