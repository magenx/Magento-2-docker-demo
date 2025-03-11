<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\Recaptcha;

use Magento\ReCaptchaWebapiApi\Api\Data\EndpointInterface;

class IsCaptchaApplicableForRequest implements IsCaptchaApplicableForRequestInterface
{
    /**
     * Determine whether Captcha should be used for request.
     *
     * This is the default class loaded which currently should always validate as true.
     *
     * @param EndpointInterface $endpoint
     * @return bool
     */
    public function execute(EndpointInterface $endpoint): bool
    {
        return true;
    }
}
