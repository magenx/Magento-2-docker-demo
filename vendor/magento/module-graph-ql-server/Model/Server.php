<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQlServer\Model;

use Magento\Framework\GraphQl\Query\Fields as QueryFields;
use Magento\Framework\GraphQl\Query\QueryProcessor;
use Magento\Framework\GraphQl\Schema\SchemaGeneratorInterface;
use Magento\GraphQlServer\Model\Context\ContextFactory;

/**
 *
 */
class Server
{
    private static $schema;
    /**
     * @var QueryFields
     */
    private $fields;
    /**
     * @var QueryProcessor
     */
    private $queryProcessor;
    /**
     * @var SchemaGeneratorInterface
     */
    private $schemaGenerator;
    /**
     * @var ContextFactory
     */
    private $contextFactory;

    public function __construct(
        QueryFields $fields,
        QueryProcessor $queryProcessor,
        SchemaGeneratorInterface $schemaGenerator,
        ContextFactory $contextFactory
    ) {
        $this->fields = $fields;
        $this->queryProcessor = $queryProcessor;
        $this->schemaGenerator = $schemaGenerator;
        $this->contextFactory = $contextFactory;
    }

    /**
     * @param string $query
     * @param ?array $variables
     * @return array
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlInputException
     */
    public function execute(string $query, ?array $variables) : array
    {
        $this->fields->setQuery($query, $variables);
        if (!self::$schema) {
            self::$schema = $this->schemaGenerator->generate();
        }
        return $this->queryProcessor->process(
            self::$schema,
            $query,
            $this->contextFactory->create(),
            $variables ?? []
        );
    }
}
