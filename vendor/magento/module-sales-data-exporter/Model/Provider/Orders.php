<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesDataExporter\Model\Provider;

use Magento\QueryXml\Model\QueryProcessor;

class Orders
{
    /**
     * @var QueryProcessor
     */
    private $queryProcessor;

    /**
     * @var array
     */
    private $methods;

    public function __construct(
        QueryProcessor $queryProcessor,
        $methods = []
    )
    {
        $this->methods = $methods;
        $this->queryProcessor = $queryProcessor;
    }

    /**
     * @param array $values
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function get(array $values): array
    {
        $queryArguments['methods'] = $this->methods;
        foreach ($values as $value) {
            $queryArguments['entityIds'][] = $value['id'];
        }
        $output = [];
        $cursor = $this->queryProcessor->execute('salesOrder', $queryArguments);
        while ($row = $cursor->fetch()) {
            $output[] = $row;
        }
        return $output;
    }
}
