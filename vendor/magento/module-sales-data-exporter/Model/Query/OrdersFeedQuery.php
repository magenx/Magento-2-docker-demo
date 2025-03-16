<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesDataExporter\Model\Query;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\DataExporter\Model\Query\FeedQuery;

class OrdersFeedQuery extends FeedQuery
{
    /**
     * @var string
     */
    private $environment;

    /**
     * @param ResourceConnection $resourceConnection
     * @param string $environment
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        string $environment
    ) {
        parent::__construct($resourceConnection);
        $this->environment = $environment;
    }

    /**
     * @inheritDoc
     */
    public function getLimitSelect(
        FeedIndexMetadata $metadata,
        string $modifiedAt,
        int $offset,
        ?array $ignoredExportStatus = null
    ): Select {
        return parent::getLimitSelect($metadata, $modifiedAt, $offset, $ignoredExportStatus)
            ->where('t.mode = ?', $this->environment);
    }

    /**
     * @inheritDoc
     */
    public function getDataSelect(
        FeedIndexMetadata $metadata,
        string $modifiedAt,
        ?string $limit,
        ?array $ignoredExportStatus = null
    ): Select {
        return parent::getDataSelect($metadata, $modifiedAt, $limit, $ignoredExportStatus)
            ->where('t.mode = ?', $this->environment);
    }
}
