<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\Recaptcha;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Webapi\Rest\Request;
use Magento\ReCaptchaUi\Model\IsCaptchaEnabledInterface;
use Magento\ReCaptchaUi\Model\ValidationConfigResolverInterface;
use Magento\ReCaptchaValidationApi\Api\Data\ValidationConfigInterface;
use Magento\ReCaptchaWebapiApi\Api\Data\EndpointInterface;
use Magento\ReCaptchaWebapiApi\Api\WebapiValidationConfigProviderInterface;
use PayPal\Braintree\Model\Ui\ConfigProvider;

/**
 * Provide checkout related endpoint configuration.
 */
class WebapiConfigProvider implements WebapiValidationConfigProviderInterface
{
    public const CAPTCHA_ID = 'braintree';

    /**
     * @var Request
     */
    private Request $request;

    /**
     * @var IsCaptchaEnabledInterface
     */
    private IsCaptchaEnabledInterface $isEnabled;

    /**
     * @var ValidationConfigResolverInterface
     */
    private ValidationConfigResolverInterface $configResolver;

    /**
     * @param Request $request
     * @param IsCaptchaEnabledInterface $isEnabled
     * @param ValidationConfigResolverInterface $configResolver
     */
    public function __construct(
        Request $request,
        IsCaptchaEnabledInterface $isEnabled,
        ValidationConfigResolverInterface $configResolver
    ) {
        $this->request = $request;
        $this->isEnabled = $isEnabled;
        $this->configResolver = $configResolver;
    }

    /**
     * Provides a validation config for an endpoint if it exists and validation is required.
     *
     * @param EndpointInterface $endpoint
     * @return ValidationConfigInterface|null
     * @throws InputException
     */
    public function getConfigFor(EndpointInterface $endpoint): ?ValidationConfigInterface
    {
        // Check if we should perform validation for the Rest Request.
        if ($endpoint->getServiceMethod() === 'savePaymentInformationAndPlaceOrder'
            && !$this->isBraintreePaymentRestRequest()
        ) {
            return null;
        }

        //phpcs:disable Magento2.PHP.LiteralNamespaces
        if ($endpoint->getServiceMethod() === 'savePaymentInformationAndPlaceOrder'
            || $endpoint->getServiceClass() === 'Magento\QuoteGraphQl\Model\Resolver\SetPaymentAndPlaceOrder'
            || $endpoint->getServiceClass() === 'Magento\QuoteGraphQl\Model\Resolver\PlaceOrder'
        ) {
            if ($this->isEnabled->isCaptchaEnabledFor(self::CAPTCHA_ID)) {
                return $this->configResolver->get(self::CAPTCHA_ID);
            }
        }
        //phpcs:enable Magento2.PHP.LiteralNamespaces

        return null;
    }

    /**
     * Check rest request from Braintree Payments
     *
     * @return bool
     */
    private function isBraintreePaymentRestRequest(): bool
    {
        $requestData = $this->request->getRequestData();

        return isset($requestData['paymentMethod']['method'])
            && $requestData['paymentMethod']['method'] === ConfigProvider::CODE;
    }
}
