<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Controller\SmartButtons;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Checkout\Model\Session\SuccessValidator;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Checkout\Model\Session as CheckoutSession;

class Success implements HttpGetActionInterface
{
    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var SuccessValidator
     */
    private $successValidator;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @param ResultFactory $resultFactory
     * @param SuccessValidator $successValidator
     * @param ManagerInterface $eventManager
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        ResultFactory $resultFactory,
        SuccessValidator $successValidator,
        ManagerInterface $eventManager,
        CheckoutSession $checkoutSession
    ) {
        $this->resultFactory = $resultFactory;
        $this->successValidator = $successValidator;
        $this->eventManager = $eventManager;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Checkout success page for purchase from product detail page.
     *
     * @return ResultInterface
     */
    public function execute() : ResultInterface
    {
        if (!$this->successValidator->isValid()) {
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('checkout/cart');
        }
        $this->checkoutSession->setLastSuccessQuoteId(null);
        $result = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $this->eventManager->dispatch(
            'checkout_onepage_controller_success_action',
            [
                'order_ids' => [$this->checkoutSession->getLastOrderId()],
                'order' => $this->checkoutSession->getLastRealOrder()
            ]
        );
        return $result;
    }
}
