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
use Magento\Catalog\Block\ShortcutInterface;
use Magento\Framework\View\Element\Template;

/**
 * @api
 */
class Message extends Template implements ShortcutInterface
{
    private const CART_TYPE = 'cart';
    /**
     * @var Config
     */
    private $config;

    /**
     * @var array
     */
    private $componentConfig;

    /**
     * @var string
     */
    private $pageType;

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
        $this->config = $config;
        $this->componentConfig = $componentConfig;
        $this->pageType = $pageType;
        $this->session = $session;
        parent::__construct($context, $data);
        /** @phpstan-ignore-next-line */
        $this->setTemplate($data['template'] ?? $componentConfig[$this->pageType]['template']);
    }

    /**
     * @inheritDoc
     */
    public function getAlias() : string
    {
        return 'magpaypayments_message';
    }

    /**
     * Get component parameters.
     *
     * @return array[]
     */
    public function getComponentParams() : array
    {
        return [
            'styles' => $this->getStyles(),
            'placement' => $this->componentConfig[$this->pageType]['placement'] ?? '',
            'renderContainer' => $this->componentConfig[$this->pageType]['renderContainer'] ?? ''
        ];
    }

    /**
     * Check if quote amount > 0 & message & smart buttons display for the particular location are enabled
     *
     * @param string $location
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isEnabled(string $location) : bool
    {
        $isAllowed = true;

        if ($this->pageType === self::CART_TYPE) {
            $isAllowed = (bool)(int)$this->session->getQuote()->getGrandTotal();
        }
        return $isAllowed && $this->config->isEnabled() && $this->config->canDisplayPayLaterMessage()
            && $this->config->isLocationEnabled($location);
    }

    /**
     * Get message styles.
     *
     * @return array
     */
    private function getStyles() : array
    {
        return $this->componentConfig[$this->pageType]['styles'];
    }
}
