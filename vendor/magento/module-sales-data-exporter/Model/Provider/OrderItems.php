<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesDataExporter\Model\Provider;

use Magento\QueryXml\Model\QueryProcessor;
use Magento\Framework\Serialize\Serializer\Json;

class OrderItems
{
    private const PRODUCT_TYPE_BUNDLE = 'bundle';
    private const PRODUCT_TYPE_CONFIGURABLE = 'configurable';

    /**
     * @var QueryProcessor
     */
    private $queryProcessor;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param QueryProcessor $queryProcessor
     * @param Json $serializer
     */
    public function __construct(
        QueryProcessor $queryProcessor,
        Json $serializer
    ) {
        $this->queryProcessor = $queryProcessor;
        $this->serializer = $serializer;
    }

    /**
     * @param array $values
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function get(array $values): array
    {
        foreach ($values as $value) {
            $queryArguments['entityIds'][] = $value['id'];
        }
        $output = [];
        $cursor = $this->queryProcessor->execute('salesOrderItems', $queryArguments);
        while ($row = $cursor->fetch()) {
            $output[] = [
                'id' => $row['order_id'],
                'items' => $this->processRow($row)
            ];
        }
        return $output;
    }

    /**
     * @param array $row
     * @return array
     */
    private function processRow(array $row) : array {
        $row['items_shipped_together'] = false;
        if ($row['product_type'] == self::PRODUCT_TYPE_BUNDLE) {
            $productOptions = $this->serializer->unserialize($row['product_options']);
            $row['items_shipped_together'] = (bool) $productOptions['shipment_type'] == 0;
        }
        if ($row['product_type'] == self::PRODUCT_TYPE_CONFIGURABLE) {
            $row['items_shipped_together'] = true;
        }
        return $row;
    }
}
