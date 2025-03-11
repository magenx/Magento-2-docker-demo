<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Magento\PaymentServicesPaypal\Plugin;

use Magento\Framework\Message\ManagerInterface;
use Magento\PaymentServicesPaypal\Model\ApplePayConfigProvider;
use Magento\PaymentServicesPaypal\Model\HostedFieldsConfigProvider;
use Magento\PaymentServicesPaypal\Model\SmartButtonsConfigProvider;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Controller\Adminhtml\Order\View;

class OrderViewEnvironmentMessage
{
    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @param ManagerInterface $messageManager
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        ManagerInterface $messageManager,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->messageManager = $messageManager;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Display the sandbox warning message if
     * 1) environment is sandbox
     * 2) order paid with a Payment Service method
     *
     * @param View $subject
     */
    public function beforeExecute(View $subject)
    {
        $id = $subject->getRequest()->getParam('order_id');
        $warningMessage = 'This order was processed with a
        Payment Services sandbox payment method. No money was collected.';

        if ($id === null) {
            return;
        }
        try {
            $order = $this->orderRepository->get($id);
        } catch (\Exception $e) {
            return;
        }

        $payment = $order->getPayment();
        if (!in_array($payment->getMethod(), [
            HostedFieldsConfigProvider::CODE,
            SmartButtonsConfigProvider::CODE,
            ApplePayConfigProvider::CODE
        ])) {
            return;
        }
        /* @phpstan-ignore-next-line */
        if ($payment->getAdditionalInformation('payments_mode') === 'sandbox') {
            $this->messageManager->addWarningMessage(__($warningMessage));
        }
    }
}
