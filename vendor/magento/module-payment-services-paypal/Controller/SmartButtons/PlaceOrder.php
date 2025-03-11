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
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\PaymentServicesPaypal\Model\CancellationService;
use Magento\PaymentServicesPaypal\Model\SmartButtons\Checkout;
use Exception;

class PlaceOrder implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var MessageManagerInterface
     */
    private $messageManager;

    /**
     * @var Checkout
     */
    private $checkout;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var CancellationService
     */
    private $cancellationService;

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param MessageManagerInterface $messageManager
     * @param Checkout $checkout
     * @param UrlInterface $url
     * @param CancellationService $cancellationService
     */
    public function __construct(
        RequestInterface $request,
        ResponseInterface $response,
        MessageManagerInterface $messageManager,
        Checkout $checkout,
        UrlInterface $url,
        CancellationService $cancellationService
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->messageManager = $messageManager;
        $this->checkout = $checkout;
        $this->url = $url;
        $this->cancellationService = $cancellationService;
    }

    /**
     * @inheritdoc
     */
    public function execute() : ResponseInterface
    {
        try {
            $this->checkout->validateQuote();
            $this->checkout->placeOrder();
            $this->response->setRedirect($this->url->getUrl($this->checkout->getSuccessPageUri()));
        } catch (LocalizedException $e) {
            $canceled = $this->cancellationService->execute((int) $this->checkout->getQuote()->getId());
            $this->processException(
                $e,
                $e->getMessage(),
                $canceled
            );
        } catch (Exception $e) {
            $canceled = $this->cancellationService->execute((int) $this->checkout->getQuote()->getId());
            $this->processException(
                $e,
                __('We can\'t process the order right now. Please try again later.')->getText(),
                $canceled
            );
        }
        return $this->response;
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function createCsrfValidationException(RequestInterface $request) :? InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validateForCsrf(RequestInterface $request) :? bool
    {
        return true;
    }

    /**
     * Process exception.
     *
     * @param Exception $exception
     * @param string $message
     * @param bool $canceled
     * @return void
     */
    private function processException(Exception $exception, string $message, bool $canceled) : void
    {
        $this->messageManager->addExceptionMessage(
            $exception,
            $message
        );
        $this->response->setRedirect($this->url->getUrl($canceled ? 'checkout/cart' : '*/*/review'));
    }
}
