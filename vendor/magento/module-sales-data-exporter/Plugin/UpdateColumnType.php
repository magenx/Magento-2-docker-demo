<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesDataExporter\Plugin;

use Magento\Framework\Mview\View\Changelog;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Ddl\Table;

/**
 * Workaround to enable mview functionality for order statuses
 */
class UpdateColumnType
{
    /**
     * Order status feed indexer id
     */
    public const ORDER_STATUS_VIEW_ID = 'sales_order_status_data_exporter';

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @param ResourceConnection $indexerRegistry
     */
    public function __construct(
        ResourceConnection $resource
    ) {
        $this->resource = $resource;
    }

    /**
     * Change type of entity_id column
     *
     * @param Changelog $subject
     */
    public function afterCreate(Changelog $subject): void
    {
        if ($subject->getViewId() === self::ORDER_STATUS_VIEW_ID) {
            $changelogTableName = $this->resource->getTableName($subject->getName());
            $this->resource->getConnection()->modifyColumn(
                $changelogTableName,
                $subject->getColumnName(),
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => 32,
                    'unsigned' => false,
                    'nullable' => false,
                    'default' => '',
                    'comment' => 'Order status',
                ]);
        }
    }
}
