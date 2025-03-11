<?php

/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2023 Adobe
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

namespace Magento\PaymentServicesPaypal\Model\Api;

use Magento\PaymentServicesPaypal\Api\PaymentSdkManagementInterface;
use Magento\PaymentServicesPaypal\Api\PaymentSdkRequestInterface;
use Magento\PaymentServicesPaypal\Api\Data\PaymentSdkParamsInterfaceFactory;
use Magento\PaymentServicesPaypal\Api\Data\PaymentConfigSdkParamsInterfaceFactory;

/**
 * Implementation for the REST WebAPI request to get payment sdk urls
 *
 * @api
 */
class PaymentSdkRequest implements PaymentSdkRequestInterface
{
    /**
     * @var PaymentSdkManagementInterface
     */
    private $paymentSdkManagement;

    /**
     * @var PaymentSdkParamsInterfaceFactory
     */
    private $paymentSdkParamsFactory;

    /**
     * @var PaymentConfigSdkParamsInterfaceFactory
     */
    private $paymentConfigSdkParamsFactory;

    /**
     * @param PaymentSdkManagementInterface $paymentSdkManagement
     * @param PaymentSdkParamsInterfaceFactory $paymentSdkParamsFactory
     * @param PaymentConfigSdkParamsInterfaceFactory $paymentConfigSdkParamsFactory
     */
    public function __construct(
        PaymentSdkManagementInterface $paymentSdkManagement,
        PaymentSdkParamsInterfaceFactory $paymentSdkParamsFactory,
        PaymentConfigSdkParamsInterfaceFactory $paymentConfigSdkParamsFactory
    ) {
        $this->paymentSdkManagement = $paymentSdkManagement;
        $this->paymentSdkParamsFactory = $paymentSdkParamsFactory;
        $this->paymentConfigSdkParamsFactory = $paymentConfigSdkParamsFactory;
    }

    /**
     * @inheritDoc
     */
    public function getByLocation(string $location)
    {
        return $this->get($location);
    }

    /**
     * @inheritDoc
     */
    public function getByLocationAndMethodCode(string $location, string $methodCode)
    {
        return $this->get($location, $methodCode)[0];
    }

    /**
     * Get the sdk params from cache or service and move them into appropriate objects to be returned
     *
     * @param string $location
     * @param string|null $methodCode
     * @return \Magento\PaymentServicesPaypal\Api\Data\PaymentSdkParamsInterface[]
     */
    private function get(string $location, ?string $methodCode = null)
    {
        // Get sdk params from cache or service
        $sdkManagementParams = $this->paymentSdkManagement->getParams($location, null, $methodCode);

        // Move the array of sdk params into appropriate objects to be returned
        $sdkParams = [];
        foreach ($sdkManagementParams as $sdkManagementParam) {
            $sdkParam = $this->paymentSdkParamsFactory->create();
            $sdkParam->setCode($sdkManagementParam['code']);
            $params = [];
            foreach ($sdkManagementParam['params'] as $param) {
                $params[] = $this->paymentConfigSdkParamsFactory->create()
                    ->setName($param['name'])
                    ->setValue($param['value']);
            }
            $sdkParam->setParams($params);
            $sdkParams[] = $sdkParam;
        }

        return $sdkParams;
    }
}
