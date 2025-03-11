<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentServicesSaaSExport\Model\Http;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\SaaSCommon\Model\Http\Converter\JsonConverter;
use Magento\PaymentServicesBase\Model\Config;

/**
 * Represents JSON converter for http request and response body.
 */
class Converter extends JsonConverter
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var string
     */
    private $feedName;

    /**
     * @var string
     */
    private $version;

    /**
     * @param Json $serializer
     */
    public function __construct(
        Json $serializer,
        Config $config,
        string $feedName,
        string $version
    ) {
        parent::__construct($serializer);
        $this->config = $config;
        $this->feedName = $feedName;
        $this->version = $version;
    }

    /**
     * @inheritdoc
     */
    public function toBody(array $data): string
    {
        $environment = $data['environment'];
        unset($data['environment']);
        $body = [
            'feed' => $this->feedName,
            'timestamp' => time(),
            'merchantId' => $this->config->getMerchantId($environment),
            'version' => $this->version,
            'data' => $data
        ];
        return parent::toBody($body);
    }
}
