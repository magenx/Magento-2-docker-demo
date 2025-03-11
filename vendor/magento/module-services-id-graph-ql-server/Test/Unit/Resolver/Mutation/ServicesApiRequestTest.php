<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ServicesIdGraphQlServer\Test\Unit\Resolver\Mutation;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\GraphQl\Model\Query\Context;
use Magento\ServicesId\Model\ServicesClientInterface;
use Magento\ServicesId\Model\ServicesConfigMessage;
use Magento\ServicesIdGraphQlServer\Resolver\Mutation\ServicesApiRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ServicesApiRequestTest extends TestCase
{
    protected const REQUEST_METHOD = 'GET';
    protected const PAYLOAD_TEST = 'payload test';

    /**
     * @var ServicesApiRequest
     */
    private $servicesApiRequest;
    /**
     * @var ServicesClientInterface|MockObject
     */
    private $servicesClientMock;
    /**
     * @var Json
     */
    private $serializer;
    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;
    /**
     * @var Field|MockObject
     */
    private Field $fieldMock;
    /**
     * @var ResolveInfo|MockObject
     */
    private ResolveInfo $resolveInfoMock;
    /**
     * @var Context|MockObject
     */
    private Context $contextMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->servicesClientMock = $this->createMock(ServicesClientInterface::class);
        $this->serializer = new Json();
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->fieldMock = $this->createMock(Field::class);
        $this->resolveInfoMock = $this->createMock(ResolveInfo::class);
        $this->contextMock = $this->createMock(Context::class);

        $this->servicesApiRequest = new ServicesApiRequest(
            $this->servicesClientMock,
            $this->serializer,
            $this->loggerMock
        );
    }

    /**
     * @param string $uri
     * @return void
     *
     * @dataProvider validUriParamDataProvider
     * @throws \Exception
     */
    public function testValidUriParamInRequest(string $uri): void
    {
        $expectedResult = [
            'environmentId'=>'testEnvId',
            'content'=>'test_content',
            'service'=> 'saas_service'
        ];

        $args['servicesApiRequest'] = [
            'method' => self::REQUEST_METHOD,
            'uri' => $uri,
            'payload' => self::PAYLOAD_TEST
        ];

        $this->servicesClientMock
            ->expects($this->once())
            ->method('request')
            ->with(
                self::REQUEST_METHOD,
                $uri,
                self::PAYLOAD_TEST,
                []
            )
            ->willReturn($expectedResult);

        $this->loggerMock
            ->expects($this->never())
            ->method('error');

        $result = $this->servicesApiRequest->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );
        $this->assertEquals(['response' => json_encode($expectedResult)], $result);
    }

    /**
     * @return array[]
     */
    public function validUriParamDataProvider(): array
    {
        return [
            ["/path/test/"],
            ["path/without/initial/slash"],
            ["/path/test/dkdioe-483948-dh8dodl"],
            ["path/ending/slash/"],
            ["https://commerce-int.adobe.io/ping"],
            ["http://test.commerce.adobe.io/ping"],
            ["https://test.commerce.adobe.io:8080/ping/pong"],
            ["https://user:password@commerce-int.adobe.io/ping"],
        ];
    }

    /**
     * @param string $uri
     * @return void
     *
     * @dataProvider invalidUriParamDataProvider
     * @throws \Exception
     */
    public function testInvalidUriParamInRequest(string $uri): void
    {
        $expectedResult = [
            'status' => 403,
            'statusText' => 'FORBIDDEN',
            'message' => ServicesConfigMessage::ERROR_REQUEST_NOT_ALLOWED_DOMAIN
        ];

        $args['servicesApiRequest'] = [
            'method' => self::REQUEST_METHOD,
            'uri' => $uri,
            'payload' => self::PAYLOAD_TEST
        ];

        $this->servicesClientMock
            ->expects($this->never())
            ->method('request');

        $this->loggerMock
            ->expects($this->once())
            ->method('error');

        $result = $this->servicesApiRequest->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );
        $this->assertEquals(['response' => json_encode($expectedResult)], $result);
    }

    /**
     * @return array[]
     */
    public function invalidUriParamDataProvider(): array
    {
        return [
            ["https://www.google.com"],
            ["https://www.google.com/maps"],
            ["http://127.0.0.1/"],
            ["http://127.0.0.1:9200"],
            ["http://127.0.0.1/magento2/auth.json.sample"],
            ["https://user:password@commerce-int.adobe.com/ping"],
            ["//collaborator-url.com"],
            ["/path///../test/"],
            ["path/invalid@./cca342"],
        ];
    }
}
