<?php
/************************************************************************
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

namespace Magento\PaymentServicesPaypal\Block\Customer\Vault;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template\Context;
use Magento\PaymentServicesPaypal\Model\Config;
use Magento\Store\Model\StoreManagerInterface;

/**
 * @api
 */
class AddCardButton extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @param Context $context
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        StoreManagerInterface $storeManager,
        array $data = [],
    ) {
        parent::__construct($context, $data);

        $this->config = $config;
        $this->storeManager = $storeManager;
    }

    /**
     * Return the URL for adding a card
     *
     * @return string
     */
    public function getAddCardUrl(): string
    {
        return $this->getUrl('paymentservicespaypal/vault/addcard');
    }

    /**
     * Check if the button is enabled
     *
     * @return bool
     */
    public function isButtonEnabled(): bool
    {
        try {
            $storeId = (int)$this->storeManager->getStore()->getId();
            return $this->config->isVaultEnabled($storeId);
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }
}
