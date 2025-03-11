<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Block\Lpm;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use PayPal\Braintree\Model\Lpm\Config as LpmConfig;
use PayPal\Braintree\Gateway\Config\Config as BraintreeConfig;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use PayPal\Braintree\Model\Adapter\BraintreeAdapter;

/**
 * @api
 * @since 100.1.0
 */
class Fallback extends Template
{
    /**
     * @var BraintreeConfig
     */
    private BraintreeConfig $braintreeConfig;

    /**
     * @var BraintreeAdapter
     */
    private BraintreeAdapter $braintreeAdapter;

    /**
     * @var LpmConfig
     */
    private LpmConfig $lpmConfig;

    /**
     * Fallback constructor
     *
     * @param Context $context
     * @param BraintreeConfig $braintreeConfig
     * @param BraintreeAdapter $braintreeAdapter
     * @param LpmConfig $lpmConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        BraintreeConfig $braintreeConfig,
        BraintreeAdapter $braintreeAdapter,
        LpmConfig $lpmConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->braintreeConfig = $braintreeConfig;
        $this->braintreeAdapter = $braintreeAdapter;
        $this->lpmConfig = $lpmConfig;
    }

    /**
     * Get client token
     *
     * @return string
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getClientToken(): string
    {
        return $this->lpmConfig->getClientToken();
    }

    /**
     * Get merchant account id
     *
     * @return string|null
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getMerchantAccountId(): ?string
    {
        return $this->lpmConfig->getMerchantAccountId();
    }

    /**
     * Get redirect url on fail
     *
     * @return mixed|null
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getRedirectUrlOnFail(): mixed
    {
        return $this->lpmConfig->getRedirectUrlOnFail();
    }
}
