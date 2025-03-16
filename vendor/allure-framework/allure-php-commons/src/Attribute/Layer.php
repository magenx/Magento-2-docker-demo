<?php

declare(strict_types=1);

namespace Qameta\Allure\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class Layer extends AbstractLabel
{
    public function __construct(string $value)
    {
        parent::__construct(Label::LAYER, $value);
    }
}
