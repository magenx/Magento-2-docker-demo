<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\StoreDataExporter\Model\Provider;

use Magento\QueryXml\Model\QueryProcessor;

class Stores
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
            $queryArguments['websiteIds'][] = $value['store_view_id'];
        }
        $output = [];
        $cursor = $this->queryProcessor->execute('stores', $queryArguments);
        while ($row = $cursor->fetch()) {
            $output[] = $row;
        }
        return $output;
    }
}
