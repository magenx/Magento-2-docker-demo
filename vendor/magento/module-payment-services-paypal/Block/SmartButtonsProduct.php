<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Magento\PaymentServicesPaypal\Block;

use Magento\Catalog\Helper\Data;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template\Context;
use Magento\PaymentServicesPaypal\Model\Config;

/**
 * @api
 */
class SmartButtonsProduct extends SmartButtons
{
    /**
     * @var string
     */
    private $pageType;

    /**
     * @var Data
     */
    private $catalogData;

    /**
     * @param Context $context
     * @param Config $config
     * @param Session $session
     * @param Data $catalogData
     * @param string $pageType
     * @param array $componentConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        Session $session,
        Data $catalogData,
        string $pageType = 'minicart',
        array $componentConfig = [],
        array $data = []
    ) {
        $this->pageType = $pageType;
        $this->catalogData = $catalogData;
        parent::__construct(
            $context,
            $config,
            $session,
            $pageType,
            $componentConfig,
            $data
        );
    }

    /**
     * Get the component params of Smart Buttons
     *
     * @return array[]
     */
    public function getComponentParams() : array
    {
        return array_merge(
            parent::getComponentParams(),
            [
                // phpcs:disable Magento2.Files.LineLength, Generic.Files.LineLength
                'createOrderUrl' => $this->getUrl('paymentservicespaypal/smartbuttons/createpaypalorder', ['location' => $this->pageType]),
                'cancelUrl' => $this->getUrl('paymentservicespaypal/smartbuttons/cancel'),
                'addToCartUrl' => $this->getUrl('paymentservicespaypal/smartbuttons/addtocart'),
                'isVirtual' => $this->catalogData->getProduct() !== null && $this->catalogData->getProduct()->isVirtual()
            ]
        );
    }
}
