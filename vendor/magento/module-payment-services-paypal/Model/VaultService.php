<?php

/**
 * ADOBE CONFIDENTIAL
 *
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

namespace Magento\PaymentServicesPaypal\Model;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\PaymentServicesBase\Model\HttpException;
use Magento\PaymentServicesBase\Model\ServiceClientInterface;
use Magento\PaymentServicesPaypal\Model\Vault\VaultTokenFormatter;
use Magento\PaymentServicesPaypal\Model\Vault\VaultTokenProvider;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Model\PaymentTokenManagement;
use Psr\Log\LoggerInterface;

class VaultService
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var ServiceClientInterface
     */
    private ServiceClientInterface $httpClient;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    private PaymentTokenRepositoryInterface $tokenRepository;

    /**
     * @var VaultTokenProvider
     */
    private VaultTokenProvider $vaultTokenProvider;

    /**
     * @var VaultTokenFormatter
     */
    private VaultTokenFormatter $vaultTokenFormatter;

    /**
     * @var PaymentTokenManagement
     */
    private PaymentTokenManagement $paymentTokenManagement;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var Json
     */
    private Json $serializer;

    /**
     * @param Config $config
     * @param ServiceClientInterface $httpClient
     * @param PaymentTokenRepositoryInterface $tokenRepository
     * @param VaultTokenProvider $vaultTokenProvider
     * @param VaultTokenFormatter $vaultTokenFormatter
     * @param PaymentTokenManagement $paymentTokenManagement
     * @param LoggerInterface $logger
     * @param Json $serializer
     */
    public function __construct(
        Config                                          $config,
        ServiceClientInterface                          $httpClient,
        PaymentTokenRepositoryInterface                 $tokenRepository,
        VaultTokenProvider                              $vaultTokenProvider,
        VaultTokenFormatter                             $vaultTokenFormatter,
        PaymentTokenManagement                          $paymentTokenManagement,
        LoggerInterface                                 $logger,
        Json                                            $serializer
    ) {
        $this->config = $config;
        $this->httpClient = $httpClient;
        $this->tokenRepository = $tokenRepository;
        $this->vaultTokenProvider = $vaultTokenProvider;
        $this->vaultTokenFormatter = $vaultTokenFormatter;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->logger = $logger;
        $this->serializer = $serializer;
    }

    /**
     * Call checkout service to delete vaulted card
     *
     * @param PaymentTokenInterface $paymentToken
     * @param int $customerId
     * @return array
     */
    public function deleteVaultedCard(PaymentTokenInterface $paymentToken, int $customerId): array
    {
        $tokenId = $paymentToken->getGatewayToken();

        $uri = '/'
            . $this->config->getMerchantId()
            . '/vault/card';

        return $this->httpClient->request(
            [
                'x-token-id' => $tokenId,
                'x-commerce-customer-id' => $customerId
            ],
            $uri,
            Http::METHOD_DELETE
        );
    }

    /**
     * Mark payment token as invisible in Commerce DB
     *
     * @param PaymentTokenInterface $paymentToken
     * @return void
     */
    public function deleteVaultedCardFromCommerce(PaymentTokenInterface $paymentToken): void
    {
        $this->tokenRepository->delete($paymentToken);
    }

    /**
     * Call checkout service to create a vault setup token
     *
     * @param array $body
     *
     * @return array
     */
    public function createVaultCardSetupToken(array $body): array
    {
        $headers = [
            'Content-Type' => 'application/json',
        ];

        $body = json_encode($body);

        if (!$body) {
            $this->logger->error('Error encoding body for vault setup token request');
            throw new HttpException('Error encoding body for vault setup token request');
        }

        $uri = sprintf('/%s/paypal/vault/card/setup', $this->config->getMerchantId());

        $response = $this->httpClient->request(
            $headers,
            $uri,
            Http::METHOD_POST,
            $body,
        );

        $this->logger->debug(
            var_export(
                [
                    'request' => [
                        $uri,
                        $headers,
                        Http::METHOD_POST,
                        $body
                    ],
                    'response' => $response
                ],
                true
            )
        );

        if (!isset($response['is_successful']) || !$response['is_successful']) {
            throw new HttpException('Failed to create vault setup token.');
        }

        return $response;
    }

    /**
     * Call checkout service to create a vault payment token
     *
     * @param int $customerId
     * @param string $tokenId
     * @param string $cardDescription
     * @param int $storeId
     * @return array
     */
    public function createVaultCardPaymentToken(
        int $customerId,
        string $tokenId,
        string $cardDescription,
        int $storeId
    ): array {
        $headers = [
            'Content-Type' => 'application/json',
            'x-commerce-customer-id' => $customerId,
        ];

        $body = json_encode(
            [
                "provider-setup-token-id" => $tokenId,
            ]
        );

        if (!$body) {
            $this->logger->error('Error encoding body for vault card payment token request');
            throw new HttpException('Error encoding body for vault card payment token request');
        }

        $uri = sprintf('/%s/paypal/vault/card', $this->config->getMerchantId());

        $response = $this->httpClient->request(
            $headers,
            $uri,
            Http::METHOD_POST,
            $body,
        );

        if (!isset($response['is_successful']) || !$response['is_successful']) {
            throw new HttpException('Failed to create vault payment token.');
        }

        try {
            $paymentSourceDetails = $this->vaultTokenFormatter->buildVaultPaymentSourceDetails($response);

            $token = $this->vaultTokenProvider->createPaymentToken(
                $paymentSourceDetails,
                $response['mp-vault-token-id'],
                $customerId,
                $storeId,
                $cardDescription
            );

            try {
                $this->tokenRepository->save($token);
            } catch (AlreadyExistsException $e) {
                // If the token already exists, update its gateway token
                $existingToken = $this->getByPublicHashAndCustomerId($token->getPublicHash(), $customerId);
                $this->updatePaymentToken($existingToken, $response['mp-vault-token-id'], $cardDescription);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new HttpException('Failed to create the vault payment token');
        }

        return $response;
    }

    /**
     * Get payment token by public hash and customer ID
     *
     * @param string $publicHash
     * @param int $customerId
     * @return PaymentTokenInterface
     */
    private function getByPublicHashAndCustomerId(string $publicHash, int $customerId): PaymentTokenInterface
    {
        $token = $this->paymentTokenManagement->getByPublicHash($publicHash, $customerId);

        if (!$token) {
            $this->logger->error(
                'Failed to find token by public hash and customer id',
                [$publicHash, $customerId]
            );
            throw new HttpException('Failed to create the vault payment token: Duplicated token not found.');
        }

        return $token;
    }

    /**
     * Update payment token info if duplicated
     *
     * @param PaymentTokenInterface $existingToken
     * @param string $gatewayToken
     * @param string $cardDescription
     * @return void
     */
    private function updatePaymentToken(
        PaymentTokenInterface $existingToken,
        string $gatewayToken,
        string $cardDescription
    ): void {
        $existingToken->setGatewayToken($gatewayToken);
        $existingToken->setIsActive(true);
        $existingToken->setIsVisible(true);

        $details = $this->serializer->unserialize($existingToken->getTokenDetails());
        $details['description'] = $cardDescription;
        $existingToken->setTokenDetails($this->serializer->serialize($details));

        $this->tokenRepository->save($existingToken);
    }
}
