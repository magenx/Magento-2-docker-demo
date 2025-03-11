<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ServicesIdGraphQlServer\Resolver\Mutation;

use Magento\ServicesId\Model\MerchantRegistryConnectionValidator;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Resolver for mutation validateSandboxKeys
 */
class ValidateSandboxKeys implements ResolverInterface
{
    /**
     * @var MerchantRegistryConnectionValidator
     */
    private $connectionValidator;

    /**
     * @param MerchantRegistryConnectionValidator $connectionValidator
     */
    public function __construct(
        MerchantRegistryConnectionValidator $connectionValidator
    ) {
        $this->connectionValidator = $connectionValidator;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        return ['message' => $this->connectionValidator->validate('sandbox')];
    }
}
