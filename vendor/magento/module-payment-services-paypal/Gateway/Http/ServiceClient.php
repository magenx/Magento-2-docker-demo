<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Gateway\Http;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\PaymentServicesBase\Model\ServiceClientInterface;
use Magento\Payment\Model\Method\Logger;

class ServiceClient implements ClientInterface
{
    /**
     * @var ServiceClientInterface
     */
    private $httpClient;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param ServiceClientInterface $httpClient
     * @param Logger $logger
     */
    public function __construct(
        ServiceClientInterface $httpClient,
        Logger $logger
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    /**
     * Places request to gateway. Returns result as ENV array
     *
     * @param TransferInterface $transferObject
     * @return array
     * @throws \Magento\Payment\Gateway\Http\ClientException
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $environment = $transferObject->getClientConfig() ? $transferObject->getClientConfig()['environment'] : '';
        $response = $this->httpClient->request(
            $transferObject->getHeaders(),
            $transferObject->getUri(),
            $transferObject->getMethod(),
            $transferObject->getBody() == null ? '' : json_encode($transferObject->getBody()),
            'json',
            $environment
        );

        $this->logger->debug(
            [
                'request' => [
                    $transferObject->getUri(),
                    $transferObject->getHeaders(),
                    $transferObject->getMethod(),
                    $transferObject->getBody()
                ],
                'response' => $response
            ]
        );

        return $response;
    }
}
