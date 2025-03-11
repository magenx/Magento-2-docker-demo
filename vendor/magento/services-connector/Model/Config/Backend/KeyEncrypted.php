<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\ServicesConnector\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;

/**
 * Encrypted config api keys backend model.
 *
 */
class KeyEncrypted extends \Magento\Framework\App\Config\Value implements
    \Magento\Framework\App\Config\Data\ProcessorInterface
{
    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param EncryptorInterface $encryptor
     * @param LoggerInterface $logger
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        EncryptorInterface $encryptor,
        LoggerInterface $logger,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->encryptor = $encryptor;
        $this->logger = $logger;
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Decrypt value after loading
     *
     * @return void
     */
    protected function _afterLoad()
    {
        $value = $this->getValue();
        if (!empty($value) && ($decrypted = $this->safedecrypt($value))) {
            $this->setValue($decrypted);
        }
    }

    /**
     * Encrypt value before saving
     *
     * @return void
     */
    public function beforeSave()
    {
        $this->_dataSaveAllowed = false;
        $value = $this->getValue();
        // don't save value, if an obscured value was received. This indicates that data was not changed.
        // based on Magento\Config\Model\Config\Backend\Encrypted code
        if (!empty($value) && !preg_match('/^\*+$/', $value)) {
            $this->_dataSaveAllowed = true;
            $encrypted = $this->encryptor->encrypt($value);
            $this->setValue($encrypted);
        } elseif (empty($value)) {
            $this->_dataSaveAllowed = true;
        }
    }

    /**
     * Process config value
     *
     * @param string $value
     * @return string
     */
    public function processValue($value)
    {
        return $this->safeDecrypt($value);
    }

    /**
     * Tries to decrypt the input value, in case of an issue decrypting it return the original value.
     *
     * @param string|null $value
     * @return string|null
     */
    private function safeDecrypt(?string $value): ?string
    {
        if (empty($value)) {
            return '';
        }

        $decryptedValue = '';
        $failedDecryption = false;
        try {
            $decryptedValue = $this->encryptor->decrypt($value);
        } catch (\Exception $exception) {
            $this->logger->debug('Error decrypting key', ['error' => $exception]);
            $failedDecryption = true;
        }
        if (empty($decryptedValue)) {
            $this->logger->debug('Error decrypting key', ['error' => "Decryption returned empty value"]);
            $failedDecryption = true;
        }
        if (false === mb_detect_encoding($decryptedValue, 'UTF-8', true)) {
            $this->logger->debug('Error decrypting key', ['error' => "Decryption returned non UTF-8 string"]);
            $failedDecryption = true;
        }
        // in case of a failed decryption returns original value
        return ($failedDecryption) ? $value : $decryptedValue;
    }
}
