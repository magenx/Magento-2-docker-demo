<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\Adapter\PaymentMethod;

use Braintree\PayPalAccount;
use DateInterval;
use DateTimeZone;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use PayPal\Braintree\Gateway\Request\PaymentDataBuilder;
use PayPal\Braintree\Model\Ui\PayPal\ConfigProvider;

class BraintreePayPalPaymentTokenAdapter implements PaymentTokenAdapterInterface
{
    /**
     * @var PayPalAccount
     */
    private PayPalAccount $paymentMethod;

    /**
     * @var DateTimeFactory
     */
    private DateTimeFactory $dateTimeFactory;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @param PayPalAccount $paymentMethod
     * @param DateTimeFactory $dateTimeFactory
     * @param SerializerInterface $serializer
     */
    public function __construct(
        PayPalAccount $paymentMethod,
        DateTimeFactory $dateTimeFactory,
        SerializerInterface $serializer
    ) {
        $this->paymentMethod = $paymentMethod;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->serializer = $serializer;
    }

    /**
     * Get the payment method code.
     *
     * @return string
     */
    public function getPaymentMethodCode(): string
    {
        return ConfigProvider::PAYPAL_CODE;
    }

    /**
     * Get Payment token type.
     *
     * @return string
     */
    public function getType(): string
    {
        return PaymentTokenFactoryInterface::TOKEN_TYPE_ACCOUNT;
    }

    /**
     * Get Gateway token.
     *
     * @return string
     */
    public function getGatewayToken(): string
    {
        return $this->paymentMethod->token;
    }

    /**
     * Get the token expiration date.
     *
     * Default to 1 year.
     *
     * @return string
     */
    public function getExpiresAt(): string
    {
        $expiryDate = $this->dateTimeFactory->create('now', new DateTimeZone('UTC'));
        $expiryDate->add(new DateInterval('P1Y'));

        return $expiryDate->format('Y-m-d 00:00:00');
    }

    /**
     * Get the token details.
     *
     * @return string
     */
    public function getTokenDetails(): string
    {
        return $this->serializer->serialize([
            PaymentDataBuilder::CUSTOMER_ID => $this->paymentMethod->customerId,
            'payerEmail' => $this->paymentMethod->email,
            'billingAgreementId' => $this->paymentMethod->billingAgreementId
        ]);
    }
}
