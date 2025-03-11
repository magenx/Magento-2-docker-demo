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

namespace Magento\SaaSCommon\Model\Http;

use Magento\DataExporter\Status\ExportStatusCodeProvider;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\SaaSCommon\Model\Logging\SaaSExportLoggerInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Parse Feed Service API response
 */
class ResponseParser
{
    private SerializerInterface $serializer;
    private SaaSExportLoggerInterface $logger;
    private string $errorItemsField;

    /**
     * @param SerializerInterface $serializer
     * @param SaaSExportLoggerInterface $logger
     */
    public function __construct(
        SerializerInterface $serializer,
        SaaSExportLoggerInterface $logger,
        string $errorItemsField = 'invalidFeedItems') {
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->errorItemsField = $errorItemsField;
    }

    /**
     * Parse data
     *
     * @param ResponseInterface $response
     * @return array
     */
    public function parse(ResponseInterface $response): array
    {
        $failedItems = [];
        try {
            $responseText = $response->getBody()->getContents();
            $json = $this->serializer->unserialize($responseText);
            if (isset($json[$this->errorItemsField])) {
                foreach ($json[$this->errorItemsField] as $item) {
                    $parsedItem = $this->parseItem($item);
                    if (!$parsedItem) {
                        return  [];
                    }
                    [$index, $field, $message] = $parsedItem;
                    //  TODO: combine in message all errors for each item
                    if (!isset($failedItems[$index])) {
                        $failedItems[$index] = [
                            'message' => $message,
                            'field' => $field,
                            'status' => ExportStatusCodeProvider::FAILED_ITEM_ERROR
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            $this->logger->warning(
                'Cannot parse response. API request was not successful.',
                [
                    'response' => $responseText ?? 'read error',
                    'error' => $e->getMessage()
                ]
            );
        }
        return $failedItems;
    }

    /**
     * Parse item
     *
     * @param array $item
     * @return ?array
     */
    private function parseItem(array $item)
    {
        //  parse {"field": "/2/updatedAt",}
        if (isset($item['field'], $item['message'])) {
            $field = $item['field'] ?? '';
            $field = explode('/', $field);
            if (count($field) >= 3) {
                return [$field[1], $field[2], $item['message']];
            } else {
                // can't determine index.
                // TODO: main method must return [] if index not determined (all items have to be resubmitted)
                return null;
            }
        }

        if (isset($item['itemIndex'], $item['code'], $item['message'])) {
            return [$item['itemIndex'], $item['code'], $item['message']];
        }

        return null;
    }
}
