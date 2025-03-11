<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Gateway\Command;

use Braintree\Result\Successful;
use InvalidArgumentException;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;
use PayPal\Braintree\Gateway\Helper\SubjectReader;
use PayPal\Braintree\Gateway\Request\CustomerDataBuilder;
use PayPal\Braintree\Gateway\Validator\GeneralResponseValidator;
use PayPal\Braintree\Model\Adapter\BraintreeAdapter;
use PayPal\Braintree\Model\Adapter\BraintreeSearchAdapter;

class FindOrCreateCustomerCommand implements CommandInterface
{
    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

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
     * @var BraintreeSearchAdapter
     */
    private BraintreeSearchAdapter $braintreeSearchAdapter;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param SubjectReader $subjectReader
     * @param GeneralResponseValidator $responseValidator
     * @param BraintreeAdapter $braintreeAdapter
     * @param BraintreeSearchAdapter $braintreeSearchAdapter
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        SubjectReader $subjectReader,
        GeneralResponseValidator $responseValidator,
        BraintreeAdapter $braintreeAdapter,
        BraintreeSearchAdapter $braintreeSearchAdapter
    ) {
        $this->customerRepository = $customerRepository;
        $this->subjectReader = $subjectReader;
        $this->responseValidator = $responseValidator;
        $this->braintreeAdapter = $braintreeAdapter;
        $this->braintreeSearchAdapter = $braintreeSearchAdapter;
    }

    /**
     * Executes command basing on business object.
     *
     * Find or Create Braintree Customer and return their ID.
     *
     * @param array $commandSubject
     * @return string|null
     * @throws CommandException
     * @throws LocalizedException
     */
    public function execute(array $commandSubject): ?string
    {
        $customerId = $this->subjectReader->readCustomerId($commandSubject);

        try {
            $customer = $this->customerRepository->getById($customerId);
        } catch (NoSuchEntityException $ex) {
            throw new CommandException(__($ex->getMessage()));
        }

        $this->validateCustomer($customer);

        $results = $this->braintreeAdapter->searchCustomers([
            $this->braintreeSearchAdapter->customerEmail()->is($customer->getEmail()),
            $this->braintreeSearchAdapter->customerFirstName()->is($customer->getFirstname()),
            $this->braintreeSearchAdapter->customerLastName()->is($customer->getLastname())
        ]);

        $result = $results->firstItem();

        if ($result === null) {
            $result = $this->braintreeAdapter->createCustomer([
                CustomerDataBuilder::FIRST_NAME => $customer->getFirstname(),
                CustomerDataBuilder::LAST_NAME => $customer->getLastname(),
                CustomerDataBuilder::EMAIL => $customer->getEmail()
            ]);
            $responseSubject = ['response' => ['object' => $result]];
        } else {
            $responseSubject['response']['object'] = new Successful(['customer' => $result]);
        }

        // Validate result
        $validationResult = $this->responseValidator->validate($responseSubject);

        if (!$validationResult->isValid()) {
            throw new LocalizedException(__(implode(PHP_EOL, $validationResult->getFailsDescription())));
        }

        try {
            $customer = $this->subjectReader->readCustomer($responseSubject['response']);
        } catch (InvalidArgumentException $ex) {
            throw new CommandException(__($ex->getMessage()));
        }

        return $customer->id ?? null;
    }

    /**
     * Validate Customer
     *
     * @param CustomerInterface $customer
     * @return void
     * @throws CommandException
     */
    private function validateCustomer(CustomerInterface $customer): void
    {
        if ($customer->getEmail() === null
            || $customer->getFirstname() === null
            || $customer->getLastname() === null
            || trim($customer->getEmail()) === ''
            || trim($customer->getFirstname()) === ''
            || trim($customer->getLastname()) === ''
        ) {
            throw new CommandException(__('Invalid Customer'));
        }
    }
}
