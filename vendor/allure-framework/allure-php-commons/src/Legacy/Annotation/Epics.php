<?php

declare(strict_types=1);

namespace Yandex\Allure\Adapter\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Qameta\Allure\Attribute;
use Qameta\Allure\Legacy\Annotation\LegacyAnnotationInterface;

use function array_map;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 * @deprecated Use native PHP attribute {@see Attribute\Epic}
 * @psalm-suppress MissingConstructor
 */
class Epics implements LegacyAnnotationInterface
{
    /**
     * @var list<string>
     * @Required
     */
    public array $epicNames;

    /**
     * @return list<string>
     */
    public function getEpicNames(): array
    {
        return $this->epicNames;
    }

    /**
     * @return list<Attribute\Epic>
     */
    public function convert(): array
    {
        return array_map(
            fn (string $name) => new Attribute\Epic($name),
            $this->epicNames,
        );
    }
}
