<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Gateway\Request\GooglePay;

use PayPal\Braintree\Gateway\Config\Config;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use PayPal\Braintree\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Helper\Formatter;
use PayPal\Braintree\Model\GooglePay\Ui\ConfigProvider;
use PayPal\Braintree\Observer\GooglePay\DataAssignObserver;

class ThreeDSecureDataBuilder implements BuilderInterface
{
    use Formatter;

    /**
     * @var Config
     */
    protected Config $config;

    /**
     * @var SubjectReader
     */
    protected SubjectReader $subjectReader;

    /**
     * Constructor
     *
     * @param Config $config
     * @param SubjectReader $subjectReader
     */
    public function __construct(Config $config, SubjectReader $subjectReader)
    {
        $this->config = $config;
        $this->subjectReader = $subjectReader;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function build(array $buildSubject): array
    {
        $result = [];

        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $amount = $this->formatPrice($this->subjectReader->readAmount($buildSubject));

        $payment = $paymentDO->getPayment();

        // This 3D Secure data builder should only be used only for GooglePay, GooglePay vault does not required 3DS.
        if ($payment->getMethod() !== ConfigProvider::METHOD_CODE) {
            return $result;
        }

        // 3D Secure is required only for GooglePay Non-Network Tokenized cards.
        if ($payment->getAdditionalInformation(DataAssignObserver::IS_CARD_NETWORK_TOKENIZED) !== false) {
            return $result;
        }

        if ($this->is3DSecureEnabled($paymentDO->getOrder(), $amount)) {
            $result['options']['threeDSecure'] = ['required' => true]; // 'three_d_secure' was removed in version 4.x.x
        }

        return $result;
    }

    /**
     * Check if 3d secure is enabled
     *
     * @param OrderAdapterInterface $order
     * @param float $amount
     * @return bool
     * @throws InputException
     * @throws NoSuchEntityException
     */
    private function is3DSecureEnabled(OrderAdapterInterface $order, $amount): bool
    {
        if (!$this->config->isVerify3DSecure() || $amount < $this->config->getThresholdAmount()) {
            return false;
        }

        $billingAddress = $order->getBillingAddress();
        $specificCounties = $this->config->get3DSecureSpecificCountries();

        return !(!empty($specificCounties) && !in_array($billingAddress->getCountryId(), $specificCounties, true));
    }
}
