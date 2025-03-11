<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Helper;

use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order as OrderModel;

class Order
{
    /**
     * @var ManagerInterface
     */
    private ManagerInterface $eventManager;

    /**
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        ManagerInterface $eventManager
    ) {
        $this->eventManager = $eventManager;
    }

    /**
     * Cancel the orders of BT transactions whose authorization expired
     *
     * @param OrderInterface $order
     * @return OrderInterface
     */
    public function cancelExpired(OrderInterface $order): OrderInterface
    {
        foreach ($order->getAllItems() as $item) {
            $item->cancel();
        }
        $order->setSubtotalCanceled($order->getSubtotal() - $order->getSubtotalInvoiced());
        $order->setBaseSubtotalCanceled($order->getBaseSubtotal() - $order->getBaseSubtotalInvoiced());

        $order->setTaxCanceled($order->getTaxAmount() - $order->getTaxInvoiced());
        $order->setBaseTaxCanceled($order->getBaseTaxAmount() - $order->getBaseTaxInvoiced());

        $order->setShippingCanceled($order->getShippingAmount() - $order->getShippingInvoiced());
        $order->setBaseShippingCanceled($order->getBaseShippingAmount() - $order->getBaseShippingInvoiced());

        $order->setDiscountCanceled(abs((float) $order->getDiscountAmount()) - $order->getDiscountInvoiced());
        $order->setBaseDiscountCanceled(
            abs((float) $order->getBaseDiscountAmount()) - $order->getBaseDiscountInvoiced()
        );

        $order->setTotalCanceled($order->getGrandTotal() - $order->getTotalPaid());
        $order->setBaseTotalCanceled($order->getBaseGrandTotal() - $order->getBaseTotalPaid());

        $order->setState(OrderModel::STATE_CANCELED);
        $order->setStatus(OrderModel::STATE_CANCELED);

        $this->eventManager->dispatch('order_cancel_after', ['order' => $order]);
        return $order;
    }
}
