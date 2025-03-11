<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Block\Customer\Ach;

use PayPal\Braintree\Gateway\Config\Ach\Config;
use Magento\Framework\View\Element\Template;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Block\AbstractTokenRenderer;
use PayPal\Braintree\Model\Ach\Ui\ConfigProvider;

/**
 * @api
 * @since 100.0.2
 */
class VaultTokenRenderer extends AbstractTokenRenderer
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * Initialize dependencies.
     *
     * @param Template\Context $context
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function getIconUrl()
    {
        return $this->config->getAchIcon()['url'];
    }

    /**
     * @inheritdoc
     */
    public function getIconHeight()
    {
        return $this->config->getAchIcon()['height'];
    }

    /**
     * @inheritdoc
     */
    public function getIconWidth()
    {
        return $this->config->getAchIcon()['width'];
    }

    /**
     * Can render specified token
     *
     * @param PaymentTokenInterface $token
     * @return boolean
     */
    public function canRender(PaymentTokenInterface $token): bool
    {
        return $token->getPaymentMethodCode() === ConfigProvider::METHOD_CODE;
    }

    /**
     * Get the last 4 digits of the bank account number.
     *
     * @return string
     */
    public function getAccountNumberLastFourDigits(): string
    {
        return $this->getTokenDetails()['last4'] ?? '';
    }

    /**
     * Get the banks routing number
     *
     * @return string
     */
    public function getBankRoutingNumber(): string
    {
        return $this->getTokenDetails()['routingNumber'] ?? '';
    }
}
