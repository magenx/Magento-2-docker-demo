<?php

declare(strict_types=1);

namespace Yandex\Allure\Adapter\Annotation;

use Qameta\Allure\Attribute;
use Qameta\Allure\Legacy\Annotation\LegacyAnnotationInterface;
use Yandex\Allure\Adapter\Model\SeverityLevel;

/**
 * @Annotation
 * @Target({"METHOD"})
 * @deprecated Use native PHP attribute {@see Attribute\Severity}
 */
class Severity implements LegacyAnnotationInterface
{
    /**
     * @psalm-suppress DeprecatedClass
     */
    public string $level = SeverityLevel::NORMAL;

    public function convert(): Attribute\Severity
    {
        return new Attribute\Severity($this->level);
    }
}
