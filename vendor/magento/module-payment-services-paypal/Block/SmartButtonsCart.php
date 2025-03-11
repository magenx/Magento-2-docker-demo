<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Block;

use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template\Context;
use Magento\PaymentServicesPaypal\Model\Config;

/**
 * @api
 */
class SmartButtonsCart extends SmartButtons
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @param Context $context
     * @param Config $config
     * @param Session $session
     * @param string $pageType
     * @param array $componentConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        Session $session,
        string $pageType = 'minicart',
        array $componentConfig = [],
        array $data = []
    ) {
        $this->session = $session;
        parent::__construct($context, $config, $session, $pageType, $componentConfig, $data);
    }

    /**
     * @inheritDoc
     */
    public function isLocationEnabled(string $location): bool
    {
        return parent::isEnabled() && (bool)(float)$this->session->getQuote()->getGrandTotal()
            && parent::isLocationEnabled($location);
    }
}
