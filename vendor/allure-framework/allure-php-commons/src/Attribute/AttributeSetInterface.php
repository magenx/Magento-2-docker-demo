<?php

declare(strict_types=1);

namespace Qameta\Allure\Attribute;

interface AttributeSetInterface extends AttributeInterface
{
    /**
     * @return list<AttributeInterface>
     */
    public function getAttributes(): array;
}
