<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Controller\SmartButtons;

use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\PaymentServicesPaypal\Model\SmartButtons\Checkout;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Exception\LocalizedException;

class Review implements HttpGetActionInterface
{
    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var MessageManagerInterface
     */
    private $messageManager;

    /**
     * @var Checkout
     */
    private $checkout;

    /**
     * @param ResultFactory $resultFactory
     * @param MessageManagerInterface $messageManager
     * @param Checkout $checkout
     */
    public function __construct(
        ResultFactory $resultFactory,
        MessageManagerInterface $messageManager,
        Checkout $checkout
    ) {
        $this->resultFactory = $resultFactory;
        $this->messageManager = $messageManager;
        $this->checkout = $checkout;
    }

    /**
     * @return ResultInterface
     */
    public function execute() : ResultInterface
    {
        try {
            $this->checkout->validateQuote();
        } catch (LocalizedException $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                $e->getMessage()
            );
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('checkout/cart');
        }
        return $this->resultFactory->create(ResultFactory::TYPE_PAGE);
    }
}
