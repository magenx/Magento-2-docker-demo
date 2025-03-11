<?php
/**
 * Copyright 2024 Adobe
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

namespace Magento\SaaSCommon\Model\Http\Command;

use GuzzleHttp\Client;
use Laminas\Http\Request;
use Magento\DataExporter\Model\FeedExportStatus;
use Magento\DataExporter\Model\Indexer\Config as IndexerConfig;
use Magento\DataExporter\Status\ExportStatusCodeProvider;
use Magento\Framework\App\ObjectManager;
use Magento\SaaSCommon\Console\ProgressBarManager;
use Magento\SaaSCommon\Model\DataFilter;
use Magento\SaaSCommon\Model\Exception\UnableSendData;
use Magento\SaaSCommon\Model\Http\ResponseParser;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\SaaSCommon\Model\Http\Converter\Factory;
use Magento\SaaSCommon\Model\Http\ConverterInterface;
use Magento\SaaSCommon\Model\Metadata\RequestMetadataHeaderProvider;
use Magento\ServicesConnector\Api\ClientResolverInterface;
use Magento\ServicesId\Model\ServicesConfig;
use Magento\ServicesId\Model\ServicesConfigInterface;
use Magento\SaaSCommon\Model\Logging\SaaSExportLoggerInterface as LoggerInterface;
use Magento\DataExporter\Model\FeedExportStatusBuilder;

/**
 * Class responsible for call execution to SaaS Service
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SubmitFeed
{
    /**
     * Config paths
     */
    private const ENVIRONMENT_CONFIG_PATH = 'magento_saas/environment';

    /**
     * Extension name for Services Connector
     */
    private const EXTENSION_NAME = 'Magento_DataExporter';

    /**
     * @var ClientResolverInterface
     */
    private $clientResolver;

    /**
     * @var ConverterInterface
     */
    private $converter;

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @var ServicesConfigInterface
     */
    private $servicesConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var bool
     */
    private $extendedLog;

    /**
     * @var string[]
     */
    private $headers;

    private ResponseParser $responseParser;

    /**
     * @var FeedExportStatusBuilder
     */
    private FeedExportStatusBuilder $feedExportStatusBuilder;

    /**
     * @var RequestMetadataHeaderProvider
     */
    private RequestMetadataHeaderProvider $requestMetadataHeaderProvider;

    /**
     * @var IndexerConfig
     */
    private IndexerConfig $indexerConfig;

    /**
     * @var ProgressBarManager
     */
    private ProgressBarManager $progressBarManager;

    /**
     * @var FeedRouteResolverInterface
     */
    private FeedRouteResolverInterface $feedRouteResolver;

    /**
     * @param ClientResolverInterface $clientResolver
     * @param Factory $converterFactory
     * @param ScopeConfigInterface $config
     * @param ServicesConfigInterface $servicesConfig
     * @param LoggerInterface $logger
     * @param DataFilter $dataFilter
     * @param ResponseParser $responseParser
     * @param FeedExportStatusBuilder $feedExportStatusBuilder
     * @param RequestMetadataHeaderProvider $requestMetadataHeaderProvider
     * @param ?IndexerConfig $indexerConfig
     * @param ?ProgressBarManager $progressBarManager
     * @param ?FeedRouteResolverInterface $feedRouteResolver
     * @param bool $extendedLog
     * @param string[] $headers
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        ClientResolverInterface $clientResolver,
        Factory $converterFactory,
        ScopeConfigInterface $config,
        ServicesConfigInterface $servicesConfig,
        LoggerInterface $logger,
        DataFilter $dataFilter,
        ResponseParser $responseParser,
        FeedExportStatusBuilder $feedExportStatusBuilder,
        RequestMetadataHeaderProvider $requestMetadataHeaderProvider,
        ?IndexerConfig $indexerConfig = null,
        ?ProgressBarManager $progressBarManager = null,
        ?FeedRouteResolverInterface $feedRouteResolver = null,
        bool $extendedLog = false,
        array $headers = []
    ) {
        $this->clientResolver = $clientResolver;
        $this->converter = $converterFactory->create();
        $this->config = $config;
        $this->servicesConfig = $servicesConfig;
        $this->logger = $logger;
        $this->extendedLog = $extendedLog;
        $this->headers = $headers;
        $this->responseParser = $responseParser;
        $this->feedExportStatusBuilder = $feedExportStatusBuilder;
        $this->requestMetadataHeaderProvider = $requestMetadataHeaderProvider;
        $this->indexerConfig = $indexerConfig
            ?? ObjectManager::getInstance()->get(IndexerConfig::class);
        $this->progressBarManager = $progressBarManager
            ?? ObjectManager::getInstance()->get(ProgressBarManager::class);
        $this->feedRouteResolver = $feedRouteResolver
            ?? ObjectManager::getInstance()->get(FeedRouteResolverInterface::class);
    }

    /**
     * Execute call to SaaS Service
     * Returns status of operation:
     * - true: feed submitted successfully
     * - false: feed submitted unsuccessfully. Need to retry feed submission
     *
     * @param string $feedName
     * @param array $data
     * @param int|null $timeout
     * @return FeedExportStatus
     */
    public function execute(string $feedName, array $data, ?int $timeout = null) : FeedExportStatus
    {
        if (true === $this->indexerConfig->isDryRun()) {
            $this->logFeedData($feedName, $data);
            $this->progressBarManager->updateExportInfo(count($data), 0);
            return $this->feedExportStatusBuilder->build(
                ExportStatusCodeProvider::FEED_SUBMIT_SKIPPED,
                'Feed submission is skipped as executed in the "dummy" mode.'
            );
        }
        $environmentId = $this->servicesConfig->getEnvironmentId();
        try {
            $client = $this->clientResolver->createHttpClient(
                self::EXTENSION_NAME,
                $this->config->getValue(self::ENVIRONMENT_CONFIG_PATH)
            );

            $headers = $this->getHeaders();
            $this->logFeedData($feedName, $data);
            $body = $this->converter->toBody($data);
            $options = [
                'headers' => $headers,
                'body' => $body
            ];

            if (null !== $timeout) {
                $options['timeout'] = $timeout;
            }

            if ($this->servicesConfig->isApiKeySet()) {
                $response = $client->request(
                    Request::METHOD_POST,
                    $this->feedRouteResolver->getRoute($feedName),
                    $options
                );
                $failedItems = $this->responseParser->parse($response);
                $exportStatus = $this->feedExportStatusBuilder->build(
                    $response->getStatusCode(),
                    $response->getReasonPhrase(),
                    $failedItems
                );
                if (!$exportStatus->getStatus()->isSuccess()) {
                    $log = $this->prepareLog($client, $exportStatus, $feedName, $data, $environmentId);
                    $this->logger->error(
                        'Export error. API request was not successful.',
                        $log
                    );
                }
                $this->progressBarManager->updateExportInfo(
                    count($data),
                    count($failedItems),
                    $exportStatus->getStatus()->isSuccess()
                );
            } else {
                throw new UnableSendData('API Keys Validation Failed');
            }
        } catch (UnableSendData $exception) {
            $exportStatus = $this->feedExportStatusBuilder->build(
                ExportStatusCodeProvider::APPLICATION_ERROR,
                $exception->getMessage()
            );
            $this->logger->error(
                $exception->getMessage(),
                [
                    'exception' => $exception,
                    'feed' => $feedName
                ]
            );
        } catch (\Throwable $exception) {
            $exportStatus = $this->feedExportStatusBuilder->build(
                ExportStatusCodeProvider::APPLICATION_ERROR,
                $exception->getMessage()
            );
            $this->logger->error(
                $exception->getMessage(),
                [
                    'exception' => $exception,
                    'environment_id' => $environmentId,
                    'route' => $this->feedRouteResolver->getRoute($feedName),
                    'feed' => $feedName
                ]
            );
        }

        return $exportStatus;
    }

    /**
     * Prepare log formatting.
     *
     * @param Client $client
     * @param FeedExportStatus $feedExportStatus
     * @param string $feedName
     * @param array $payload
     * @param string|null $environmentId
     * @return array
     * @throws UnableSendData
     */
    private function prepareLog(
        Client $client,
        FeedExportStatus $feedExportStatus,
        string $feedName,
        array $payload,
        ?string $environmentId
    ): array {
        $clientConfig = $client->getConfig();

        $log = [
            'environment_id' => $environmentId,
            'status_code' => $feedExportStatus->getStatus()->getValue(),
            'feed' => $feedName,
            'reason' => $feedExportStatus->getReasonPhrase(),
            'route' => $this->feedRouteResolver->getRoute($feedName),
            'base_uri' => isset($clientConfig['base_uri'])
                ? $clientConfig['base_uri']->__toString() : 'base uri wasn\'t set',
            'failedItems' => $feedExportStatus->getFailedItems()
        ];

        if (true === $this->extendedLog) {
            $log['headers'] = $clientConfig['headers'] ?? 'no headers';
            $log['payload'] = $payload;
        }
        return $log;
    }

    /**
     * Create a list of headers for the feed submit request.
     *
     * @return array
     */
    private function getHeaders(): array
    {
        $headers = [
            'Content-Type' => $this->converter->getContentMediaType(),
            $this->requestMetadataHeaderProvider->getName() => $this->requestMetadataHeaderProvider->getValue()
        ];

        if (null !== $this->converter->getContentEncoding()) {
            $headers['Content-Encoding'] = $this->converter->getContentEncoding();
        }

        if (empty($this->headers)) {
            return $headers;
        }

        foreach ($this->headers as $headerName => $headerValue) {
            if (!empty($headerValue)) {
                $headers[$headerName] = $headerValue;
            }
        }

        return $headers;
    }

    /**
     * Save feed data to log
     *
     * @param string $feedName
     * @param array $data
     * @return void
     */
    private function logFeedData(string $feedName, array $data): void
    {
        if ($this->extendedLog) {
            $this->logger->info(json_encode(["feed" => $feedName, "data" => $data], JSON_PRETTY_PRINT));
        }
    }
}
