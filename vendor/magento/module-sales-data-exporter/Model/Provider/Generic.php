<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesDataExporter\Model\Provider;

use Magento\QueryXml\Model\QueryProcessor;

class Generic
{
    /**
     * @var QueryProcessor
     */
    private $queryProcessor;

    /**
     * @var string
     */
    private $queryName;

    /**
     * @var array
     */
    private $keyNames;

    /**
     * @param QueryProcessor $queryProcessor
     * @param string $queryName
     * @param array $keyNames
     */
    public function __construct(
        QueryProcessor $queryProcessor,
        string $queryName,
        array $keyNames
    )
    {
        $this->queryProcessor = $queryProcessor;
        $this->queryName = $queryName;
        $this->keyNames = $keyNames;
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
        $cursor = $this->queryProcessor->execute($this->queryName, $queryArguments);
        while ($row = $cursor->fetch()) {
            $output[] = [
                'id' => $row[$this->keyNames['orderKeyName']],
                $this->keyNames['entitiesKeyName'] => $row
            ];
        }
        return $output;
    }
}
