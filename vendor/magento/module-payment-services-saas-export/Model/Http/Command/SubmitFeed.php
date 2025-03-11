<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesSaaSExport\Model\Http\Command;

use Laminas\Http\Request;
use Magento\Framework\App\ObjectManager;
use Magento\PaymentServicesBase\Model\Config;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\SaaSCommon\Model\Exception\UnableSendData;
use Magento\SaaSCommon\Model\Http\ConverterInterface;
use Magento\ServicesConnector\Api\ClientResolverInterface;
use Magento\ServicesConnector\Exception\KeyNotFoundException;
use Magento\ServicesConnector\Api\KeyValidationInterface;
use Magento\ServicesConnector\Exception\PrivateKeySignException;
use Magento\ServicesId\Model\ServicesConfigInterface;
use Magento\PaymentServicesBase\Model\OnboardingStatus;

/**
 * Class responsible for call execution to SaaS Payment Service
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SubmitFeed
{
    public const CONFIG_PATH_MERCHANT_ID = 'payment/payment_methods/%s_merchant_id';
    /**
     * Config paths
     */
    public const ROUTE_CONFIG_PATH = 'magento_saas/routes/';

    /**
     * Extension name for Services Connector
     */
    public const EXTENSION_NAME = 'Magento_DataExporter';

    /**
     * @var ClientResolverInterface
     */
    private $clientResolver;

    /**
     * @var KeyValidationInterface
     */
    private $keyValidator;

    /**
     * @var ConverterInterface
     */
    private $converter;

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var OnboardingStatus
     */
    private $onboardingStatus;

    /**
     * @var Config
     */
    private $paymentsConfig;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var FeedRouteResolverInterface
     */
    private FeedRouteResolverInterface $feedRouteResolver;

    /**
     * @param ClientResolverInterface $clientResolver
     * @param KeyValidationInterface $keyValidator
     * @param ConverterInterface $converter
     * @param ScopeConfigInterface $config
     * @param ServicesConfigInterface $servicesConfig
     * @param LoggerInterface $logger
     * @param OnboardingStatus $onboardingStatus
     * @param Config $paymentsConfig
     * @param string $environment
     * @param ?string $feedRoute
     * @param ?FeedRouteResolverInterface $feedRouteResolver
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        ClientResolverInterface $clientResolver,
        KeyValidationInterface $keyValidator,
        ConverterInterface $converter,
        ScopeConfigInterface $config,
        ServicesConfigInterface $servicesConfig,
        LoggerInterface $logger,
        OnboardingStatus $onboardingStatus,
        Config $paymentsConfig,
        string $environment,
        ?string $feedRoute = null,
        ?FeedRouteResolverInterface $feedRouteResolver = null
    ) {
        $this->clientResolver = $clientResolver;
        $this->keyValidator = $keyValidator;
        $this->converter = $converter;
        $this->config = $config;
        $this->logger = $logger;
        $this->onboardingStatus = $onboardingStatus;
        $this->paymentsConfig = $paymentsConfig;
        $this->environment = $environment;
        $this->feedRouteResolver = $feedRouteResolver
            ?? ObjectManager::getInstance()->get(FeedRouteResolverInterface::class);
    }

    /**
     * Execute call to SaaS Service
     *
     * @param string $feedName
     * @param array $data
     * @return bool
     * @throws UnableSendData|PrivateKeySignException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(string $feedName, array $data) : bool
    {
        $result = false;
        try {
            if (!$this->validateApiKey() || !$this->validateMerchantId()) {
                $this->logger->error('API Key or Merchant ID Validation Failed!');
                throw new UnableSendData('Unable to send data to service');
            }

            $client = $this->clientResolver->createHttpClient(
                self::EXTENSION_NAME,
                $this->environment
            );
            $headers = [
                'Content-Type' => $this->converter->getContentMediaType(),
                'x-request-user-agent' => sprintf('PaymentServices/%s', $this->paymentsConfig->getVersion())
            ];
            $data['environment'] = $this->environment;
            $body = $this->converter->toBody($data);
            $options = [
                'headers' => $headers,
                'body' => $body
            ];
            $response = $client->request(Request::METHOD_POST, $this->feedRouteResolver->getRoute($feedName), $options);
            $responseCode = $response->getStatusCode();
            $result = ($responseCode == 200);
            if (!$result) {
                $this->logger->error('API request was not successful.', [
                    'status_code' => $response->getStatusCode(),
                    'reason' => $response->getReasonPhrase()
                ]);
            }
            if ($responseCode === 401 || $responseCode === 403) {
                $this->onboardingStatus->setIsOnboarded($this->environment, false);
            }
        } catch (GuzzleException $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableSendData('Unable to send data to service');
        } catch (KeyNotFoundException $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableSendData('Unable to send data to service');
        }

        return $result;
    }

    /**
     * Validate the API Gateway Key
     *
     * @return bool
     * @throws KeyNotFoundException
     */
    private function validateApiKey() : bool
    {
        return $this->keyValidator->execute(
            self::EXTENSION_NAME,
            $this->environment
        );
    }

    /**
     * Validate payment merchant id.
     *
     * @return bool
     * @throws KeyNotFoundException
     */
    private function validateMerchantId() : bool
    {
        return (bool)$this->config->getValue(sprintf(self::CONFIG_PATH_MERCHANT_ID, $this->environment));
    }
}
