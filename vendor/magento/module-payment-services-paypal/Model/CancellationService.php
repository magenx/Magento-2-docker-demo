<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PaymentServicesPaypal\Gateway\Response\TxnIdHandler;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * The service to cancel an order and void authorization transaction.
 */
class CancellationService
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var array
     */
    private array $methods;

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderRepositoryInterface $orderRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param array $methods
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderRepositoryInterface $orderRepository,
        CartRepositoryInterface $quoteRepository,
        array $methods = []
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderRepository = $orderRepository;
        $this->quoteRepository = $quoteRepository;
        $this->methods = $methods;
    }

    /**
     * Cancels an order and authorization transaction in case when order was create or cancel transaction.
     *
     * @param int $cartId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function execute(int $cartId): bool
    {
        $quote = $this->quoteRepository->get($cartId);
        $incrementId = $quote->getReservedOrderId();

        return $this->executeForAdmin($cartId, $incrementId);
    }

    /**
     * Cancels an order and authorization transaction in case when order was create or cancel transaction for admin.
     *
     * @param int $cartId
     * @param string|null $incrementId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function executeForAdmin(int $cartId, ?string $incrementId = null): bool
    {
        $quote = $this->quoteRepository->get($cartId);
        if (!$this->isCancelable($quote)) {
            return false;
        }

        $order = $incrementId ? $this->getOrder($incrementId) : false;
        $this->cancel($order, $quote);

        return true;
    }

    /**
     * Check if quote cancelable.
     *
     * @param CartInterface $quote
     * @return bool
     */
    private function isCancelable(CartInterface $quote): bool
    {
        $payment = $quote->getPayment();
        if (!in_array($payment->getMethod(), $this->methods)) {
            return false;
        }

        //Using $payment->getAuthorizationTransaction()->getTxnId() for old orders
        if (!$payment->getAdditionalInformation(TxnIdHandler::AUTH_ID_KEY)) {
            return false;
        }

        return true;
    }

    /**
     * Cancel order or transaction.
     *
     * @param OrderInterface|null $order
     * @param CartInterface $quote
     * @return void
     */
    private function cancel(?OrderInterface $order, CartInterface $quote): void
    {
        $payment = $quote->getPayment();
        if ($order) {
            $order->cancel();
            $order->addCommentToStatusHistory(
                __('Order auto-canceled due to technical issues.')
            );
            $this->orderRepository->save($order);
        } else {
            $payment->getMethodInstance()->cancel($payment);
        }

        $payment->unsAdditionalInformation(TxnIdHandler::AUTH_ID_KEY);
        $this->quoteRepository->save($quote);
    }

    /**
     * Gets order by specified field.
     *
     * @param string $incrementId
     * @return OrderInterface|null
     */
    private function getOrder(string $incrementId): ?OrderInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(OrderInterface::INCREMENT_ID, $incrementId)
            ->create();

        $items = $this->orderRepository->getList($searchCriteria)
            ->getItems();

        return array_pop($items);
    }
}
