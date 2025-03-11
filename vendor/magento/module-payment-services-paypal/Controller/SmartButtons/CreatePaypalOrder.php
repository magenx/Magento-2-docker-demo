<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Controller\SmartButtons;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\PaymentServicesPaypal\Model\SmartButtons\Checkout;
use Magento\Framework\Exception\LocalizedException;
use Magento\PaymentServicesBase\Model\HttpException;
use Exception;

class CreatePaypalOrder implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @var Checkout
     */
    private $checkout;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @param RequestInterface $request
     * @param ResultFactory $resultFactory
     * @param Checkout $checkout
     */
    public function __construct(
        RequestInterface $request,
        ResultFactory $resultFactory,
        Checkout $checkout
    ) {
        $this->request = $request;
        $this->resultFactory = $resultFactory;
        $this->checkout = $checkout;
    }

    /**
     * Dispatch the order creation request with Commerce params
     *
     * @return ResultInterface
     */
    public function execute() : ResultInterface
    {
        $error = false;
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        try {
            $paymentSource = $this->request->getPost('payment_source');
            $threeDSMode = $this->request->getPost('three_ds_mode');
            $location = $this->request->getParam('location');
            $this->checkout->setLocation($location);
            if ($location !== $this->checkout::LOCATION_PRODUCT_PAGE) {
                $this->checkout->unsetQuote();
            }
            $this->checkout->validateQuote();
            try {
                $response = $this->checkout->createPayPalOrder($paymentSource, $threeDSMode);
                if (!$response['is_successful']) {
                    throw new HttpException('Failed to create an order.');
                }
                $result->setHttpResponseCode($response['status'])
                    ->setData(['response' => $response]);
            } catch (LocalizedException | Exception $e) {
                $error = __('Can\'t create PayPal order. Please try again.');
            }
        } catch (LocalizedException $e) {
            $error = $e->getMessage();
        }
        if ($error) {
            $result->setHttpResponseCode(500)
                ->setData(
                    [
                        'response' => [
                            'error' => $error
                        ]
                    ]
                );
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function createCsrfValidationException(RequestInterface $request) :? InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function validateForCsrf(RequestInterface $request) :? bool
    {
        return true;
    }
}
