<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PaymentServicesSaaSExport\Setup\Declaration\Schema\Db\MySQL\DDL\Trigger;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\Declaration\Schema\Db\DDLTriggerInterface;
use Magento\Framework\Setup\Declaration\Schema\Dto\Table;
use Magento\Framework\Setup\Declaration\Schema\ElementHistory;
use Psr\Log\LoggerInterface;

class SafeMigrateDataFromAnotherTable implements DDLTriggerInterface
{
    /**
     * Pattern with which we can match whether we can apply and use this trigger or not.
     */
    private const MATCH_PATTERN = '/safeMigrateDataFromAnotherTable\(([^\)]+)\)/';

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function isApplicable(string $statement): bool
    {
        return (bool)preg_match(self::MATCH_PATTERN, $statement);
    }

    /**
     * @inheritdoc
     */
    public function getCallback(ElementHistory $tableHistory): callable
    {
        /** @var Table $table */
        $table = $tableHistory->getNew();
        preg_match(self::MATCH_PATTERN, $table->getOnCreate(), $matches);
        $sourceTableName = $this->resourceConnection->getTableName($matches[1]);
        $connection  = $this->resourceConnection->getConnection($table->getResource());

        if (!$connection->isTableExists($sourceTableName)) {
            $this->logger->info(
                sprintf('Source table %s does not exist. Skipping data migration...', $sourceTableName)
            );

            return function () {
            };
        }

        return function () use ($table, $sourceTableName, $connection) {
            $tableName = $table->getName();
            $select = $connection->select()->from($sourceTableName);
            $connection->query($connection->insertFromSelect($select, $tableName));
        };
    }
}
