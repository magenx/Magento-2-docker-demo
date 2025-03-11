<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ServicesIdGraphQlServer\Resolver\Mutation;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\ServicesConnector\Api\ConfigInterface;
use Magento\ServicesConnector\Model\Config;
use Magento\ServicesId\Model\ServicesClientInterface;
use Magento\ServicesId\Model\ServicesConfigMessage;

/**
 * Resolver for imsReadOrganizations
 */
class ImsReadOrganizations implements ResolverInterface
{
    /**
     * @var ServicesClientInterface
     */
    private $servicesClient;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ConfigInterface
     */
    private $servicesConnectorConfig;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param ServicesClientInterface $servicesClient
     * @param ScopeConfigInterface $scopeConfig
     * @param ConfigInterface $servicesConnectorConfig
     * @param Json $serializer
     */
    public function __construct(
        ServicesClientInterface $servicesClient,
        ScopeConfigInterface $scopeConfig,
        ConfigInterface $servicesConnectorConfig,
        Json $serializer
    ) {
        $this->servicesClient = $servicesClient;
        $this->scopeConfig = $scopeConfig;
        $this->servicesConnectorConfig = $servicesConnectorConfig;
        $this->serializer = $serializer;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        if ($this->servicesConnectorConfig->isImsTokenAuthCredentialsType()) {
            $result = $this->servicesClient->request(
                'GET',
                $this->scopeConfig->getValue(Config::IMS_TECH_ACCOUNT_IMS_READ_ORGANIZATIONS_RESOURCE_PATH),
                null,
                [],
                null,
                $this->scopeConfig->getValue(Config::IMS_TECH_ACCOUNT_IMS_URL_PATH)
            );
        } else {
            $result['error'] = [
                'status' => 401,
                'statusText' => 'UNAUTHORIZED',
                'message' => ServicesConfigMessage::ERROR_IMS_CREDENTIALS_TYPE_NOT_SET
            ];
        }

        return (isset($result['error']))
            ? ['error' => $this->serializer->serialize($result)]
            : ['organizations' => $this->getOrganizationIdList($result)];
    }

    /**
     * Get the list of Organization Ids
     *
     * @param array $result
     * @return array
     */
    private function getOrganizationIdList(array $result): array
    {
        $organizationsList = [];
        foreach ($result as $organization) {
            if (isset($organization['orgRef'])) {
                $organizationsList[] =
                    [
                        'id' => sprintf("%s@%s", $organization['orgRef']['ident'], $organization['orgRef']['authSrc']),
                        'name' => $organization['orgName'],
                    ];
            }
        }
        return $organizationsList;
    }
}
