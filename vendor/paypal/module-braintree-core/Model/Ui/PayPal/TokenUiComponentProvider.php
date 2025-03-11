<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\Ui\PayPal;

use Braintree\Result\Error;
use Braintree\Result\Successful;
use InvalidArgumentException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use PayPal\Braintree\Gateway\Config\Config;
use PayPal\Braintree\Gateway\DataResolver\Customer\GetCustomerIdByPaymentTokenInterface;
use PayPal\Braintree\Gateway\Request\PaymentDataBuilder;
use PayPal\Braintree\Model\Adapter\BraintreeAdapter;
use Magento\Framework\UrlInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory;
use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;
use Psr\Log\LoggerInterface;

/**
 * PayPal token component provider for front-end UI
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TokenUiComponentProvider implements TokenUiComponentProviderInterface
{
    /**
     * @var TokenUiComponentInterfaceFactory
     */
    private TokenUiComponentInterfaceFactory $componentFactory;

    /**
     * @var Config
     */
    private Config $braintreeConfig;

    /**
     * @var GetCustomerIdByPaymentTokenInterface
     */
    private GetCustomerIdByPaymentTokenInterface $braintreeGetCustomerIdByPaymentToken;

    /**
     * @var BraintreeAdapter
     */
    private BraintreeAdapter $braintreeAdapter;

    /**
     * @var UrlInterface
     */
    private UrlInterface $urlBuilder;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param TokenUiComponentInterfaceFactory $componentFactory
     * @param Config $braintreeConfig
     * @param GetCustomerIdByPaymentTokenInterface $braintreeGetCustomerIdByPaymentToken
     * @param BraintreeAdapter $braintreeAdapter
     * @param UrlInterface $urlBuilder
     * @param SerializerInterface $serializer
     * @param LoggerInterface $logger
     * @return void
     */
    public function __construct(
        TokenUiComponentInterfaceFactory $componentFactory,
        Config $braintreeConfig,
        GetCustomerIdByPaymentTokenInterface $braintreeGetCustomerIdByPaymentToken,
        BraintreeAdapter $braintreeAdapter,
        UrlInterface $urlBuilder,
        SerializerInterface $serializer,
        LoggerInterface $logger
    ) {
        $this->componentFactory = $componentFactory;
        $this->braintreeConfig = $braintreeConfig;
        $this->braintreeGetCustomerIdByPaymentToken = $braintreeGetCustomerIdByPaymentToken;
        $this->braintreeAdapter = $braintreeAdapter;
        $this->urlBuilder = $urlBuilder;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * Get UI component for token
     *
     * @param PaymentTokenInterface $paymentToken
     * @return TokenUiComponentInterface
     */
    public function getComponentForToken(PaymentTokenInterface $paymentToken): TokenUiComponentInterface
    {
        try {
            $jsonDetails = $this->serializer->unserialize($paymentToken->getTokenDetails() ?: '{}');
        } catch (InvalidArgumentException $ex) {
            $this->logger->error('Failed to un-serialize PayPal token with error: ' . $ex->getMessage(), [
                'payment_token_entity_id' => $paymentToken->getEntityId(),
                'class' => TokenUiComponentProvider::class
            ]);

            $jsonDetails = null;
        }

        // Base config
        $config = [
            'code' => ConfigProvider::PAYPAL_VAULT_CODE,
            'nonceUrl' => $this->getNonceRetrieveUrl(),
            TokenUiComponentProviderInterface::COMPONENT_PUBLIC_HASH => $paymentToken->getPublicHash()
        ];

        // Get the Braintree Customer ID from token or token details.
        $braintreeCustomerId = $this->braintreeGetCustomerIdByPaymentToken->execute($paymentToken);

        // Unset Braintree Customer ID from token details, no error if it does not exist.
        unset($jsonDetails[PaymentDataBuilder::CUSTOMER_ID]);

        // Set the rest of the data.
        $config[TokenUiComponentProviderInterface::COMPONENT_DETAILS] = $jsonDetails;

        // If Braintree Customer ID is not set, use old functionality.
        if ($braintreeCustomerId === null) {
            return $this->getVaultComponent($config);
        }

        try {
            $config['clientToken'] = $this->getClientToken($braintreeCustomerId);
        } catch (InputException|NoSuchEntityException|InvalidArgumentException $ex) {
            $this->logger->error(
                'Failed to generate client token for stored token Customer ID with error: ' . $ex->getMessage(),
                [
                    'payment_token_entity_id' => $paymentToken->getEntityId()
                ]
            );
        }

        // If we have a client token, use the checkout widget template.
        // Otherwise, standard for backwards compatibility.
        return isset($config['clientToken'])
            ? $this->getCustomerVaultComponent($config)
            : $this->getVaultComponent($config);
    }

    /**
     * Get the component where we don't have the Braintree Customer ID associated token.
     *
     * @param array $config
     * @return TokenUiComponentInterface
     */
    private function getVaultComponent(array $config): TokenUiComponentInterface
    {
        return $this->componentFactory->create(
            [
                'config' => $config,
                'name' => 'PayPal_Braintree/js/view/payment/method-renderer/paypal-vault'
            ]
        );
    }

    /**
     * Get the component where for the Braintree Customer ID associated token.
     *
     * @param array $config
     * @return TokenUiComponentInterface
     */
    private function getCustomerVaultComponent(array $config): TokenUiComponentInterface
    {
        return $this->componentFactory->create(
            [
                'config' => $config,
                'name' => 'PayPal_Braintree/js/view/payment/method-renderer/paypal-customer-vault'
            ]
        );
    }

    /**
     * Get url to retrieve payment method nonce
     *
     * @return string|null
     */
    private function getNonceRetrieveUrl(): ?string
    {
        return $this->urlBuilder->getUrl('braintree/payment/getNonce', ['_secure' => true]);
    }

    /**
     * Generate a client token with the Braintree Customer ID.
     *
     * @param string $customerId
     * @return Error|Successful|string|null
     * @throws InputException
     * @throws NoSuchEntityException
     */
    private function getClientToken(string $customerId): Error|Successful|string|null
    {
        // Set Parameters.
        $params = [
            PaymentDataBuilder::CUSTOMER_ID => $customerId
        ];

        $merchantAccountId = $this->braintreeConfig->getMerchantAccountId();

        if (!empty($merchantAccountId)) {
            $params[PaymentDataBuilder::MERCHANT_ACCOUNT_ID] = $merchantAccountId;
        }

        return $this->braintreeAdapter->generate($params);
    }
}
