<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Gateway\Request;

use Magento\Framework\App\Request\Http;
use Magento\PaymentServicesPaypal\Gateway\Response\TxnIdHandler;
use Magento\PaymentServicesPaypal\Model\Config;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Quote\Model\Quote\Payment as QuotePayment;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class VoidRequest implements BuilderInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Config $config,
        StoreManagerInterface $storeManager
    ) {
        $this->config = $config;
        $this->storeManager = $storeManager;
    }

    /**
     * Build void request
     *
     * @param array $buildSubject
     * @return array
     * @throws NoSuchEntityException
     */
    public function build(array $buildSubject)
    {
        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = SubjectReader::readPayment($buildSubject);

        /** @var Payment|QuotePayment $payment */
        $payment = $paymentDO->getPayment();
        $paymentsMode = $payment->getAdditionalInformation('payments_mode');
        //Using $payment->getAuthorizationTransaction()->getTxnId() for old orders
        $auth =
            $payment->getAdditionalInformation(TxnIdHandler::AUTH_ID_KEY)
            ?? $payment->getAuthorizationTransaction()?->getTxnId();
        $uri = '/'
            . $this->config->getMerchantId($paymentsMode)
            . '/payment/'
            . $auth
            . '/void';

        if ($payment instanceof Payment) {
            $storeId = $payment->getOrder()->getStoreId();
        } else {
            $storeId = $payment->getQuote()->getStoreId();
        }
        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();

        return [
            'uri' => $uri,
            'method' => Http::METHOD_POST,
            'body' => [],
            'headers' => [
                'Content-Type' => 'application/json',
                'x-scope-id' => $websiteId
            ],
            'clientConfig' => [
                'environment' => $paymentsMode
            ]
        ];
    }
}
