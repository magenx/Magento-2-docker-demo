<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Magento\PaymentServicesPaypal\Model;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Exception;
use Psr\Log\LoggerInterface;

class CustomerHeadersBuilder
{
    private const HASH_ALGORITHM = 'sha256';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Return a group of HTTP headers that help identify customer information
     *
     * @param PaymentDataObjectInterface $payment
     * @return array
     */
    public function buildCustomerHeaders(PaymentDataObjectInterface $payment): array
    {
        $headers = [
            'x-commerce-customer-email-hash' => hash(
                self::HASH_ALGORITHM,
                $payment->getOrder()->getBillingAddress()->getEmail()
            ),
            'x-commerce-customer-composite-info-hash' =>
                $this->buildCompositeCustomerHeader($payment)
        ];
        $customerId = $payment->getOrder()->getCustomerId();
        if ($customerId) {
            $headers['x-commerce-customer-id'] = $customerId;
        }
        return $headers;
    }

    /**
     * Build the HTTP header that contains hashed values of cart ID, customer billing and shipping address
     *
     * @param PaymentDataObjectInterface $payment
     * @return string
     */
    private function buildCompositeCustomerHeader(PaymentDataObjectInterface $payment): string
    {
        $billingAddress = $payment->getOrder()->getBillingAddress();
        $shippingAddress = $payment->getOrder()->getShippingAddress();
        $hashedBillingAddress = $billingAddress ? hash(
            self::HASH_ALGORITHM,
            $billingAddress->getStreetLine1()
            . $billingAddress->getStreetLine2()
            . $billingAddress->getCity()
            . $billingAddress->getRegionCode()
            . $billingAddress->getPostcode()
        ): '';
        $hashedShippingAddress = $shippingAddress ? hash(
            self::HASH_ALGORITHM,
            $shippingAddress->getStreetLine1()
            . $shippingAddress->getStreetLine2()
            . $shippingAddress->getCity()
            . $shippingAddress->getRegionCode()
            . $shippingAddress->getPostcode()
        ) : '';
        $compositeCustomerInfo = [
            'b' => $hashedBillingAddress,
            's' => $hashedShippingAddress
        ];
        try {
            $hashedCompositeHeaders = json_encode($compositeCustomerInfo, JSON_THROW_ON_ERROR);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return '';
        }
        return $hashedCompositeHeaders;
    }
}
