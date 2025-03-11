<?php

declare(strict_types=1);

namespace Yandex\Allure\Adapter\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Qameta\Allure\Attribute;
use Qameta\Allure\Legacy\Annotation\LegacyAnnotationInterface;
use Yandex\Allure\Adapter\Model\DescriptionType;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 * @deprecated Use native PHP attribute {@see Attribute\Description}
 * @psalm-suppress MissingConstructor
 */
class Description implements LegacyAnnotationInterface
{
    /**
     * @var string
     * @Required
     */
    public string $value;

    /**
     * @var string
     * @psalm-suppress DeprecatedClass
     */
    public string $type = DescriptionType::TEXT;

    public function convert(): Attribute\Description
    {
        /** @psalm-suppress DeprecatedClass */
        return new Attribute\Description(
            $this->value,
            DescriptionType::HTML == $this->type,
        );
    }
}
