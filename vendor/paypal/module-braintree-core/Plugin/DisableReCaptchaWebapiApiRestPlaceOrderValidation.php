<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Plugin;

use Magento\ReCaptchaCheckout\Model\WebapiConfigProvider;
use Magento\ReCaptchaValidationApi\Api\Data\ValidationConfigInterface;
use Magento\ReCaptchaWebapiApi\Api\Data\EndpointInterface;
use PayPal\Braintree\Model\Recaptcha\IsCaptchaApplicableForRequestInterface;

class DisableReCaptchaWebapiApiRestPlaceOrderValidation
{
    /**
     * @var IsCaptchaApplicableForRequestInterface
     */
    private IsCaptchaApplicableForRequestInterface $isCaptchaApplicableForRequest;

    /**
     * @param IsCaptchaApplicableForRequestInterface $isCaptchaApplicableForRequest
     */
    public function __construct(IsCaptchaApplicableForRequestInterface $isCaptchaApplicableForRequest)
    {
        $this->isCaptchaApplicableForRequest = $isCaptchaApplicableForRequest;
    }

    /**
     * Check whether ReCaptcha should be applied on the endpoint, no validation if result is already null.
     *
     * @param WebapiConfigProvider $subject
     * @param ValidationConfigInterface|null $result
     * @param EndpointInterface $endpoint
     * @return ValidationConfigInterface|null
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetConfigFor(
        WebapiConfigProvider $subject,
        ?ValidationConfigInterface $result,
        EndpointInterface $endpoint
    ): ?ValidationConfigInterface {
        return $result === null || $this->isCaptchaApplicableForRequest->execute($endpoint) ? $result : null;
    }
}
