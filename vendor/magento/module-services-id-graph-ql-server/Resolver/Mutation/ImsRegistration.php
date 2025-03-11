<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ServicesIdGraphQlServer\Resolver\Mutation;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\ServicesId\Model\ServicesClientInterface;
use Magento\ServicesId\Model\ServicesConfigInterface;

/**
 * Resolver for mutation imsRegistration
 */
class ImsRegistration implements ResolverInterface
{
    /**
     * IMS registration service URL config path
     */
    const CONFIG_PATH_REGISTRATION_API_PATH = 'services_connector/services_id/registration_api_path';

    /**
     * @var ServicesClientInterface
     */
    private $servicesClient;

    /**
     * @var ServicesConfigInterface
     */
    private $servicesConfig;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var UrlInterface
     */
    private $urlInterface;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param ServicesClientInterface $servicesClient
     * @param ServicesConfigInterface $servicesConfig
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlInterface $urlInterface
     * @param Json $serializer
     */
    public function __construct(
        ServicesClientInterface $servicesClient,
        ServicesConfigInterface $servicesConfig,
        ScopeConfigInterface $scopeConfig,
        UrlInterface $urlInterface,
        Json $serializer
    ) {
        $this->servicesClient = $servicesClient;
        $this->servicesConfig = $servicesConfig;
        $this->scopeConfig = $scopeConfig;
        $this->urlInterface = $urlInterface;
        $this->serializer = $serializer;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        $serviceUri = $this->scopeConfig->getValue(self::CONFIG_PATH_REGISTRATION_API_PATH);
        $redirectUri = $this->urlInterface->getUrl('services_id/index/index');
        $payload = ['redirectUrl' => $redirectUri];
        $data = $this->serializer->serialize($payload);
        $headers = ['Magento-Environment-Id' => $this->servicesConfig->getEnvironmentId()];
        $result = $this->servicesClient->request('POST', $serviceUri, $data, $headers);

        return !isset($result['id'])
            ? ['error' => $this->serializer->serialize($result)]
            : ['id' => $result['id'], 'organizationId' => $result['organizationId']];
    }
}
