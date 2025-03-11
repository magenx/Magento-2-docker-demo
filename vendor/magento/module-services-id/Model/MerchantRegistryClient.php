<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ServicesId\Model;

use Psr\Log\LoggerInterface;

/**
 * Client class for updating Registry Service record
 */
class MerchantRegistryClient
{
    /**
     * @var ServicesClientInterface
     */
    private $servicesClient;

    /**
     * @var ServicesConfigInterface
     */
    private $servicesConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ServicesClientInterface $servicesClient
     * @param ServicesConfigInterface $servicesConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        ServicesClientInterface $servicesClient,
        ServicesConfigInterface $servicesConfig,
        LoggerInterface $logger
    ) {
        $this->servicesClient = $servicesClient;
        $this->servicesConfig = $servicesConfig;
        $this->logger = $logger;
    }

    /**
     * Call registry API to update record
     *
     * @param string $environmentId
     * @param string $cloudId
     * @return void
     */
    public function setCloudId(string $environmentId, string $cloudId): void
    {
        if ($this->servicesConfig->isApiKeySet() && !empty($environmentId)) {
            try {
                $path = sprintf('registry/environments/%s/cloudid/%s', $environmentId, $cloudId);
                $url = $this->servicesConfig->getRegistryApiUrl($path);
                $response = $this->servicesClient->request('PUT', $url);
                if ($response
                    && !empty($response['status'])
                    && $response['status'] != 200
                    && !empty($response['message'])) {
                    $this->logger->error(
                        'Unable to set cloud id to merchant registry.',
                        ['error' => $response['message']]
                    );
                }
            } catch (\Exception $exception) {
                $this->logger->error('Unable to set cloud id to merchant registry.', ['error' => $exception]);
            }
        }
    }
}
