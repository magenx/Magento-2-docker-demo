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
use Magento\PaymentServicesPaypal\Model\OrderService;
use Magento\PaymentServicesPaypal\Model\SmartButtons\Checkout;
use Magento\PaymentServicesPaypal\Model\SmartButtons\Checkout\AddressConverter;
use Magento\Framework\UrlInterface;
use Magento\Framework\Exception\LocalizedException;
use Exception;

class UpdateQuote implements HttpPostActionInterface, CsrfAwareActionInterface
{

    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var Checkout
     */
    private $checkout;

    /**
     * @var AddressConverter
     */
    private $addressConverter;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @param OrderService $orderService
     * @param RequestInterface $request
     * @param ResultFactory $resultFactory
     * @param Checkout $checkout
     * @param AddressConverter $addressConverter
     * @param UrlInterface $url
     */
    public function __construct(
        OrderService $orderService,
        RequestInterface $request,
        ResultFactory $resultFactory,
        Checkout $checkout,
        AddressConverter $addressConverter,
        UrlInterface $url
    ) {
        $this->orderService = $orderService;
        $this->request = $request;
        $this->resultFactory = $resultFactory;
        $this->checkout = $checkout;
        $this->addressConverter = $addressConverter;
        $this->url = $url;
    }

    /**
     * Execute quote update
     *
     * @return ResultInterface
     */
    public function execute() : ResultInterface
    {
        $error = false;
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        try {
            $location = $this->checkout->getLocation();
            if ($location !== $this->checkout::LOCATION_PRODUCT_PAGE) {
                $this->checkout->unsetQuote();
            }
            $this->checkout->validateQuote();
            try {
                $order = $this->orderService->get($this->request->getParam('paypal_order_id'));
                $this->checkout->updateQuote(
                    $this->addressConverter->convertShippingAddress($order),
                    $this->addressConverter->convertBillingAddress($order),
                    $this->request->getParam('paypal_order_id'),
                    $this->request->getParam('paypal_payer_id', ''),
                    $order['paypal-order']['mp_order_id']
                );
            } catch (LocalizedException | Exception $e) {
                $error = __('Can\'t update quote. Please try again.');
            }
        } catch (LocalizedException $e) {
            $error = $e->getMessage();
        }
        if (!$error) {
            $result->setHttpResponseCode(200)
                ->setData(
                    [
                        'success' => true,
                        'redirectUrl' => $this->url->getUrl('paymentservicespaypal/smartbuttons/review')
                    ]
                );
        } else {
            $result->setHttpResponseCode(500)
                ->setData(
                    [
                        'success' => false,
                        'error' => $error
                    ]
                );
        }
        return $result;
    }

    /**
     * Override for CsrfVaildationException method
     *
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function createCsrfValidationException(RequestInterface $request) :? InvalidRequestException
    {
        return null;
    }

    /**
     * Override for CsrfValidation method
     *
     * @param RequestInterface $request
     * @return bool|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validateForCsrf(RequestInterface $request) :? bool
    {
        return true;
    }
}
