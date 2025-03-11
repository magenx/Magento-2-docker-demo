<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace PayPal\Braintree\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Helper\Formatter;
use PayPal\Braintree\Gateway\Config\Config;
use PayPal\Braintree\Gateway\Helper\SubjectReader;

class VaultCaptureDataBuilder implements BuilderInterface
{
    use Formatter;

    /**
     * @var SubjectReader
     */
    private SubjectReader $subjectReader;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * Constructor
     *
     * @param SubjectReader $subjectReader
     * @param Config $config
     */
    public function __construct(
        SubjectReader $subjectReader,
        Config $config
    ) {
        $this->subjectReader = $subjectReader;
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject): array
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();
        $extensionAttributes = $payment->getExtensionAttributes();
        $paymentToken = $extensionAttributes->getVaultPaymentToken();

        if (is_null($paymentToken)) {
            $paymentGatewayToken = false;
        } else {
            $paymentGatewayToken = $paymentToken->getGatewayToken();
        }

        return [
            'amount' => $this->formatPrice($this->subjectReader->readAmount($buildSubject)),
            'paymentMethodToken' => $paymentGatewayToken,
            PaymentDataBuilder::MERCHANT_ACCOUNT_ID => $this->config
                ->getMerchantAccountId($paymentDO->getOrder()->getStoreId())
        ];
    }
}
