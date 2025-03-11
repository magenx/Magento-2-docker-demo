<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Controller\SmartButtons;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\App\ViewInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\PaymentServicesPaypal\Model\SmartButtons\Checkout;
use Exception;

class SaveShippingMethod implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var ViewInterface
     */
    private $view;

    /**
     * @var Checkout
     */
    private $checkout;

    /**
     * @var MessageManagerInterface
     */
    private $messageManager;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @param RequestInterface $request
     * @param ResultFactory $resultFactory
     * @param ViewInterface $view
     * @param Checkout $checkout
     * @param MessageManagerInterface $messageManager
     * @param UrlInterface $url
     */
    public function __construct(
        RequestInterface $request,
        ResultFactory $resultFactory,
        ViewInterface $view,
        Checkout $checkout,
        MessageManagerInterface $messageManager,
        UrlInterface $url
    ) {
        $this->request = $request;
        $this->resultFactory = $resultFactory;
        $this->view = $view;
        $this->checkout = $checkout;
        $this->messageManager = $messageManager;
        $this->url = $url;
    }

    /**
     * @return ResultInterface
     */
    public function execute() : ResultInterface
    {
        $isAjax = $this->request->getParam('isAjax');
        try {
            $this->checkout->validateQuote();
            try {
                $this->checkout->updateShippingMethod($this->request->getParam('shipping_method'));
                if ($isAjax) {
                    $this->view->loadLayout('paymentservicespaypal_smartbuttons_review_details', true, true, false);

                    /** @var ViewInterface $html */
                    $html = $this->view->getLayout()->getBlock('page.block');

                    /** @var \Magento\Quote\Model\Quote $quote */
                    $quote = $this->checkout->getQuote();
                    $html = $html->setQuote($quote)->toHtml();

                    return $this->resultFactory->create(ResultFactory::TYPE_JSON)
                        ->setHttpResponseCode(200)
                        ->setData(['html' => $html]);
                }
            } catch (LocalizedException $e) {
                $this->messageManager->addExceptionMessage(
                    $e,
                    $e->getMessage()
                );
            } catch (Exception $e) {
                $this->messageManager->addExceptionMessage(
                    $e,
                    __('Can\'t update shipping method. Please try again.')
                );
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                $e->getMessage()
            );
        }
        if ($isAjax) {
            return $this->resultFactory->create(ResultFactory::TYPE_JSON)
                ->setHttpResponseCode(200)
                ->setData(['redirectUrl' => $this->url->getUrl('*/*/review')]);
        }
        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
            ->setPath('*/*/review');
    }

    /**
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request) :? InvalidRequestException
    {
        return null;
    }

    /**
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request) :? bool
    {
        return true;
    }
}
