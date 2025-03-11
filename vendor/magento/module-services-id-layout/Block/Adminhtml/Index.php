<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ServicesIdLayout\Block\Adminhtml;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\GraphQlServer\Model\UrlProvider;
use Magento\ServicesId\Model\ServicesConfig;
use Magento\Store\Model\ScopeInterface;

/**
 * Block for services-id front end loader
 *
 * @api
 */
class Index extends Template
{
    /**
     * Config Paths
     * @var string
     */
    private const FRONTEND_URL_PATH = 'services_connector/services_id/frontend_url';
    private const GATEWAY_URL_PATH = 'services_connector/{env}_gateway_url';

    /**
     * @var UrlProvider
     */
    private $graphQlUrlProvider;

    /**
     * @param Context $context
     * @param UrlProvider $graphQlUrl
     */
    public function __construct(
        Context $context,
        UrlProvider $graphQlUrl
    ) {
        $this->graphQlUrlProvider = $graphQlUrl;
        parent::__construct($context);
    }

    /**
     * Returns config for frontend url
     *
     * @return string
     */
    public function getFrontendUrl(): string
    {
        return (string) $this->_scopeConfig->getValue(
            self::FRONTEND_URL_PATH,
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Returns GraphQl BFF url
     *
     * @return string
     */
    public function getGraphQlUrl(): string
    {
        return $this->graphQlUrlProvider->getUrl();
    }

    /**
     * Returns config for gateway url
     *
     * @return string
     */
    public function getGatewayUrl(): string
    {
        $environment =
            $this->_scopeConfig->getValue(ServicesConfig::CONFIG_PATH_SERVICES_CONNECTOR_ENVIRONMENT) ??
            'production'
        ;
        return $this->_scopeConfig->getValue(str_replace(
            '{env}',
            $environment,
            self::GATEWAY_URL_PATH
        ));
    }
}
