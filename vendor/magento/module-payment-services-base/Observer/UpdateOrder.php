<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Magento\PaymentServicesBase\Observer;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\PaymentServicesBase\Model\Config;
use Magento\Sales\Model\Order;
use Magento\PaymentServicesBase\Model\ServiceClientInterface;

class UpdateOrder extends AbstractDataAssignObserver
{
    /**
     * @var ServiceClientInterface
     */
    private $httpClient;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var array
     */
    private $methods;

    /**
     * @param ServiceClientInterface $httpClient
     * @param Config $config
     * @param array $methods
     */
    public function __construct(
        ServiceClientInterface $httpClient,
        Config $config,
        $methods = []
    ) {
        $this->httpClient = $httpClient;
        $this->config = $config;
        $this->methods = $methods;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        /* @var $order Order */
        $order = $observer->getEvent()->getOrder();
        if (!in_array($order->getPayment()->getMethod(), $this->methods)) {
            return $this;
        }

        $internalOrderId = $order->getPayment()->getAdditionalInformation('payments_order_id');
        $websiteId = $order->getStore()->getWebsiteId();
        $order = [
            'order-id' => $order->getId(),
            'order-increment-id' => $order->getIncrementId()
        ];

        $this->httpClient->request(
            [
                'Content-Type' => 'application/json',
                'x-scope-id' => $websiteId
            ],
            '/payment/order/' . $internalOrderId,
            Http::METHOD_PATCH,
            json_encode($order)
        );

        return $this;
    }
}
