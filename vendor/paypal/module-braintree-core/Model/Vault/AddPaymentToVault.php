<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\Vault;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use PayPal\Braintree\Api\Data\PaymentInterface;
use PayPal\Braintree\Gateway\Command\CreatePaymentMethodCommand;
use PayPal\Braintree\Gateway\Command\FindOrCreateCustomerCommand;
use PayPal\Braintree\Gateway\Data\Customer\AddressAdapterFactory;
use PayPal\Braintree\Gateway\Data\Vault\PaymentAdapterFactory;
use PayPal\Braintree\Model\Adapter\PaymentMethod\PaymentTokenAdapterInterface;
use PayPal\Braintree\Model\Vault\PaymentToken\GeneratePublicHashInterface;
use PayPal\Braintree\Model\Vault\PaymentToken\SaveInterface;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AddPaymentToVault implements AddPaymentToVaultInterface
{
    /**
     * @var PaymentTokenFactoryInterface
     */
    private PaymentTokenFactoryInterface $paymentTokenFactory;

    /**
     * @var IsAddPaymentToVaultEnabledInterface
     */
    private IsAddPaymentToVaultEnabledInterface $isAddPaymentToVaultEnabled;

    /**
     * @var CreatePaymentMethodCommand
     */
    private CreatePaymentMethodCommand $createPaymentMethodCommand;

    /**
     * @var FindOrCreateCustomerCommand
     */
    private FindOrCreateCustomerCommand $findOrCreateCustomerCommand;

    /**
     * @var AddressAdapterFactory
     */
    private AddressAdapterFactory $addressAdapterFactory;

    /**
     * @var PaymentAdapterFactory
     */
    private PaymentAdapterFactory $paymentAdapterFactory;

    /**
     * @var GeneratePublicHashInterface
     */
    private GeneratePublicHashInterface $generatePublicHash;

    /**
     * @var SaveInterface
     */
    private SaveInterface $save;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param PaymentTokenFactoryInterface $paymentTokenFactory
     * @param IsAddPaymentToVaultEnabledInterface $isAddPaymentToVaultEnabled
     * @param CreatePaymentMethodCommand $createPaymentMethodCommand
     * @param FindOrCreateCustomerCommand $findOrCreateCustomerCommand
     * @param AddressAdapterFactory $addressAdapterFactory
     * @param PaymentAdapterFactory $paymentAdapterFactory
     * @param GeneratePublicHashInterface $generatePublicHash
     * @param SaveInterface $save
     * @param LoggerInterface $logger
     */
    public function __construct(
        PaymentTokenFactoryInterface $paymentTokenFactory,
        IsAddPaymentToVaultEnabledInterface $isAddPaymentToVaultEnabled,
        CreatePaymentMethodCommand $createPaymentMethodCommand,
        FindOrCreateCustomerCommand $findOrCreateCustomerCommand,
        AddressAdapterFactory $addressAdapterFactory,
        PaymentAdapterFactory $paymentAdapterFactory,
        GeneratePublicHashInterface $generatePublicHash,
        SaveInterface $save,
        LoggerInterface $logger
    ) {
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->isAddPaymentToVaultEnabled = $isAddPaymentToVaultEnabled;
        $this->createPaymentMethodCommand = $createPaymentMethodCommand;
        $this->findOrCreateCustomerCommand = $findOrCreateCustomerCommand;
        $this->addressAdapterFactory = $addressAdapterFactory;
        $this->paymentAdapterFactory = $paymentAdapterFactory;
        $this->generatePublicHash = $generatePublicHash;
        $this->save = $save;
        $this->logger = $logger;
    }

    /**
     * Vault a Payment nonce for a customer.
     *
     * Billing address is optional but advised for Card vaulting.
     *
     * @param CustomerInterface $customer
     * @param PaymentInterface $payment
     * @param AddressInterface|null $address
     * @param int|null $storeId
     * @return bool
     * @throws LocalizedException
     */
    public function execute(
        CustomerInterface $customer,
        PaymentInterface $payment,
        ?AddressInterface $address = null,
        ?int $storeId = null
    ): bool {
        if (!$this->isPaymentMethodVaultEnabled($payment, $storeId)) {
            return false;
        }

        try {
            $braintreeCustomerId = $this->findOrCreateCustomerCommand->execute([
                'customer_id' => (int) $customer->getId()
            ]);

            $result = $this->createPaymentMethodCommand->execute([
                'braintreeCustomerId' => $braintreeCustomerId,
                'paymentMethodData' => $this->paymentAdapterFactory->create(['payment' => $payment]),
                'addressData' => $address === null
                    ? null
                    : $this->addressAdapterFactory->create(['address' => $address]),
                'storeId' => $storeId
            ]);
        } catch (CommandException $ex) {
            $this->logger->error(
                'Failed to create Braintree customer Payment method: ' . $ex->getMessage(),
                [
                    'class' => AddPaymentToVault::class,
                    'customerId' => $customer->getId()
                ]
            );

            return false;
        }

        if (empty($result) || !isset($result['paymentMethod'])) {
            $this->logger->error('There is no Payment Method result available from Braintree', [
                'class' => AddPaymentToVault::class,
                'customerId' => $customer->getId(),
                'paymentMethodCode' => $payment->getPaymentMethodCode()
            ]);

            return false;
        }

        return $this->save->execute($this->createPaymentToken($customer, $result['paymentMethod']));
    }

    /**
     * Check whether payment method vault is enabled or not
     *
     * @param PaymentInterface $payment
     * @param int|null $storeId
     * @return bool
     */
    private function isPaymentMethodVaultEnabled(PaymentInterface $payment, ?int $storeId): bool
    {
        return $this->isAddPaymentToVaultEnabled->execute(
            $payment->getPaymentMethodCode() ?? '',
            $storeId
        );
    }

    /**
     * Create payment token
     *
     * @param CustomerInterface $customer
     * @param PaymentTokenAdapterInterface $paymentTokenAdapter
     * @return PaymentTokenInterface
     */
    private function createPaymentToken(
        CustomerInterface $customer,
        PaymentTokenAdapterInterface $paymentTokenAdapter
    ): PaymentTokenInterface {
        $paymentToken = $this->paymentTokenFactory->create($paymentTokenAdapter->getType());

        $paymentToken->setPaymentMethodCode($paymentTokenAdapter->getPaymentMethodCode());
        $paymentToken->setGatewayToken($paymentTokenAdapter->getGatewayToken());
        $paymentToken->setCustomerId((int) $customer->getId());
        $paymentToken->setIsActive(true);
        $paymentToken->setIsVisible(true);
        $paymentToken->setTokenDetails($paymentTokenAdapter->getTokenDetails());
        $paymentToken->setExpiresAt($paymentTokenAdapter->getExpiresAt());

        $publicHash = $this->generatePublicHash->execute($paymentToken);

        $paymentToken->setPublicHash($publicHash);

        return $paymentToken;
    }
}
