<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Plugin;

use Closure;
use Exception;
use Magento\PaymentServicesPaypal\Model\CancellationService;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Model\AdminOrder\Create;
use Magento\Sales\Model\Order;

/**
 * Cancels an order and an authorization transaction.
 */
class AdminOrderCancellation
{
    /**
     * @var CartRepositoryInterface
     */
    private CartRepositoryInterface $quoteRepository;

    /**
     * @var CancellationService
     */
    private CancellationService $cancellationService;

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param CancellationService $cancellationService
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        CancellationService $cancellationService
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->cancellationService = $cancellationService;
    }

    /**
     * Cancels an order if an exception occurs during the order creation.
     *
     * @param Create $subject
     * @param Closure $proceed
     * @return Order
     * @throws Exception
     */
    public function aroundCreateOrder(
        Create $subject,
        Closure $proceed
    ): Order {
        try {
            return $proceed();
        } catch (Exception $e) {
            $this->cancellationService->executeForAdmin(
                (int) $subject->getQuote()->getId(),
                $subject->getQuote()->getReservedOrderId()
            );
            throw $e;
        }
    }
}
