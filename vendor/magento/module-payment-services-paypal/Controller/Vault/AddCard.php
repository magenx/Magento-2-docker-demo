<?php
/************************************************************************
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
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Controller\Vault;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\PaymentServicesPaypal\Model\Config;
use Magento\Store\Model\StoreManagerInterface;

class AddCard implements HttpGetActionInterface
{
    /**
     * @var PageFactory
     */
    private PageFactory $pageFactory;

    /**
     * @var ResultFactory
     */
    private ResultFactory $resultFactory;

    /**
     * @var Session
     */
    private Session $customerSession;

    /**
     * @var MessageManagerInterface
     */
    private MessageManagerInterface $messageManager;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @param PageFactory $pageFactory
     * @param ResultFactory $resultFactory
     * @param Session $customerSession
     * @param MessageManagerInterface $messageManager
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        PageFactory $pageFactory,
        ResultFactory $resultFactory,
        Session $customerSession,
        MessageManagerInterface $messageManager,
        Config $config,
        StoreManagerInterface $storeManager
    ) {
        $this->pageFactory = $pageFactory;
        $this->resultFactory = $resultFactory;
        $this->customerSession = $customerSession;
        $this->messageManager = $messageManager;
        $this->config = $config;
        $this->storeManager = $storeManager;
    }

    /**
     * Execute action based on request and return result
     *
     * @return ResultInterface
     * @throws NoSuchEntityException
     */
    public function execute(): ResultInterface
    {
        $storeId = (int)$this->storeManager->getStore()->getId();
        $customerId = $this->customerSession->getCustomerId();

        if ($customerId === null || !$this->config->isVaultEnabled($storeId)) {
            $this->messageManager->addErrorMessage(__('Error while trying to access the page.'));
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
                ->setPath('vault/cards/listaction');
        }

        return $this->pageFactory->create();
    }
}
