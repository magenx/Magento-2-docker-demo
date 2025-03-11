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

namespace Magento\PaymentServicesPaypal\Block\Customer\Vault;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Integration\Api\UserTokenIssuerInterface;
use Magento\Integration\Model\CustomUserContext;
use Magento\Integration\Model\UserToken\UserTokenParametersFactory;
use Magento\PaymentServicesPaypal\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Psr\Log\LoggerInterface;

/**
 * @api
 */
class AddCardForm extends \Magento\Framework\View\Element\Template
{
    private const XML_PATH_GRAPHQL_DISABLE_SESSION = 'graphql/session/disable';

    /**
     * @var Config
     */
    private Config $paymentsConfig;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var CustomerSession
     */
    private CustomerSession $customerSession;

    /**
     * @var UserTokenIssuerInterface
     */
    private $tokenIssuer;

    /**
     * @var UserTokenParametersFactory
     */
    private UserTokenParametersFactory $tokenParamsFactory;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param Context $context
     * @param Config $paymentsConfig
     * @param StoreManagerInterface $storeManager
     * @param CustomerSession $customerSession
     * @param UserTokenIssuerInterface $tokenIssuer
     * @param UserTokenParametersFactory $tokenParamsFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $paymentsConfig,
        StoreManagerInterface $storeManager,
        CustomerSession $customerSession,
        UserTokenIssuerInterface $tokenIssuer,
        UserTokenParametersFactory $tokenParamsFactory,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        array $data = [],
    ) {
        parent::__construct($context, $data);

        $this->paymentsConfig = $paymentsConfig;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->tokenIssuer = $tokenIssuer;
        $this->tokenParamsFactory = $tokenParamsFactory;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Prepare the layout of the new credit card block.
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->pageConfig->getTitle()->set("Add New Card");

        return $this;
    }

    /**
     * Pass on data needed in the JS component.
     *
     * @return array
     */
    public function getComponentParams(): array
    {
        try {
            $storeViewCode = $this->storeManager->getStore()->getCode();
        } catch (\Exception $e) {
            $storeViewCode = $this->storeManager->getDefaultStoreView()->getCode();
        }

        try {
            // if we use cookie session, we should use graphql endpoint that includes store code
            $token = '';
            $graphQLEndpointUrl = $this->storeManager->getStore()->getBaseUrl() . 'graphql';

            if ($this->isCookieSessionDisabledForGQL()) {
                // if we use oauth token, we can use the default graphql endpoint
                $graphQLEndpointUrl = '';

                $userContext = new CustomUserContext(
                    (int) $this->customerSession->getCustomer()->getId(),
                    UserContextInterface::USER_TYPE_CUSTOMER
                );

                $token = $this->tokenIssuer->create(
                    $userContext,
                    $this->tokenParamsFactory->create()
                );
            }
        } catch (\Exception $e) {
            $this->logger->error("could not create token: " . $e->getMessage());
        }

        return [
            'savedCardListUrl' => $this->getUrl('vault/cards/listaction'),
            'paymentsSDKUrl' =>  $this->paymentsConfig->getPaymentsSDKUrl(),
            'storeViewCode' => $storeViewCode,
            'oauthToken' => $token,
            'graphQLEndpointUrl' => $graphQLEndpointUrl,
        ];
    }

    /**
     * Get config value is session disabled for graphql area.
     *
     * We need this for compatibility with Magento 2.4.4.
     *
     * @return bool
     */
    private function isCookieSessionDisabledForGQL(): bool
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_GRAPHQL_DISABLE_SESSION);

        if ($value === '1') {
            return true;
        }

        return false;
    }
}
