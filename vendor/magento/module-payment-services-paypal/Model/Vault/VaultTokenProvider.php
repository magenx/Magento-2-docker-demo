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

namespace Magento\PaymentServicesPaypal\Model\Vault;

use Exception;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\PaymentServicesPaypal\Api\Data\VaultPaymentSourceDetailsInterface;
use Magento\PaymentServicesPaypal\Model\Ui\ConfigProvider;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;

class VaultTokenProvider
{
    private const MAX_DESCRIPTION_LENGTH = 40;

    /**
     * @var PaymentTokenFactoryInterface
     */
    private PaymentTokenFactoryInterface $paymentTokenFactory;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var EncryptorInterface
     */
    private EncryptorInterface $encryptor;

    /**
     * @var Json
     */
    private Json $serializer;

    /**
     * @param PaymentTokenFactoryInterface $tokenFactory
     * @param StoreManagerInterface $storeManager
     * @param EncryptorInterface $encryptor
     * @param Json $serializer
     */
    public function __construct(
        PaymentTokenFactoryInterface    $tokenFactory,
        StoreManagerInterface           $storeManager,
        EncryptorInterface              $encryptor,
        Json                            $serializer,
    ) {
        $this->paymentTokenFactory = $tokenFactory;
        $this->storeManager = $storeManager;
        $this->encryptor = $encryptor;
        $this->serializer = $serializer;
    }

    /**
     * Build a vault token with the billingAddress and cardDescription
     *
     * @param VaultPaymentSourceDetailsInterface $paymentSourceDetails
     * @param string $mpVaultTokenId
     * @param int $customerId
     * @param int $storeId
     * @param string $cardDescription
     *
     * @return PaymentTokenInterface
     * @throws LocalizedException
     * @throws Exception
     */
    public function createPaymentToken(
        VaultPaymentSourceDetailsInterface $paymentSourceDetails,
        string $mpVaultTokenId,
        int $customerId,
        int $storeId,
        string $cardDescription = ""
    ): PaymentTokenInterface {
        $token = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);
        $token->setCustomerId($customerId);
        $token->setWebsiteId((int)$this->storeManager->getStore($storeId)->getWebsiteId());

        $token->setGatewayToken($mpVaultTokenId);
        $token->setPaymentMethodCode(ConfigProvider::CC_CODE);
        $token->setIsActive(true);
        $token->setIsVisible(true);
        $token->setExpiresAt($this->getTokenExpiryDate($paymentSourceDetails->getCard()->getExpiry()));

        // Set the token details and generate hash without the description
        $tokenDetails = $this->getCardPaymentSourceDetails($paymentSourceDetails);
        $publicHash = $this->generatePublicHash($token, $this->serializer->serialize($tokenDetails));
        $token->setPublicHash($publicHash);

        // Add the card description if available to the token details
        if (!empty($cardDescription)) {
            $tokenDetails['description'] = mb_substr($cardDescription, 0, self::MAX_DESCRIPTION_LENGTH);
        }

        $token->setTokenDetails($this->serializer->serialize($tokenDetails));

        return $token;
    }

    /**
     * Format card expiry from Paypal to Commerce (ex: 2027-02 to 02/2027)
     *
     * @param string $cardExpiry
     * @return string
     * @throws Exception
     */
    private function formatCardExpiry(string $cardExpiry): string
    {
        try {
            $expiryDate = new \DateTime($cardExpiry . '-01');
            return $expiryDate->format('m/Y');
        } catch (Exception $e) {
            throw new Exception('Invalid card expiry date');
        }
    }

    /**
     * Generates a token expiry date based from the card expiry (ex: 2027-02)
     *
     * @param string $cardExpiry
     * @return string
     * @throws Exception
     */
    private function getTokenExpiryDate(string $cardExpiry): string
    {
        try {
            $expiryDate = new \DateTime($cardExpiry . '-01');

            // Add one month to the expiry date
            $expiryDate->add(new \DateInterval('P1M'));

            return $expiryDate->format('Y-m-d 00:00:00');
        } catch (Exception $e) {
            throw new Exception('Invalid card expiry date');
        }
    }

    /**
     * Filter out empty fields from an array
     *
     * @param array $data
     * @return array
     */
    private function filterEmptyFields(array $data): array
    {
        return array_filter($data, function ($value) {
            return $value !== '';
        });
    }

    /**
     * Get the payment token details from the card payment source details
     *
     * @param VaultPaymentSourceDetailsInterface $paymentSourceDetails
     * @return array
     * @throws Exception
     */
    private function getCardPaymentSourceDetails(VaultPaymentSourceDetailsInterface $paymentSourceDetails): array
    {
        return [
            'type' => $paymentSourceDetails->getCard()->getType(),
            'brand' => $paymentSourceDetails->getCard()->getBrand(),
            'maskedCC' => $paymentSourceDetails->getCard()->getLastDigits(),
            'expirationDate' => $this->formatCardExpiry($paymentSourceDetails->getCard()->getExpiry()),
            'cardholderName' => $paymentSourceDetails->getCard()->getCardholderName(),
            'billingAddress' => $this->filterEmptyFields(
                $paymentSourceDetails->getCard()->getBillingAddress()->toArray()
            ),
        ];
    }

    /**
     * Generate vault payment public hash
     *
     * Ensure consistency in hash generation for checkout and vault without purchase by removing description and
     * billing address from the token details before generating the hash.
     * However, in checkout, the card type is used to generate the hash as part of the token details but the type
     * is unavailable from Paypal API when vaulting without purchase.
     *
     * Which means the same card will have different hashes when saved from checkout or vault without purchase.
     *
     * @param PaymentTokenInterface $paymentToken
     * @param string $tokenDetails
     * @return string
     */
    private function generatePublicHash(PaymentTokenInterface $paymentToken, string $tokenDetails): string
    {
        $hashKey = $paymentToken->getGatewayToken();

        if ($paymentToken->getCustomerId()) {
            $hashKey = $paymentToken->getCustomerId();
        }

        $hashKey .= $paymentToken->getPaymentMethodCode()
            . $paymentToken->getType()
            . $tokenDetails;

        return $this->encryptor->getHash($hashKey);
    }
}
