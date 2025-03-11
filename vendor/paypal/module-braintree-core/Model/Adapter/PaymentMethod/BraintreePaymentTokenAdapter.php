<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\Adapter\PaymentMethod;

use Braintree\CreditCard;
use DateInterval;
use DateTimeZone;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use PayPal\Braintree\Gateway\Config\Config;
use PayPal\Braintree\Model\Ui\ConfigProvider;

class BraintreePaymentTokenAdapter implements PaymentTokenAdapterInterface
{
    /**
     * @var CreditCard
     */
    private CreditCard $paymentMethod;

    /**
     * @var DateTimeFactory
     */
    private DateTimeFactory $dateTimeFactory;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @param CreditCard $paymentMethod
     * @param DateTimeFactory $dateTimeFactory
     * @param SerializerInterface $serializer
     * @param Config $config
     */
    public function __construct(
        CreditCard $paymentMethod,
        DateTimeFactory $dateTimeFactory,
        SerializerInterface $serializer,
        Config $config
    ) {
        $this->paymentMethod = $paymentMethod;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->serializer = $serializer;
        $this->config = $config;
    }

    /**
     * Get the payment method code.
     *
     * @return string
     */
    public function getPaymentMethodCode(): string
    {
        return ConfigProvider::CODE;
    }

    /**
     * Get Payment token type.
     *
     * @return string
     */
    public function getType(): string
    {
        return PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD;
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
     * @return string
     */
    public function getExpiresAt(): string
    {
        $expiryDate = $this->dateTimeFactory->create(
            sprintf(
                '%s-%s-01 00:00:00',
                $this->paymentMethod->expirationYear,
                $this->paymentMethod->expirationMonth
            ),
            new DateTimeZone('UTC')
        );

        $expiryDate->add(new DateInterval('P1M'));

        return $expiryDate->format('Y-m-d 00:00:00');
    }

    /**
     * Get the token details.
     *
     * @return string
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getTokenDetails(): string
    {
        return $this->serializer->serialize([
            'customerId' => $this->paymentMethod->customerId,
            'type' => $this->getCreditCardType($this->paymentMethod->cardType),
            'maskedCC' => $this->paymentMethod->last4,
            'expirationDate' => $this->paymentMethod->expirationDate
        ]);
    }

    /**
     * Get type of credit card mapped from Braintree.
     *
     * @param string $type
     * @return string
     * @throws InputException
     * @throws NoSuchEntityException
     */
    private function getCreditCardType(string $type): string
    {
        $replaced = str_replace(' ', '-', strtolower($type));
        $mapper = $this->config->getCcTypesMapper();

        return $mapper[$replaced];
    }
}
