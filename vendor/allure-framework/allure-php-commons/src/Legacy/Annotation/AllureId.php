<?php

declare(strict_types=1);

namespace Yandex\Allure\Adapter\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Qameta\Allure\Attribute;
use Qameta\Allure\Legacy\Annotation\LegacyAnnotationInterface;

/**
 * @Annotation
 * @Target({"METHOD"})
 * @deprecated Use native PHP attribute {@see Attribute\AllureId}
 * @psalm-suppress MissingConstructor
 */
class AllureId implements LegacyAnnotationInterface
{
    /**
     * @var string
     * @Required
     */
    public string $value;

    public function convert(): Attribute\AllureId
    {
        return new Attribute\AllureId($this->value);
    }
}
