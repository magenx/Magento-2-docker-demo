<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesDataExporter\Plugin;

use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Sales\Model\Order\Status;

/**
 * Plugin for running order status feed indexation during save
 */
class ReindexOrderStatusFeed
{
    /**
     * Order status feed indexer id
     */
    public const ORDER_STATUS_FEED_INDEXER = 'sales_order_status_data_exporter';

    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @param IndexerRegistry $indexerRegistry
     */
    public function __construct(
        IndexerRegistry $indexerRegistry
    ) {
        $this->indexerRegistry = $indexerRegistry;
    }

    /**
     * Execute reindex process on save commit callback
     *
     * @param Status $subject
     *
     * @return Status
     */
    public function beforeAfterCommitCallback(Status $subject): Status
    {
        $indexer = $this->indexerRegistry->get(self::ORDER_STATUS_FEED_INDEXER);

        if (!$indexer->isScheduled()) {
            $indexer->reindexList([$subject->getId()]);
        }

        return $subject;
    }
}
