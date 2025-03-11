<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ServicesIdGraphQlServer\Resolver\Mutation;

use InvalidArgumentException;
use Laminas\Uri\UriFactory;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\ServicesId\Model\ServicesClientInterface;
use Magento\ServicesId\Model\ServicesConfigMessage;
use Psr\Log\LoggerInterface;

/**
 * Resolver for mutation servicesApiRequest
 */
class ServicesApiRequest implements ResolverInterface
{
    /**
     * Allowed domain for the uri requests
     */
    protected const ALLOWED_DOMAIN = "adobe.io";

    /**
     * Regular expression for validating the path
     * The paths can have one or more segments
     * Each segment can contain alphanumeric characters, underscores, or hyphens.
     * The path can optionally start and/or end with a forward slash.
     */
    protected const PATH_REGEX = "/^(\/?[\w-]+)+\/?$/";

    /**
     * @var ServicesClientInterface
     */
    private $servicesClient;

    /**
     * @var Json
     */
    private $serializer;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ServicesClientInterface $servicesClient
     * @param Json $serializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        ServicesClientInterface $servicesClient,
        Json $serializer,
        LoggerInterface $logger
    ) {
        $this->servicesClient = $servicesClient;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        $request = $args['servicesApiRequest'];
        try {
            $this->validateUriParam($request['uri']);
            $result = $this->servicesClient->request(
                $request['method'],
                $request['uri'],
                $request['payload'],
                isset($request['headers']) ? $this->getHeaders($request['headers']) : []
            );
        } catch (InvalidArgumentException $ex) {
            $result = [
                'status' => 403,
                'statusText' => 'FORBIDDEN',
                'message' => ServicesConfigMessage::ERROR_REQUEST_NOT_ALLOWED_DOMAIN
            ];
            $this->logger->error($ex->getMessage());
        }
        $response = $this->serializer->serialize($result);
        return ['response' => $response];
    }

    /**
     * Parse headers from request
     *
     * @param array $requestHeaders
     * @return array
     */
    private function getHeaders(array $requestHeaders) : array
    {
        $headers = [];
        foreach ($requestHeaders as $header) {
            $headers[$header['key']] = $header['value'];
        }
        return $headers;
    }

    /**
     * Validates uri param from request:
     * - In case path is provided (standard usage), validate is a correct path format
     * - If a full URI is passed, only  requests to adobeio domain are allowed,
     *
     * @param string $uri
     * @return void
     * @throws InvalidArgumentException
     */
    private function validateUriParam(string $uri) : void
    {
        //complete URL
        if (filter_var($uri, FILTER_VALIDATE_URL)) {
            $uri_parts = UriFactory::factory($uri);
            if (!empty($uri_parts->getHost() && str_ends_with($uri_parts->getHost(), self::ALLOWED_DOMAIN) === false)) {
                throw new InvalidArgumentException(
                    sprintf(
                        "servicesApiRequest mutation can only allow requests to the following domain(s): %s",
                        self::ALLOWED_DOMAIN
                    )
                );
            }
        } elseif (!(preg_match(self::PATH_REGEX, $uri))) {
            throw new InvalidArgumentException(
                sprintf(
                    "servicesApiRequest mutation - invalid uri parameter provided: %s",
                    $uri
                )
            );
        }
    }
}
