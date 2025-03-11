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

namespace Magento\PaymentServicesSaaSExport\Model\Http\Command;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\PaymentServicesBase\Model\ServiceRouteResolverInterface;
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
     * @var ServiceRouteResolverInterface $serviceRouteResolver
     */
    private ServiceRouteResolverInterface $serviceRouteResolver;

    /**
     * @param ScopeConfigInterface $config
     * @param ServicesConfigInterface $servicesConfig
     * @param ServiceRouteResolverInterface $serviceRouteResolver
     */
    public function __construct(
        ScopeConfigInterface $config,
        ServicesConfigInterface $servicesConfig,
        ServiceRouteResolverInterface $serviceRouteResolver
    ) {
        $this->config = $config;
        $this->servicesConfig = $servicesConfig;
        $this->serviceRouteResolver = $serviceRouteResolver;
    }

    /**
     * @inheritDoc
     */
    public function getRoute(string $feedName): string
    {
        $feedRoute = $this->config->getValue(self::ROUTE_CONFIG_PATH . $feedName);
        $environmentId = $this->servicesConfig->getEnvironmentId();
        return $this->serviceRouteResolver->resolve($feedRoute . '/' . $environmentId);
    }
}
