<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Gateway\Request;

use Magento\PaymentServicesPaypal\Model\Config;
use Magento\PaymentServicesPaypal\Model\CustomerHeadersBuilder;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class AuthorizationRequest implements BuilderInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var CustomerHeadersBuilder
     */
    private $customerHeaderBuilder;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Config $config
     * @param CustomerHeadersBuilder $customerHeaderBuilder
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Config $config,
        CustomerHeadersBuilder $customerHeaderBuilder,
        StoreManagerInterface $storeManager
    ) {
        $this->config = $config;
        $this->customerHeaderBuilder = $customerHeaderBuilder;
        $this->storeManager = $storeManager;
    }

    /**
     * Build authorization request
     *
     * @param array $buildSubject
     * @return array
     * @throws NoSuchEntityException
     */
    public function build(array $buildSubject)
    {
        /** @var PaymentDataObjectInterface $payment */
        $payment = SubjectReader::readPayment($buildSubject);
        $extensionAttributes = $payment->getPayment()->getExtensionAttributes();
        $paymentToken = $extensionAttributes->getVaultPaymentToken();

        $uri = '/'
            . $this->config->getMerchantId()
            . '/payment/paypal/order/'
            . $this->getPayPalOrderId($payment)
            . '/authorize';

        $websiteId = $this->storeManager->getStore($payment->getOrder()->getStoreId())->getWebsiteId();
        $body = [
            'mp-transaction' => [
                'order-increment-id' => $payment->getOrder()->getOrderIncrementId()
            ]
        ];
        if (isset($paymentToken)) {
            $body['mp-transaction']['payment-vault-id'] = $paymentToken->getGatewayToken();
        }
        $request =  [
            'uri' => $uri,
            'method' => \Magento\Framework\App\Request\Http::METHOD_POST,
            'body' => $body,
            'headers' => [
                'Content-Type' => 'application/json',
                'x-scope-id' => $websiteId
            ]
        ];
        $customHeaders = $this->customerHeaderBuilder->buildCustomerHeaders($payment);
        $request['headers'] = array_merge($request['headers'], $customHeaders);

        return $request;
    }

    private function getPayPalOrderId($payment) {
        $orderId = $payment->getPayment()->getAdditionalInformation('paypal_order_id');

        if (empty($orderId)) {
            throw new NoSuchEntityException(__("Order is missing and can not be authorized. Try again later."));
        }

        return $orderId;
    }
}
