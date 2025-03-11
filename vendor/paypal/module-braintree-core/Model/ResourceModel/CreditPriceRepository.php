<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\ResourceModel;

use Magento\Framework\Data\Collection;
use Magento\Framework\DataObject;
use PayPal\Braintree\Api\CreditPriceRepositoryInterface;
use PayPal\Braintree\Api\Data\CreditPriceDataInterface;
use PayPal\Braintree\Model\ResourceModel\CreditPrice\CollectionFactory;

class CreditPriceRepository implements CreditPriceRepositoryInterface
{
    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * CreditPriceRepository constructor.
     *
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function save(CreditPriceDataInterface $entity): CreditPriceDataInterface
    {
        $entity->getResource()->save($entity);
        return $entity;
    }

    /**
     * @inheritdoc
     */
    public function getByProductId($productId)
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('product_id', $productId);
        $collection->setOrder('term', Collection::SORT_ORDER_ASC);
        return $collection->getItems();
    }

    /**
     * @inheritdoc
     */
    public function getCheapestByProductId($productId): CreditPriceDataInterface|DataObject
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('product_id', $productId);
        $collection->setOrder('monthly_payment', Collection::SORT_ORDER_ASC);
        $collection->setPageSize(1);

        return $collection->getFirstItem();
    }

    /**
     * @inheritdoc
     */
    public function deleteByProductId($productId)
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('product_id', $productId);
        return $collection->walk('delete');
    }
}
