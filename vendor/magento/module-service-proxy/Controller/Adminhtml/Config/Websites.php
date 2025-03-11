<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ServiceProxy\Controller\Adminhtml\Config;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ScopeInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Store\Model\Group;
use Magento\Store\Model\ScopeInterface as StoreScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\ServiceProxy\Controller\Adminhtml\AbstractProxyController;
use Exception;
use Magento\Store\Model\Website;

/**
 * Websites Provider
 */
class Websites extends AbstractProxyController implements HttpGetActionInterface
{
    // phpcs:disable
    const ADMIN_RESOURCE = 'Magento_ServiceProxy::services';

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->jsonFactory = $jsonFactory;
    }

    /**
     * Retrieve and update service configurations remotely
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $response = $this->jsonFactory->create();

        try {
            $response->setHttpResponseCode(200)->setData($this->getWebsites());
        } catch (Exception $e) {
            $response->setHttpResponseCode(500)->setData('Failed to load stores, ' . $e->getMessage());
        }

        return $response;
    }

    /**
     * Retrieve websites of the instance
     *
     * @return array
     */
    private function getWebsites() : array
    {
        $websites = [];
        $websites[] = [
            'id' => '',
            'code' => '',
            'name' => 'Default',
            'scope' => ScopeInterface::SCOPE_DEFAULT,
        ];
        foreach ($this->storeManager->getWebsites() as $website) {
            $websites[] = [
                'id' => $website->getId(),
                'code' => $website->getCode(),
                'name' => $website->getName(),
                'scope' => StoreScopeInterface::SCOPE_WEBSITES,
                'groups' => $this->getGroups($website),
            ];
        }

        return $websites;
    }

    /**
     * Retrieve groups of stores of the website
     *
     * @param Website $website
     * @return array
     */
    private function getGroups(Website $website) : array
    {
        $groups = [];
        foreach ($website->getGroups() as $group) {
            $groups[] = [
                'id' => $group->getId(),
                'code' => $group->getCode(),
                'name' => $group->getName(),
                'scope' => StoreScopeInterface::SCOPE_GROUPS,
                'stores' => $this->getStores($group),
            ];
        }

        return $groups;
    }

    /**
     * Retrieve stores of a store group
     *
     * @param Group $group
     * @return array
     */
    private function getStores(Group $group) : array
    {
        $stores = [];
        foreach ($group->getStores() as $store) {
            $stores[] = [
                'id' => $store->getId(),
                'code' => $store->getCode(),
                'scope' => StoreScopeInterface::SCOPE_STORES,
                'name' => $store->getName()
            ];
        }

        return $stores;
    }
}
