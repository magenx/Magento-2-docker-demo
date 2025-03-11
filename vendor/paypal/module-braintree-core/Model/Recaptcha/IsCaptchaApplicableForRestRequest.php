<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\Recaptcha;

use Magento\Framework\Webapi\Rest\Request;
use Magento\ReCaptchaWebapiApi\Api\Data\EndpointInterface;
use PayPal\Braintree\Model\ApplePay\Ui\ConfigProvider;

class IsCaptchaApplicableForRestRequest implements IsCaptchaApplicableForRequestInterface
{
    /**
     * @var Request
     */
    private Request $request;

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Determine whether Captcha should be used for request.
     *
     * Currently, it is only used for the REST Place order request and disables Captcha for Apple Pay as not required.
     *
     * @param EndpointInterface $endpoint
     * @return bool
     */
    public function execute(EndpointInterface $endpoint): bool
    {
        // Should check for REST API checkout place order endpoint.
        if ($endpoint->getServiceMethod() !== 'savePaymentInformationAndPlaceOrder') {
            return true;
        }

        $requestData = $this->request->getRequestData();

        // Should check for captcha only & only if payment method is not Apple Pay.
        return isset($requestData['paymentMethod']['method'])
            && $requestData['paymentMethod']['method'] !== ConfigProvider::METHOD_CODE;
    }
}
