<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\StoreDataExporter\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Indexer\IndexerRegistry;

/**
 * Observer for store feed indexation during save operation
 */
class ReindexStoreFeed implements ObserverInterface
{
    /**
     * Review feed indexer id
     */
    public const ORDER_FEED_INDEXER = 'store_data_exporter';

    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @var string
     */
    private $entityName;

    /**
     * @param IndexerRegistry $indexerRegistry
     * @param $entityName
     */
    public function __construct(
        IndexerRegistry $indexerRegistry,
        string $entityName
    ) {
        $this->indexerRegistry = $indexerRegistry;
        $this->entityName = $entityName;
    }

    /**
     * Generate gift card accounts after order save.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $indexer = $this->indexerRegistry->get(self::ORDER_FEED_INDEXER);

        if (!$indexer->isScheduled()) {
            $entity = $observer->getEvent()->getData($this->entityName);
            $indexer->reindexList([$entity->getWebsiteId()]);
        }
    }
}
