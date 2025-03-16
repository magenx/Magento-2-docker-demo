<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesDataExporter\Model\Indexer;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\DataExporter\Model\Indexer\DataSerializerInterface;
use Magento\DataExporter\Model\FeedExportStatus;
use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;

/**
 * Class responsible for feed data serialization
 */
class OrdersDataSerializer implements DataSerializerInterface
{
    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var array
     */
    private $mapping;

    /**
     * @var array
     */
    private $unserializeKeys;

    /**
     * @param Json $serializer
     * @param array $unserializeKeys
     * @param array $mapping
     */
    public function __construct(
        Json $serializer,
        array $unserializeKeys = [],
        array $mapping = []
    ) {
        $this->serializer = $serializer;
        $this->mapping = $mapping;
        $this->unserializeKeys = $unserializeKeys;
    }

    /**
     * Serialize data
     *
     * @param array $data
     * @param FeedExportStatus|null $exportStatus
     * @param FeedIndexMetadata $metadata
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function serialize(array $data, ?FeedExportStatus $exportStatus, FeedIndexMetadata $metadata): array
    {
        $output = [];
        foreach ($data as $row) {
            $outputRow = [];
            foreach ($this->unserializeKeys as $unserializeKey) {
                $row[$unserializeKey] = $this->serializer->unserialize($row[$unserializeKey]);
            }
            $outputRow['feed_data'] = $this->serializer->serialize($row);
            foreach ($this->mapping as $field => $index) {
                $value = $this->getNestedValue($row, $index);
                if (isset($value)) {
                    $outputRow[$field] = is_array($value) ?
                        $this->serializer->serialize($value) :
                        $value;
                } else {
                    $outputRow[$field] = null;
                }
            }
            $output[] = $outputRow;
        }
        return $output;
    }

    /**
     * Get nested array value.
     *
     * @param array $array
     * @param string $path
     * @return mixed
     */
    private function getNestedValue(array $array, string $path)
    {
        $arrayPath = explode('.', $path);
        $reduce = function (array $source, $key) {
            return (array_key_exists($key, $source)) ? $source[$key] : null;
        };
        return array_reduce($arrayPath, $reduce, $array);
    }
}
