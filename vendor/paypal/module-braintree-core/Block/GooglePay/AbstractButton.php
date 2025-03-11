<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Block\GooglePay;

use Magento\Framework\Exception\LocalizedException;
use PayPal\Braintree\Model\GooglePay\Auth;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Model\MethodInterface;

/***/
abstract class AbstractButton extends Template
{
    /**
     * @var Session
     */
    protected Session $checkoutSession;

    /**
     * @var MethodInterface
     */
    protected MethodInterface $payment;

    /**
     * @var Auth
     */
    protected Auth $auth;

    /**
     * Button constructor.
     * @param Context $context
     * @param Session $checkoutSession
     * @param MethodInterface $payment
     * @param Auth $auth
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        MethodInterface $payment,
        Auth $auth,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->payment = $payment;
        $this->auth = $auth;
    }

    /**
     * @inheritdoc
     */
    protected function _toHtml(): string // @codingStandardsIgnoreLine
    {
        if ($this->isActive()) {
            return parent::_toHtml();
        }

        return '';
    }

    /**
     * Is method active
     *
     * @return bool
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function isActive(): bool
    {
        return $this->payment->isAvailable($this->checkoutSession->getQuote());
    }

    /**
     * Merchant name to display in popup
     *
     * @return string
     */
    public function getMerchantId(): string
    {
        return $this->auth->getMerchantId();
    }

    /**
     * Get environment code
     *
     * @return string
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getEnvironment(): string
    {
        return $this->auth->getEnvironment();
    }

    /**
     * Braintree API token
     *
     * @return string
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getClientToken(): string
    {
        return $this->auth->getClientToken();
    }

    /**
     * URL To success page
     *
     * @return string
     */
    public function getActionSuccess(): string
    {
        return $this->getUrl('braintree/googlepay/review', ['_secure' => true]);
    }

    /**
     * Currency code
     *
     * @return string|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCurrencyCode(): ?string
    {
        if ($this->checkoutSession->getQuote()->getCurrency()) {
            return $this->checkoutSession->getQuote()->getCurrency()->getBaseCurrencyCode();
        }

        return null;
    }

    /**
     * Cart grand total
     *
     * @return float|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getAmount()
    {
        return $this->checkoutSession->getQuote()->getBaseGrandTotal();
    }

    /**
     * Available card types
     *
     * @return array
     */
    public function getAvailableCardTypes(): array
    {
        return $this->auth->getAvailableCardTypes();
    }

    /**
     * BTN Color
     *
     * @return int
     */
    public function getBtnColor(): int
    {
        return $this->auth->getBtnColor();
    }

    /**
     * Get an array of the 3DSecure specific data
     *
     * @return array
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function get3DSecureConfigData(): array
    {
        if (!$this->auth->is3DSecureEnabled()) {
            return [
                'enabled' => false,
                'challengeRequested' => false,
                'thresholdAmount' => 0.0,
                'specificCountries' => [],
                'ipAddress' => ''
            ];
        }

        return [
            'enabled' => true,
            'challengeRequested' => $this->auth->is3DSecureAlwaysRequested(),
            'thresholdAmount' => $this->auth->get3DSecureThresholdAmount(),
            'specificCountries' => $this->auth->get3DSecureSpecificCountries(),
            'ipAddress' => $this->auth->getIpAddress()
        ];
    }
}
