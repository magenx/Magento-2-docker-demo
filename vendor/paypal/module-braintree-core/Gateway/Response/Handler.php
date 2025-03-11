<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Gateway\Response;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use PayPal\Braintree\Gateway\Config\Config;
use PayPal\Braintree\Gateway\Helper\SubjectReader;
use RuntimeException;

class Handler
{
    /**
     * @var PaymentTokenFactoryInterface
     */
    public PaymentTokenFactoryInterface $paymentTokenFactory;

    /**
     * @var OrderPaymentExtensionInterfaceFactory
     */
    public OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory;

    /**
     * @var SubjectReader
     */
    public SubjectReader $subjectReader;

    /**
     * @var Config
     */
    public Config $config;

    /**
     * @var Json
     */
    public mixed $serializer;

    /**
     * @var DateTimeFactory
     */
    public DateTimeFactory $dateTimeFactory;

    /**
     * VaultDetailsHandler constructor.
     *
     * @param PaymentTokenFactoryInterface $paymentTokenFactory
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     * @param Config $config
     * @param SubjectReader $subjectReader
     * @param DateTimeFactory $dateTime
     * @param Json|null $serializer
     */
    public function __construct(
        PaymentTokenFactoryInterface $paymentTokenFactory,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        Config $config,
        SubjectReader $subjectReader,
        DateTimeFactory $dateTime,
        Json $serializer = null
    ) {
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->config = $config;
        $this->subjectReader = $subjectReader;
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(Json::class);
        $this->dateTimeFactory = $dateTime;
    }

    /**
     * Convert payment token details to JSON
     *
     * @param array $details
     * @return string
     */
    public function convertDetailsToJSON(array $details): string
    {
        $json = $this->serializer->serialize($details);
        return $json ?: '{}';
    }

    /**
     * Get payment extension attributes
     *
     * @param InfoInterface $payment
     * @return OrderPaymentExtensionInterface
     */
    public function getExtensionAttributes(InfoInterface $payment): OrderPaymentExtensionInterface
    {
        $extensionAttributes = $payment->getExtensionAttributes();
        if (null === $extensionAttributes) {
            $extensionAttributes = $this->paymentExtensionFactory->create();
            $payment->setExtensionAttributes($extensionAttributes);
        }
        return $extensionAttributes;
    }
}
