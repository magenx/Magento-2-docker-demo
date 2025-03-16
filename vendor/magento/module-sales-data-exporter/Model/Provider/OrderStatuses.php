<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesDataExporter\Model\Provider;

use Magento\QueryXml\Model\QueryProcessor;

class OrderStatuses
{
    /**
     * @var QueryProcessor
     */
    private $queryProcessor;

    public function __construct(
        QueryProcessor $queryProcessor
    )
    {
        $this->queryProcessor = $queryProcessor;
    }

    /**
     * @param array $values
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function get(array $values): array
    {
        foreach ($values as $value) {
            $queryArguments['statuses'][] = $value['status'];
        }
        $output = [];
        $cursor = $this->queryProcessor->execute('salesOrderStatuses', $queryArguments);
        while ($row = $cursor->fetch()) {
            $output[] = $row;
        }
        return $output;
    }
}
