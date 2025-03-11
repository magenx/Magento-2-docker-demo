<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Controller\Vault;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Phrase;
use Magento\PaymentServicesBase\Model\HttpException;
use Magento\PaymentServicesPaypal\Model\VaultService;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;

class DeleteCard implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var MessageManagerInterface
     */
    private $messageManager;

    /**
     * @var PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

    /**
     * @var Http
     */
    private $request;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var VaultService
     */
    private $vaultService;

    /**
     * @param Session $customerSession
     * @param MessageManagerInterface $messageManager
     * @param Http $request
     * @param ResultFactory $resultFactory
     * @param PaymentTokenManagementInterface $paymentTokenManagement
     * @param VaultService $vaultService
     */
    public function __construct(
        Session $customerSession,
        MessageManagerInterface $messageManager,
        Http $request,
        ResultFactory $resultFactory,
        PaymentTokenManagementInterface $paymentTokenManagement,
        VaultService $vaultService
    ) {
        $this->customerSession = $customerSession;
        $this->messageManager = $messageManager;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->request = $request;
        $this->resultFactory = $resultFactory;
        $this->vaultService = $vaultService;
    }

    /**
     * Delete a stored card by vault entity ID
     *
     * @return ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $customerId = $this->customerSession->getCustomerId();
        if ($customerId === null) {
            return $this->createErrorResponse(__('Not logged in'));
        }
        $customerId = (int) $customerId;

        $paymentToken = $this->getPaymentToken($this->request, $customerId);

        if ($paymentToken === null) {
            return $this->createErrorResponse(__('No token found.'));
        }

        try {
            $this->vaultService->deleteVaultedCardFromCommerce($paymentToken);
        } catch (HttpException $e) {
            return $this->createErrorResponse(__('Deletion failure. Please try again.'));
        }

        return $this->createSuccessMessage();
    }

    /**
     * Create an error message and redirect to vaulted card list
     *
     * @param Phrase $errorMessage
     * @return ResponseInterface
     */
    private function createErrorResponse(Phrase $errorMessage)
    {
        $this->messageManager->addErrorMessage($errorMessage);
        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
            ->setPath('vault/cards/listaction');
    }

    /**
     * Retrieve payment token from DB
     *
     * @param Http $request
     * @param int $customerId
     * @return PaymentTokenInterface|null
     */
    private function getPaymentToken(Http $request, int $customerId)
    {
        $publicHash = $request->getPostValue(PaymentTokenInterface::PUBLIC_HASH);

        if ($publicHash === null) {
            return null;
        }

        return $this->paymentTokenManagement->getByPublicHash($publicHash, $customerId);
    }

    /**
     * * Create a success message and redirect to vaulted card list
     *
     * @return ResponseInterface
     */
    private function createSuccessMessage()
    {
        $this->messageManager->addSuccessMessage(
            __('Stored Payment Method was successfully removed')
        );

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
            ->setPath('vault/cards/listaction');
    }

    /**
     * @inheritdoc
     */
    public function createCsrfValidationException(RequestInterface $request) :? InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function validateForCsrf(RequestInterface $request) :? bool
    {
        return true;
    }
}
