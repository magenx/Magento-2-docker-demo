<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Gateway\Command;

use Braintree\CreditCard;
use Braintree\PayPalAccount;
use Exception;
use InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;
use PayPal\Braintree\Gateway\Data\AddressAdapterInterface;
use PayPal\Braintree\Gateway\Data\PaymentAdapterInterface;
use PayPal\Braintree\Gateway\Helper\SubjectReader;
use PayPal\Braintree\Gateway\Request\AddressDataBuilder;
use PayPal\Braintree\Gateway\Request\DeviceDataBuilder;
use PayPal\Braintree\Gateway\Request\PaymentDataBuilder;
use PayPal\Braintree\Gateway\Validator\GeneralResponseValidator;
use PayPal\Braintree\Model\Adapter\BraintreeAdapter;
use PayPal\Braintree\Model\Adapter\PaymentMethod\PaymentTokenAdapterFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreatePaymentMethodCommand implements CommandInterface
{
    /**
     * @var SubjectReader
     */
    private SubjectReader $subjectReader;

    /**
     * @var GeneralResponseValidator
     */
    private GeneralResponseValidator $responseValidator;

    /**
     * @var BraintreeAdapter
     */
    private BraintreeAdapter $braintreeAdapter;

    /**
     * @var PaymentTokenAdapterFactoryInterface
     */
    private PaymentTokenAdapterFactoryInterface $paymentTokenAdapterFactory;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param SubjectReader $subjectReader
     * @param GeneralResponseValidator $responseValidator
     * @param BraintreeAdapter $braintreeAdapter
     * @param PaymentTokenAdapterFactoryInterface $paymentTokenAdapterFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        SubjectReader $subjectReader,
        GeneralResponseValidator $responseValidator,
        BraintreeAdapter $braintreeAdapter,
        PaymentTokenAdapterFactoryInterface $paymentTokenAdapterFactory,
        LoggerInterface $logger
    ) {
        $this->subjectReader = $subjectReader;
        $this->responseValidator = $responseValidator;
        $this->braintreeAdapter = $braintreeAdapter;
        $this->paymentTokenAdapterFactory = $paymentTokenAdapterFactory;
        $this->logger = $logger;
    }

    /**
     * Executes command basing on business object.
     *
     * Find or Create Braintree Customer and return their ID.
     *
     * @param array $commandSubject
     * @return null|array
     * @throws CommandException
     * @throws LocalizedException
     */
    public function execute(array $commandSubject): ?array
    {
        $braintreeCustomerId = $this->subjectReader->readBraintreeCustomerId($commandSubject);

        try {
            $paymentData = $this->subjectReader->readPaymentMethodData($commandSubject);
            $addressData = $this->subjectReader->readAddressData($commandSubject);
        } catch (InvalidArgumentException $ex) {
            $this->logger->error('Failed to perform payment method create: ' . $ex->getMessage(), [
                'class' => CreatePaymentMethodCommand::class
            ]);

            throw new CommandException(__('Invalid arguments'));
        }

        $requestData = [
            PaymentDataBuilder::PAYMENT_METHOD_NONCE => $paymentData->getPaymentMethodNonce(),
            DeviceDataBuilder::DEVICE_DATA => $paymentData->getDeviceData(),
            PaymentDataBuilder::CUSTOMER_ID => $braintreeCustomerId
        ];

        if ($addressData !== null) {
            $requestData['billingAddress'] = $this->getAddress($addressData);
        }

        try {
            $responseSubject = [
                'response' => [
                    'object' => $this->braintreeAdapter->createPaymentMethod($requestData)
                ]
            ];
        } catch (Exception $ex) {
            throw new CommandException(__($ex->getMessage()));
        }

        // Validate result
        $validationResult = $this->responseValidator->validate($responseSubject);

        if (!$validationResult->isValid()) {
            throw new CommandException(__($validationResult->getFailsDescription()[0][0]->getText()));
        }

        try {
            $paymentMethod = $this->subjectReader->readPaymentMethod($responseSubject['response']);
        } catch (InvalidArgumentException $ex) {
            throw new CommandException(__($ex->getMessage()));
        }

        return $this->createResult($paymentMethod, $paymentData);
    }

    /**
     * Get the address data from the adapter.
     *
     * @param AddressAdapterInterface $addressAdapter
     * @return array
     */
    private function getAddress(AddressAdapterInterface $addressAdapter): array
    {
        $street = $addressAdapter->getStreet();
        $streetAddress = array_shift($street);
        $extendedAddress = $street !== null ? implode(', ', $street) : '';

        return [
            AddressDataBuilder::FIRST_NAME => $addressAdapter->getFirstname(),
            AddressDataBuilder::LAST_NAME => $addressAdapter->getLastname(),
            AddressDataBuilder::COMPANY => $addressAdapter->getCompany(),
            AddressDataBuilder::STREET_ADDRESS => $streetAddress,
            AddressDataBuilder::EXTENDED_ADDRESS => $extendedAddress,
            AddressDataBuilder::LOCALITY => $addressAdapter->getCity(),
            AddressDataBuilder::REGION => $addressAdapter->getRegionCode(),
            AddressDataBuilder::POSTAL_CODE => $addressAdapter->getPostcode(),
            AddressDataBuilder::COUNTRY_CODE => $addressAdapter->getCountryId()
        ];
    }

    /**
     * Get the generated result.
     *
     * We currently support only Braintree Cards & PayPal.
     *
     * @param CreditCard|PayPalAccount $paymentMethod
     * @param PaymentAdapterInterface $paymentData
     * @return array
     */
    private function createResult(
        CreditCard|PayPalAccount $paymentMethod,
        PaymentAdapterInterface $paymentData
    ): array {
        // Empty result if no object.
        if ($paymentMethod->token === null) {
            return ['paymentMethod' => null];
        }

        try {
            return [
                'paymentMethod' => $this->paymentTokenAdapterFactory->create(
                    $paymentData->getPaymentMethodCode(),
                    $paymentMethod
                )
            ];
        } catch (InvalidArgumentException $ex) {
            $this->logger->error('Failed to create payment token adapter: ' . $ex->getMessage(), [
                'class' => CreatePaymentMethodCommand::class,
                'payment_method_code' => $paymentData->getPaymentMethodCode()
            ]);
            return ['paymentMethod' => null];
        }
    }
}
