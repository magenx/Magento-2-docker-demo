<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQlServer\Model;

use Magento\Framework\UrlInterface;

/**
 * Url provider for graphql server
 *
 * @api
 */
class UrlProvider
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        UrlInterface $urlBuilder
    ) {
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Get GraphQl server URL
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->urlBuilder->getUrl('graphqls/graphql/gateway', ['_query' => ['isAjax' => 'true']]);
    }
}
