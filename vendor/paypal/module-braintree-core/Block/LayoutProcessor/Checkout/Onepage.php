<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Block\LayoutProcessor\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\ReCaptchaUi\Model\IsCaptchaEnabledInterface;
use Magento\ReCaptchaUi\Model\UiConfigResolverInterface;
use PayPal\Braintree\Gateway\Config\Config;

/**
 * Provides reCaptcha component configuration.
 */
class Onepage implements LayoutProcessorInterface
{
    /**
     * @var UiConfigResolverInterface
     */
    private UiConfigResolverInterface $captchaUiConfigResolver;

    /**
     * @var IsCaptchaEnabledInterface
     */
    private IsCaptchaEnabledInterface $isCaptchaEnabled;

    /**
     * @var Config
     */
    private Config $gatewayConfig;

    /**
     * @param UiConfigResolverInterface $captchaUiConfigResolver
     * @param IsCaptchaEnabledInterface $isCaptchaEnabled
     * @param Config $gatewayConfig
     */
    public function __construct(
        UiConfigResolverInterface $captchaUiConfigResolver,
        IsCaptchaEnabledInterface $isCaptchaEnabled,
        Config $gatewayConfig
    ) {
        $this->captchaUiConfigResolver = $captchaUiConfigResolver;
        $this->isCaptchaEnabled = $isCaptchaEnabled;
        $this->gatewayConfig = $gatewayConfig;
    }

    /**
     * @inheritdoc
     *
     * @param array $jsLayout
     * @return array
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function process($jsLayout): array
    {
        // Enable/Disable Captcha.
        $jsLayout = $this->toggleCaptcha($jsLayout);

        // Enable/Disable Express Payment methods.
        return $this->toggleExpressPayments($jsLayout);
    }

    /**
     * Toggle ReCaptcha
     *
     * @param array $jsLayout
     * @return array
     * @throws InputException
     */
    private function toggleCaptcha(array $jsLayout): array
    {
        $key = 'braintree';

        if ($this->isCaptchaEnabled->isCaptchaEnabledFor($key)) {
            $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
            ['payment']['children']['payments-list']['children']['braintree-recaptcha-container']['children']
            ['braintree-recaptcha']['settings'] = $this->captchaUiConfigResolver->get($key);

            return $jsLayout;
        }

        if (isset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
            ['payment']['children']['payments-list']['children']['braintree-recaptcha-container']['children']
            ['braintree-recaptcha'])
        ) {
            unset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                ['payment']['children']['payments-list']['children']['braintree-recaptcha-container']['children']
                ['braintree-recaptcha']);
        }

        return $jsLayout;
    }

    /**
     * Remove express payment methods if disabled in the config.
     *
     * @param array $jsLayout
     * @return array
     * @throws InputException
     * @throws NoSuchEntityException
     */
    private function toggleExpressPayments(array $jsLayout): array
    {
        // No change if express payments are enabled.
        if ($this->gatewayConfig->areCheckoutExpressPaymentsEnabled()) {
            return $jsLayout;
        }

        if (isset($jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
            ['shippingAddress']['children']['braintree-express-payments'])
        ) {
            unset($jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
                ['shippingAddress']['children']['braintree-express-payments']);
        }

        return $jsLayout;
    }
}
