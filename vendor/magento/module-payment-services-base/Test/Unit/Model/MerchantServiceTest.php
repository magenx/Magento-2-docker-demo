<?php
/**
 * ADOBE CONFIDENTIAL
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
 */
declare(strict_types=1);

namespace Magento\PaymentServicesBase\Test\Unit\Model;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\PaymentServicesBase\Model\Config;
use Magento\PaymentServicesBase\Model\MerchantService;
use Magento\PaymentServicesBase\Model\ServiceClientInterface;
use PHPUnit\Framework\TestCase;

class MerchantServiceTest extends TestCase
{
    /**
     * @var MerchantService
     */
    private $merchantService;

    /**
     * @var Config|\PHPUnit\Framework\MockObject\MockObject
     */
    private $config;

    /**
     * @var ServiceClientInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $serviceClient;

    /**
     * @var WriterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configWriter;

    /**
     * @var TypeListInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cacheTypeList;

    /**
     * Setup the test
     */
    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->serviceClient = $this->createMock(ServiceClientInterface::class);
        $this->configWriter = $this->createMock(WriterInterface::class);
        $this->cacheTypeList = $this->createMock(TypeListInterface::class);

        $this->merchantService = new MerchantService(
            $this->config,
            $this->serviceClient,
            $this->configWriter,
            $this->cacheTypeList
        );
    }

    /**
     * @return void
     */
    public function testResetMerchantId(): void
    {
        $this->config->expects($this->once())
            ->method('getMerchantId')
            ->willReturn('merchant_id');
        $this->serviceClient->expects($this->once())
            ->method('request')
            ->willReturn(['is_successful' => true, 'status'=>200]);

        $this->configWriter->expects($this->once())
            ->method($this->anything());

        $result = $this->merchantService->delete('sandbox');

        $this->assertTrue($result['is_successful']);
    }

    /**
     * @return void
     */
    public function testResetMerchantIdWithWrongEnvironment(): void
    {
        $this->config->expects($this->never())
            ->method($this->anything());

        $this->serviceClient->expects($this->never())
            ->method($this->anything());

        $this->configWriter->expects($this->never())
            ->method($this->anything());

        $result = $this->merchantService->delete('integration');

        $this->assertFalse($result['is_successful']);
    }
}
