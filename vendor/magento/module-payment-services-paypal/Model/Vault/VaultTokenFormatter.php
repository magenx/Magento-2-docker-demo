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

use Magento\PaymentServicesPaypal\Api\Data\VaultCardBillingAddressInterface;
use Magento\PaymentServicesPaypal\Api\Data\VaultCardBillingAddressInterfaceFactory;
use Magento\PaymentServicesPaypal\Api\Data\VaultCardDetailsInterface;
use Magento\PaymentServicesPaypal\Api\Data\VaultCardDetailsInterfaceFactory;
use Magento\PaymentServicesPaypal\Api\Data\VaultPaymentSourceDetailsInterface;
use Magento\PaymentServicesPaypal\Api\Data\VaultPaymentSourceDetailsInterfaceFactory;

class VaultTokenFormatter
{
    private const UNKNOWN_TYPE = 'UNKNOWN';

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
     * @param VaultPaymentSourceDetailsInterfaceFactory $vaultPaymentSourceDetailsFactory
     * @param VaultCardDetailsInterfaceFactory $vaultCardDetailsFactory
     * @param VaultCardBillingAddressInterfaceFactory $vaultCardBillingAddressFactory
     */
    public function __construct(
        VaultPaymentSourceDetailsInterfaceFactory       $vaultPaymentSourceDetailsFactory,
        VaultCardDetailsInterfaceFactory                $vaultCardDetailsFactory,
        VaultCardBillingAddressInterfaceFactory         $vaultCardBillingAddressFactory,
    ) {
        $this->vaultPaymentSourceDetailsFactory = $vaultPaymentSourceDetailsFactory;
        $this->vaultCardDetailsFactory = $vaultCardDetailsFactory;
        $this->vaultCardBillingAddressFactory = $vaultCardBillingAddressFactory;
    }

    /**
     * Transform the data received by createPaymentToken endpoint to a VaultPaymentSourceDetailsInterface object
     *
     * @param array $data
     * @return VaultPaymentSourceDetailsInterface
     */
    public function buildVaultPaymentSourceDetails(array $data): VaultPaymentSourceDetailsInterface
    {
        /** @var VaultCardBillingAddressInterface $vaultBillingAddress */
        $vaultBillingAddress = $this->vaultCardBillingAddressFactory->create();
        $billingAddress = $data['payment_source']['card']['billing_address'];

        if (!empty($billingAddress)) {
            $vaultBillingAddress->setAddressLine1($billingAddress['address_line_1'] ?? '');
            $vaultBillingAddress->setAddressLine2($billingAddress['address_line_2'] ?? '');
            $vaultBillingAddress->setRegion($billingAddress['admin_area_1'] ?? '');
            $vaultBillingAddress->setCity($billingAddress['admin_area_2'] ?? '');
            $vaultBillingAddress->setPostalCode($billingAddress['postal_code'] ?? '');
            $vaultBillingAddress->setCountryCode($billingAddress['country_code'] ?? '');
        }

        /** @var VaultCardDetailsInterface $cardDetails */
        $cardDetails = $this->vaultCardDetailsFactory->create();
        $cardDetails->setBrand($data['payment_source']['card']['brand']);
        $cardDetails->setType($data['payment_source']['card']['type'] ?? self::UNKNOWN_TYPE);
        $cardDetails->setLastDigits($data['payment_source']['card']['last_digits']);
        $cardDetails->setExpiry($data['payment_source']['card']['expiry']);
        $cardDetails->setBillingAddress($vaultBillingAddress);
        $cardDetails->setCardholderName($data['payment_source']['card']['name']);

        /** @var VaultPaymentSourceDetailsInterface $paymentSource */
        $paymentSource = $this->vaultPaymentSourceDetailsFactory->create();
        $paymentSource->setCard($cardDetails);

        return $paymentSource;
    }
}
