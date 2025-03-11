<?php
/**
 * Copyright 2023 Adobe
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

namespace Magento\SaaSCommon\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class FeedFilter
 */
class FeedRegistry
{

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var string
     */
    private $registryTable;

    /**
     * @var array
     */
    private $uniqueFields;

    /**
     * @var array
     */
    private $excludeFields;

    /**
     * FeedRegistry constructor.
     *
     * @param ResourceConnection $resourceConnection
     * @param SerializerInterface $serializer
     * @param string $registryTable
     * @param array $uniqueFields
     * @param array $excludeFields
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        SerializerInterface $serializer,
        string $registryTable = '',
        array $uniqueFields = [],
        array $excludeFields = []
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->serializer = $serializer;
        $this->registryTable = $registryTable;
        $this->uniqueFields = $uniqueFields;
        $this->excludeFields = $excludeFields;
    }

    /**
     * Get row identifier
     *
     * @param array $row
     * @return string
     */
    private function getIdentifier(array $row) : string
    {
        $uniqueFields = [];
        foreach ($this->uniqueFields as $field) {
            $uniqueFields[$field] = isset($row[$field]) ? $row[$field] : null;
        }
        return sha1($this->serializer->serialize($uniqueFields));
    }

    /**
     * Sanitize row
     *
     * @param array $row
     * @return array
     */
    private function sanitizeRow(array $row) : array
    {
        $output = [];
        foreach ($row as $key => $value) {
            if (!in_array($key, $this->excludeFields)) {
                $output[$key] = $value;
            }
        }
        return $output;
    }

    /**
     * Hash row data
     *
     * @param array $row
     * @return string
     */
    private function hashData(array $row) : string
    {
        return sha1($this->serializer->serialize($this->sanitizeRow($row)));
    }

    /**
     * Register feed
     *
     * @param array $data
     */
    public function registerFeed(array $data) : void
    {
        $input = [];
        $connection = $this->resourceConnection->getConnection();
        foreach ($data as $row) {
            $identifier = $this->getIdentifier($row);
            $input[$identifier] = [
                'identifier' => $identifier,
                'feed_hash' => $this->hashData($row)
            ];
        }
        $connection->insertOnDuplicate(
            $this->resourceConnection->getTableName($this->registryTable),
            $input,
            ['feed_hash']
        );
    }

    /**
     * Remove feed items with the same content.
     * Compares hashed version of _this_ with version stored in <registryTable>
     *
     * @param array $data
     * @return array
     */
    public function filter(array $data) : array
    {
        $identifiers = [];
        $output = [];
        foreach ($data as $row) {
            $identifier = $this->getIdentifier($row);
            $identifiers[$identifier] = $identifier;
        }
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(['f' => $this->resourceConnection->getTableName($this->registryTable)])
            ->where('f.identifier IN (?)', $identifiers);
        $hashes = $connection->fetchAssoc($select);
        foreach ($data as $row) {
            $identifier = $this->getIdentifier($row);
            if (isset($hashes[$identifier]) && $this->hashData($row) == $hashes[$identifier]['feed_hash']) {
                continue;
            }
            $output[] = $row;
        }
        return $output;
    }
}
