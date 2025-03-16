<?php

declare(strict_types=1);

namespace Yandex\Allure\Adapter\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Qameta\Allure\Attribute;
use Qameta\Allure\Legacy\Annotation\LegacyAnnotationInterface;

use function array_map;

/**
 * @Annotation
 * @Target({"METHOD", "ANNOTATION"})
 * @deprecated Use native PHP attribute {@see Attribute\Label}
 * @psalm-suppress MissingConstructor
 */
class Label implements LegacyAnnotationInterface
{
    /**
     * @Required
     */
    public string $name;

    /**
     * @var array
     * @psalm-var list<string>
     * @Required
     */
    public array $values;

    /**
     * @return list<Attribute\Label>
     */
    public function convert(): array
    {
        return array_map(
            fn (string $value) => new Attribute\Label($this->name, $value),
            $this->values,
        );
    }
}
