<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Block\Customer\Venmo;

use PayPal\Braintree\Gateway\Config\Venmo\Config;
use Magento\Framework\View\Element\Template;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Block\AbstractTokenRenderer;
use PayPal\Braintree\Model\Venmo\Ui\ConfigProvider;

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
        return $this->config->getVenmoIcon()['url'];
    }

    /**
     * @inheritdoc
     */
    public function getIconHeight()
    {
        return $this->config->getVenmoIcon()['height'];
    }

    /**
     * @inheritdoc
     */
    public function getIconWidth()
    {
        return $this->config->getVenmoIcon()['width'];
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
     * Get username of Venmo payer
     *
     * @return string
     */
    public function getVenmoUsername(): string
    {
        return $this->getTokenDetails()['username'];
    }
}
