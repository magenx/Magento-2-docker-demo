<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesDashboard\Block\Adminhtml;

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\PaymentServicesBase\Model\Config;
use Magento\PaymentServicesPaypal\Model\Config as PaymentsConfig;

/**
 * @api
 */
class Index extends Template
{
    /**
     * Config path used for frontend url
     */
    private const FRONTEND_URL_PATH = 'payment_services_dashboard/frontend_url';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var PaymentsConfig
     */
    private $paymentsConfig;

    /**
     * @var Session
     */
    private $adminSession;

    /**
     * @param Context $context
     * @param Config $config
     * @param TimezoneInterface $timezone
     * @param PaymentsConfig $paymentsConfig
     * @param Session $adminSession
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        TimezoneInterface $timezone,
        PaymentsConfig $paymentsConfig,
        Session $adminSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->timezone = $timezone;
        $this->paymentsConfig = $paymentsConfig;
        $this->adminSession = $adminSession;
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
     * Return a JSON map of endpoints path
     *
     * @return string
     */
    public function getConfigJson() : string
    {
        $config = [
            'endpoints' => [
                'config' => $this->getUrl('services/config/index'),
                'websites' => $this->getUrl('services/config/websites'),
                'servicesProxy'=> $this->getUrl('services/service/proxy'),
                'genericRedirect' => $this->getUrl('services/url/redirect')
            ],
            'configurationStatus' => [
                'magentoServicesConfigured' => [
                    'production' => $this->config->isMagentoServicesConfigured('production'),
                    'sandbox' => $this->config->isMagentoServicesConfigured('sandbox'),
                ],
                'paymentEnvironmentType' => $this->config->getEnvironmentTypeAcrossWebsites()
            ],
            'userDetails' => [
                'locale' => $this->adminSession->getUser()->getInterfaceLocale()
            ],
            'extensionVersion' => $this->config->getVersion()
        ];
        return json_encode($config);
    }
}
