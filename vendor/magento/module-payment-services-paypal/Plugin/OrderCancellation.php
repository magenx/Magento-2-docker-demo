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
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Cancels an order and an authorization transaction.
 */
class OrderCancellation
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
     * @param CartManagementInterface $subject
     * @param Closure $proceed
     * @param int $cartId
     * @param PaymentInterface|null $payment
     * @return int
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundPlaceOrder(
        CartManagementInterface $subject,
        Closure $proceed,
        int $cartId,
        ?PaymentInterface $payment = null
    ): int {
        try {
            return (int)$proceed($cartId, $payment);
        } catch (Exception $e) {
            $this->cancellationService->execute((int) $cartId);
            throw $e;
        }
    }
}
