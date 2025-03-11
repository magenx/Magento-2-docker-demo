<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ServicesIdGraphQlServer\Resolver\Query;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\ServicesId\Model\ServicesConfigInterface;

/**
 * Resolver for CommerceServicesConfig
 */
class CommerceServicesConfig implements ResolverInterface
{
    /**
     * @var ServicesConfigInterface
     */
    private $servicesConfig;

    /**
     * @param ServicesConfigInterface $servicesConfig
     */
    public function __construct(
        ServicesConfigInterface $servicesConfig
    ) {
        $this->servicesConfig = $servicesConfig;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        return [
            'sandboxApiKey' => $this->servicesConfig->getSandboxApiKey(),
            'sandboxPrivateKey' => $this->servicesConfig->getSandboxPrivateKey(),
            'productionApiKey' => $this->servicesConfig->getProductionApiKey(),
            'productionPrivateKey' => $this->servicesConfig->getProductionPrivateKey(),
            'projectId' => $this->servicesConfig->getProjectId(),
            'projectName' => $this->servicesConfig->getProjectName(),
            'environmentId' => $this->servicesConfig->getEnvironmentId(),
            'environmentName' => $this->servicesConfig->getEnvironmentName(),
            'environmentType' => $this->servicesConfig->getEnvironmentType(),
            'imsOrganizationId' => $this->servicesConfig->getImsOrganizationId(),
            'disabledFields' => $this->servicesConfig->getDisabledFields()
        ];
    }
}
