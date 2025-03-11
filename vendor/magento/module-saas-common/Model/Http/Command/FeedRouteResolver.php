<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
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
 * ************************************************************************
 */

declare(strict_types=1);

namespace Magento\SaaSCommon\Model\Http\Command;

use Magento\SaaSCommon\Model\Exception\UnableSendData;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\ServicesId\Model\ServicesConfigInterface;

class FeedRouteResolver implements FeedRouteResolverInterface
{
    private const ROUTE_CONFIG_PATH = 'commerce_data_export/routes/';

    /**
     * @var ScopeConfigInterface $config
     */
    private ScopeConfigInterface $config;

    /**
     * @var ServicesConfigInterface $servicesConfig
     */
    private ServicesConfigInterface $servicesConfig;

    /**
     * @param ScopeConfigInterface $config
     * @param ServicesConfigInterface $servicesConfig
     */
    public function __construct(
        ScopeConfigInterface $config,
        ServicesConfigInterface $servicesConfig
    ) {
        $this->config = $config;
        $this->servicesConfig = $servicesConfig;
    }

    /**
     * @inheritDoc
     *
     * @throws UnableSendData
     */
    public function getRoute(string $feedName): string
    {
        $route = $this->config->getValue(self::ROUTE_CONFIG_PATH . $feedName);
        $environmentId = $this->servicesConfig->getEnvironmentId();
        if (empty($route) || empty($environmentId)) {
            throw new UnableSendData(
                sprintf(
                    'Cannot build feed url with: route: %s and environmentId: %s',
                    $route,
                    $environmentId
                )
            );
        }

        return '/' . $route . '/' . $environmentId;
    }
}
