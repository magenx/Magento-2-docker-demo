<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Controller\Lpm;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;

class Fallback extends Action implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var Http
     */
    private Http $httpRequest;

    /**
     * Fallback constructor
     *
     * @param Context $context
     * @param Http $httpRequest
     */
    public function __construct(
        Context $context,
        Http $httpRequest
    ) {
        parent::__construct($context);
        $this->httpRequest = $httpRequest;
    }

    /**
     * Process braintree webhook response
     *
     * @return ResultInterface|Page|ResponseInterface
     */
    public function execute(): ResultInterface|Page|ResponseInterface
    {
        if (!empty($this->httpRequest->getParams())) {
            return $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        }

        $this->messageManager->addErrorMessage(
            __("Payment can not be processed as incorrect params received")
        );

        return $this->_redirect('checkout', ['_fragment' => 'payment']);
    }
}
