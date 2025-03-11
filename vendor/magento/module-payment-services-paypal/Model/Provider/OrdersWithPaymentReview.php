<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Model\Provider;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\QueryXml\Model\QueryProcessor;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class OrdersWithPaymentReview
{
    private const ORDERS_IN_PAYMENT_REVIEW_QUERY_NAME = 'salesOrdersInPaymentReview';

    /**
     * @var QueryProcessor
     */
    private QueryProcessor $queryProcessor;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var array
     */
    private array $methods;

    /**
     * @param QueryProcessor $queryProcessor
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param array $methods
     */
    public function __construct(
        QueryProcessor $queryProcessor,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        array $methods = []
    ) {
        $this->methods = $methods;
        $this->queryProcessor = $queryProcessor;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Get orders that are in Payment Review status
     *
     * @param int|null $count
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function get(?int $count = null): array
    {
        $ids = $this->getIdsForPaymentReviewOrders();

        if ($count) {
            $ids = array_slice($ids, 0, $count);
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(OrderInterface::ENTITY_ID, $ids, 'in')
            ->create();

        return $this->orderRepository
            ->getList($searchCriteria)
            ->getItems();
    }

    /**
     * Get order ids that are in Payment Review status
     *
     * @return array
     */
    private function getIdsForPaymentReviewOrders(): array
    {
        $queryArguments = ['methods' => $this->methods];

        return array_column(
            $this->queryProcessor
                ->execute(self::ORDERS_IN_PAYMENT_REVIEW_QUERY_NAME, $queryArguments)
                ->fetchAll(),
            "id"
        );
    }
}
