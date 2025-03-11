<?php
/*************************************************************************
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2023 Adobe
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
 **************************************************************************/
declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Model\Api;

use Exception;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PaymentServicesPaypal\Api\Data\PaymentConfigApplePayInterfaceFactory;
use Magento\PaymentServicesPaypal\Api\Data\PaymentConfigHostedFieldsInterfaceFactory;
use Magento\PaymentServicesPaypal\Api\Data\PaymentConfigSmartButtonsInterfaceFactory;
use Magento\PaymentServicesPaypal\Api\PaymentConfigManagementInterface;
use Magento\PaymentServicesPaypal\Api\PaymentConfigRequestInterface;
use Magento\PaymentServicesPaypal\Api\PaymentConfigResponseInterface;
use Magento\PaymentServicesPaypal\Api\PaymentConfigResponseInterfaceFactory;
use Magento\PaymentServicesPaypal\Model\ApplePayConfigProvider;
use Magento\PaymentServicesPaypal\Model\GooglePayConfigProvider;
use Magento\PaymentServicesPaypal\Model\HostedFieldsConfigProvider;
use Magento\PaymentServicesPaypal\Model\SmartButtonsConfigProvider;
use Magento\Store\Model\StoreManagerInterface;

class PaymentConfigRequest implements PaymentConfigRequestInterface
{
    /**
     * @var PaymentConfigResponseInterfaceFactory
     */
    private PaymentConfigResponseInterfaceFactory $paymentConfigResponseInterfaceFactory;

    /**
     * @var PaymentConfigManagementInterface
     */
    private PaymentConfigManagementInterface $paymentConfigManagement;

    /**
     * @var PaymentConfigApplePayInterfaceFactory
     */
    private PaymentConfigApplePayInterfaceFactory $paymentConfigApplePayInterfaceFactory;

    /**
     * @var PaymentConfigSmartButtonsInterfaceFactory
     */
    private PaymentConfigSmartButtonsInterfaceFactory $paymentConfigSmartButtonsInterfaceFactory;

    /**
     * @var PaymentConfigHostedFieldsInterfaceFactory
     */
    private PaymentConfigHostedFieldsInterfaceFactory $paymentConfigHostedFieldsInterfaceFactory;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @param PaymentConfigManagementInterface $paymentConfigManagement
     * @param PaymentConfigResponseInterfaceFactory $paymentConfigResponseInterfaceFactory
     * @param PaymentConfigApplePayInterfaceFactory $paymentConfigApplePayInterfaceFactory
     * @param PaymentConfigHostedFieldsInterfaceFactory $paymentConfigHostedFieldsInterfaceFactory
     * @param PaymentConfigSmartButtonsInterfaceFactory $paymentConfigSmartButtonsInterfaceFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        PaymentConfigManagementInterface $paymentConfigManagement,
        PaymentConfigResponseInterfaceFactory  $paymentConfigResponseInterfaceFactory,
        PaymentConfigApplePayInterfaceFactory  $paymentConfigApplePayInterfaceFactory,
        PaymentConfigHostedFieldsInterfaceFactory  $paymentConfigHostedFieldsInterfaceFactory,
        PaymentConfigSmartButtonsInterfaceFactory  $paymentConfigSmartButtonsInterfaceFactory,
        StoreManagerInterface $storeManager,
    ) {
        $this->paymentConfigManagement = $paymentConfigManagement;
        $this->paymentConfigResponseInterfaceFactory = $paymentConfigResponseInterfaceFactory;
        $this->paymentConfigApplePayInterfaceFactory = $paymentConfigApplePayInterfaceFactory;
        $this->paymentConfigHostedFieldsInterfaceFactory = $paymentConfigHostedFieldsInterfaceFactory;
        $this->paymentConfigSmartButtonsInterfaceFactory = $paymentConfigSmartButtonsInterfaceFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritdoc
     */
    public function getConfig(string $location)
    {
        try {
            $config = $this->paymentConfigManagement->getConfig(
                $location,
                (int)$this->storeManager->getStore()->getId()
            );
            /** @var  PaymentConfigResponseInterface $response  */
            $response = $this->paymentConfigResponseInterfaceFactory->create();
            $response->setData($config);

            return $response;
        } catch (Exception $e) {
            throw new NoSuchEntityException(__('Configuration not found'));
        }
    }

    /**
     * @inheritdoc
     */
    public function getApplePayConfig(string $location)
    {
        try {
            return $this->paymentConfigManagement->getConfigItem(
                $location,
                ApplePayConfigProvider::CODE,
                (int)$this->storeManager->getStore()->getId()
            );
        } catch (Exception $e) {
            throw new NoSuchEntityException(__('Configuration not found'));
        }
    }

    /**
     * @inheritdoc
     */
    public function getGooglePayConfig(string $location)
    {
        try {
            return $this->paymentConfigManagement->getConfigItem(
                $location,
                GooglePayConfigProvider::CODE,
                (int)$this->storeManager->getStore()->getId()
            );
        } catch (Exception $e) {
            throw new NoSuchEntityException(__('Configuration not found'));
        }
    }

    /**
     * @inheritdoc
     */
    public function getSmartButtonsConfig(string $location)
    {
        try {
            return $this->paymentConfigManagement->getConfigItem(
                $location,
                SmartButtonsConfigProvider::CODE,
                (int)$this->storeManager->getStore()->getId()
            );
        } catch (Exception $e) {
            throw new NoSuchEntityException(__('Configuration not found'));
        }
    }

    /**
     * @inheritdoc
     */
    public function getHostedFieldsConfig(string $location)
    {
        try {
            return $this->paymentConfigManagement->getConfigItem(
                $location,
                HostedFieldsConfigProvider::CODE,
                (int)$this->storeManager->getStore()->getId()
            );
        } catch (Exception $e) {
            throw new NoSuchEntityException(__('Configuration not found'));
        }
    }
}
