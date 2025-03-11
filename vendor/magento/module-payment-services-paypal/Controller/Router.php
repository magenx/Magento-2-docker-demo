<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Controller;

use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Route\ConfigInterface;
use Magento\Framework\App\Router\ActionList;
use Magento\Framework\App\RouterInterface;

/**
 * Matches application action in case when .well-known route was requested
 */
class Router implements RouterInterface
{
    private const IDENTIFIER = '.well-known/apple-developer-merchantid-domain-association';
    private const ACTION = 'domainassociation';

    /**
     * @var ActionFactory
     */
    private $actionFactory;

    /**
     * @var ActionList
     */
    private $actionList;

    /**
     * @var ConfigInterface
     */
    private $routeConfig;

    /**
     * @param ActionFactory $actionFactory
     * @param ActionList $actionList
     * @param ConfigInterface $routeConfig
     */
    public function __construct(
        ActionFactory $actionFactory,
        ActionList $actionList,
        ConfigInterface $routeConfig
    ) {
        $this->actionFactory = $actionFactory;
        $this->actionList = $actionList;
        $this->routeConfig = $routeConfig;
    }

    /**
     * Match domain-association route
     *
     * @param RequestInterface $request
     * @return ActionInterface|null
     */
    public function match(RequestInterface $request)
    {
        $identifier = trim($request->getPathInfo(), '/');
        if ($identifier !== self::IDENTIFIER) {
            return null;
        }

        $modules = $this->routeConfig->getModulesByFrontName('paymentservicespaypal');
        if (empty($modules)) {
            return null;
        }

        $actionClassName = $this->actionList->get($modules[0], null, 'index', self::ACTION);
        return $this->actionFactory->create($actionClassName);
    }
}
