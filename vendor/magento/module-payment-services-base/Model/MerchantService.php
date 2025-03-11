<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesBase\Model;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Request\Http;

class MerchantService
{
    public const ADMIN_MERCHANT_URL = '/admin/merchant';

    /**
     * @var ServiceClientInterface
     */
    private $serviceClient;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var TypeListInterface
     */
    private $cacheTypeList;

    /**
     * @param Config $config
     * @param ServiceClientInterface $serviceClient
     * @param WriterInterface $configWriter
     * @param TypeListInterface $cacheTypeList
     */
    public function __construct(
        Config $config,
        ServiceClientInterface $serviceClient,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList
    ) {
        $this->config = $config;
        $this->serviceClient = $serviceClient;
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
    }

    /**
     * Validates environment, sends delete merchant request to configuration service and resets values in config
     *
     * @param string $environment
     * @return array
     */
    public function delete(string $environment)
    {
        $response = ['is_successful' => false];

        if ($environment != 'sandbox' && $environment != 'production') {
            return $response;
        }

        $merchantId = $this->config->getMerchantId($environment);

        $response =  $this->serviceClient->request(
            ['Content-Type' => 'application/json',
                'x-mp-merchant-id' => $merchantId],
            self::ADMIN_MERCHANT_URL,
            Http::METHOD_DELETE,
            '',
            'json',
            $environment,
        );

        if (isset($response['is_successful']) && $response['is_successful']) {
            $this->updateConfigValueInStorage($environment);
        }

        return $response;
    }

    /**
     * Updates merchant ids values in commerce configuration
     *
     * @param string $environment
     * @return void
     */
    private function updateConfigValueInStorage(string $environment)
    {
        if ($environment == 'sandbox') {
            $this->configWriter->save('payment/payment_methods/sandbox_merchant_id', '');
        } elseif ($environment == 'production') {
            $this->configWriter->save('payment/payment_methods/production_merchant_id', '');
        }
        $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
    }
}
