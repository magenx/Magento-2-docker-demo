<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ServicesId\Model;

/**
 * Data provider for Magento services registry data
 */
class MerchantRegistryProvider
{
    /**
     * @var ServicesConfigInterface
     */
    private $servicesConfig;

    /**
     * @var ServicesClientInterface
     */
    private $servicesClient;

    /**
     * @var array
     */
    private $merchantRegistry;

    /**
     * @param ServicesConfigInterface $servicesConfig
     * @param ServicesClientInterface $servicesClient
     */
    public function __construct(
        ServicesConfigInterface $servicesConfig,
        ServicesClientInterface $servicesClient
    ) {
        $this->servicesConfig = $servicesConfig;
        $this->servicesClient = $servicesClient;
    }

    /**
     * Call API for registry data
     *
     * @return array|null
     */
    public function getMerchantRegistry() : ?array
    {
        if ($this->merchantRegistry === null && $this->servicesConfig->isApiKeySet()) {
            $uri = $this->servicesConfig->getRegistryApiUrl('registry');
            $response = $this->servicesClient->request('GET', $uri);
            $data = [];

            if (isset($response['message'])) {
                $data['error'] = true;
                $data['message'] = (string) $response['message'];
            } else {
                $data = $response['results'] ?? [];
            }

            $this->merchantRegistry = empty($data) ? [] : $data;
        }

        return $this->merchantRegistry;
    }
}
