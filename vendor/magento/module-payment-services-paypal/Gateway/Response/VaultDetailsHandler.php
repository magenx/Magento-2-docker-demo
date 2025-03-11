<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Gateway\Response;

use Exception;
use Magento\Payment\Gateway\Data\AddressAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\PaymentServicesPaypal\Api\Data\VaultCardBillingAddressInterface;
use Magento\PaymentServicesPaypal\Api\Data\VaultCardBillingAddressInterfaceFactory;
use Magento\PaymentServicesPaypal\Api\Data\VaultCardDetailsInterface;
use Magento\PaymentServicesPaypal\Api\Data\VaultCardDetailsInterfaceFactory;
use Magento\PaymentServicesPaypal\Api\Data\VaultPaymentSourceDetailsInterface;
use Magento\PaymentServicesPaypal\Api\Data\VaultPaymentSourceDetailsInterfaceFactory;
use Magento\PaymentServicesPaypal\Model\Vault\VaultTokenProvider;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class VaultDetailsHandler implements HandlerInterface
{
    private const UNKNOWN_TYPE = 'UNKNOWN';

    /**
     * @var VaultTokenProvider
     */
    private VaultTokenProvider $vaultTokenProvider;

    /**
     * @var OrderPaymentExtensionInterfaceFactory
     */
    private OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory;

    /**
     * @var VaultPaymentSourceDetailsInterfaceFactory
     */
    private VaultPaymentSourceDetailsInterfaceFactory $vaultPaymentSourceDetailsFactory;

    /**
     * @var VaultCardDetailsInterfaceFactory
     */
    private VaultCardDetailsInterfaceFactory $vaultCardDetailsFactory;

    /**
     * @var VaultCardBillingAddressInterfaceFactory
     */
    private VaultCardBillingAddressInterfaceFactory $vaultCardBillingAddressFactory;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param VaultTokenProvider $vaultTokenProvider
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     * @param VaultPaymentSourceDetailsInterfaceFactory $vaultPaymentSourceDetailsFactory
     * @param VaultCardDetailsInterfaceFactory $vaultCardDetailsFactory
     * @param VaultCardBillingAddressInterfaceFactory $vaultCardBillingAddressFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        VaultTokenProvider $vaultTokenProvider,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        VaultPaymentSourceDetailsInterfaceFactory $vaultPaymentSourceDetailsFactory,
        VaultCardDetailsInterfaceFactory $vaultCardDetailsFactory,
        VaultCardBillingAddressInterfaceFactory $vaultCardBillingAddressFactory,
        LoggerInterface $logger
    ) {
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->vaultTokenProvider = $vaultTokenProvider;
        $this->vaultPaymentSourceDetailsFactory = $vaultPaymentSourceDetailsFactory;
        $this->vaultCardDetailsFactory = $vaultCardDetailsFactory;
        $this->vaultCardBillingAddressFactory = $vaultCardBillingAddressFactory;
        $this->logger = $logger;
    }

    /**
     * Handles vault save
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     * @throws Exception
     */
    public function handle(array $handlingSubject, array $response): void
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        if (isset($response['mp-transaction']['vault'])) {
            $vault = $response['mp-transaction']['vault'];
            $paymentDO = $handlingSubject['payment'];
            $payment = $paymentDO->getPayment();

            try {
                $billingAddress = $paymentDO->getOrder()->getBillingAddress();
                $vaultData = $this->buildVaultCardDetails($vault['provider-vault-details'], $billingAddress);

                $paymentToken = $this->vaultTokenProvider->createPaymentToken(
                    $vaultData,
                    $vault['vault-token-id'],
                    (int) $paymentDO->getOrder()->getCustomerId(),
                    (int) $paymentDO->getOrder()->getStoreId(),
                );

                $extensionAttributes = $this->getExtensionAttributes($payment);
                $extensionAttributes->setVaultPaymentToken($paymentToken);
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }

    /**
     * Get payment extension attributes
     *
     * @param InfoInterface $payment
     * @return OrderPaymentExtensionInterface
     */
    private function getExtensionAttributes(InfoInterface $payment): OrderPaymentExtensionInterface
    {
        $extensionAttributes = $payment->getExtensionAttributes();
        if (null === $extensionAttributes) {
            $extensionAttributes = $this->paymentExtensionFactory->create();
            $payment->setExtensionAttributes($extensionAttributes);
        }
        return $extensionAttributes;
    }

    /**
     * Build a VaultPaymentSourceDetailsInterface object
     *
     * @param array $vaultDetails
     * @param AddressAdapterInterface $billingAddress
     *
     * @return VaultPaymentSourceDetailsInterface
     */
    public function buildVaultCardDetails(
        array $vaultDetails,
        AddressAdapterInterface $billingAddress
    ): VaultPaymentSourceDetailsInterface {
        /** @var VaultCardBillingAddressInterface $vaultBillingAddress */
        $vaultBillingAddress = $this->vaultCardBillingAddressFactory->create();
        $vaultBillingAddress->setAddressLine1($billingAddress->getStreetLine1() ?? '');
        $vaultBillingAddress->setAddressLine2($billingAddress->getStreetLine2() ?? '');
        $vaultBillingAddress->setRegion($billingAddress->getRegionCode() ?? '');
        $vaultBillingAddress->setCity($billingAddress->getCity() ?? '');
        $vaultBillingAddress->setPostalCode($billingAddress->getPostcode() ?? '');
        $vaultBillingAddress->setCountryCode($billingAddress->getCountryId() ?? '');

        /** @var VaultCardDetailsInterface $cardDetails */
        $cardDetails = $this->vaultCardDetailsFactory->create();
        $cardDetails->setCardholderName(
            sprintf("%s %s", $billingAddress->getFirstname(), $billingAddress->getLastname())
        );
        $cardDetails->setBrand($vaultDetails['brand']);
        $cardDetails->setType($vaultDetails['type'] ?? self::UNKNOWN_TYPE);
        $cardDetails->setLastDigits($vaultDetails['last_digits']);
        $cardDetails->setExpiry($vaultDetails['expiry']);
        $cardDetails->setBillingAddress($vaultBillingAddress);

        /** @var VaultPaymentSourceDetailsInterface $paymentSource */
        $paymentSource = $this->vaultPaymentSourceDetailsFactory->create();
        $paymentSource->setCard($cardDetails);

        return $paymentSource;
    }
}
