<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesDataExporter\Model\Indexer;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\DataExporter\Model\Indexer\EntityIdsProviderInterface;

/**
 * Provide orders statuses entities to index
 */
class OrderStatusIdsProvider implements EntityIdsProviderInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @inheritdoc
     */
    public function getAllIds(FeedIndexMetadata $metadata) : ?\Generator
    {
        $connection = $this->resourceConnection->getConnection();
        yield $connection->fetchAll($this->getStatusesSelect($metadata));
    }

    /**
     * @inheritDoc
     */
    public function getAffectedIds(FeedIndexMetadata $metadata, array $ids): array
    {
        return $ids;
    }

    /**
     * Get statuses select
     *
     * @param FeedIndexMetadata $metadata
     * @return Select
     */
    private function getStatusesSelect(FeedIndexMetadata $metadata) : Select
    {
        $connection = $this->resourceConnection->getConnection();
        return $connection->select()
            ->from(
                ['s' => $this->resourceConnection->getTableName($metadata->getSourceTableName())],
                [
                    $metadata->getFeedIdentity() =>
                        's.' . $metadata->getSourceTableField()
                ]
            )
            ->order('s.' .  $metadata->getSourceTableField());
    }
}
