<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ServicesIdGraphQlServer\Resolver\Mutation;

use Magento\ServicesId\Model\ServicesConfig;
use Magento\ServicesId\Model\ServicesConfigInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Psr\Log\LoggerInterface;

/**
 * Resolver for mutation saveCommerceServicesConfig
 */
class SaveCommerceServicesConfig implements ResolverInterface
{
    /**
     * @var ServicesConfigInterface
     */
    private $servicesConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ServicesConfigInterface $servicesConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        ServicesConfigInterface $servicesConfig,
        LoggerInterface $logger
    ) {
        $this->servicesConfig = $servicesConfig;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        $config = $args['commerceServicesConfig'];
        $configs = [
            ServicesConfig::CONFIG_PATH_SERVICES_CONNECTOR_SANDBOX_API_KEY => $config['sandboxApiKey'] ?? null,
            ServicesConfig::CONFIG_PATH_SERVICES_CONNECTOR_SANDBOX_PRIVATE_KEY => $config['sandboxPrivateKey'] ?? null,
            ServicesConfig::CONFIG_PATH_SERVICES_CONNECTOR_PRODUCTION_API_KEY => $config['productionApiKey'] ?? null,
            ServicesConfig::CONFIG_PATH_SERVICES_CONNECTOR_PRODUCTION_PRIVATE_KEY => $config['productionPrivateKey'] ?? null,
            ServicesConfig::CONFIG_PATH_PROJECT_ID => $config['projectId'] ?? null,
            ServicesConfig::CONFIG_PATH_PROJECT_NAME => $config['projectName'] ?? null,
            ServicesConfig::CONFIG_PATH_ENVIRONMENT_ID => $config['environmentId'] ?? null,
            ServicesConfig::CONFIG_PATH_ENVIRONMENT_NAME => $config['environmentName'] ?? null,
            ServicesConfig::CONFIG_PATH_ENVIRONMENT_TYPE => $config['environmentType'] ?? null,
            ServicesConfig::CONFIG_PATH_IMS_ORGANIZATION_ID => $config['imsOrganizationId'] ?? null
        ];
        $configs = array_filter($configs, function($value) { return $value !== null; });

        $message = 'NOT_CHANGED';
        if (!empty($configs)) {
            $message = $this->setConfigValues($configs);
        }

        return ['message' => $message];
    }

    /**
     * Set values to store configuration
     *
     * @param array $configs
     * @return string
     */
    private function setConfigValues(array $configs) : string
    {
        try {
            $this->servicesConfig->setConfigValues($configs);
            $message = 'OK';
        } catch (\Exception $ex) {
            $this->logger->error($ex->getMessage());
            $message = 'ERROR_SAVE_FAILED';
        }
        return $message;
    }
}
