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

/**
 * Filter payload data before send it to the REST endpoint
 */
class DataFilter
{
    /**
     * list of fields in feed which should be excluded
     * @var array
     */
    private $reservedFields;

    /**
     * @param array $reservedFields
     */
    public function __construct(array $reservedFields = [])
    {
        $this->reservedFields = $reservedFields;
    }

    /**
     * @param $feedName
     * @param array $feeds
     * @return array
     */
    public function filter($feedName, array $feeds) : array
    {
        if (!empty($this->reservedFields[$feedName])) {
            foreach ($feeds as &$feedItem) {
                foreach ($feedItem as $field => $value) {
                    if (in_array($field, $this->reservedFields[$feedName], true)) {
                        unset($feedItem[$field]);
                    }
                }
            }
        }

        return $feeds;
    }
}
