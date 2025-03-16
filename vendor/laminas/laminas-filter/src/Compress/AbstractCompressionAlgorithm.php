<?php

declare(strict_types=1);

namespace Laminas\Filter\Compress;

use Laminas\Stdlib\ArrayUtils;
use Traversable;

use function is_array;
use function method_exists;

/**
 * Abstract compression adapter
 *
 * @deprecated Since 2.40.0 Compression adapters will be split into multiple interfaces to clearly separate the
 *             capability of the underlying compression or archive format. For example, tar cannot compress strings and
 *             GZ cannot be used to create multi-file archives.
 *
 * @template TOptions of array
 */
abstract class AbstractCompressionAlgorithm implements CompressionAlgorithmInterface
{
    /** @var TOptions */
    protected $options = [];

    /**
     * @param null|iterable $options (Optional) Options to set
     */
    public function __construct($options = null)
    {
        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }

        if (is_array($options)) {
            $this->setOptions($options);
        }
    }

    /**
     * Returns one or all set options
     *
     * @param  string|null $option Option to return
     * @return mixed
     * @psalm-return ($option is null ? TOptions : mixed)
     */
    public function getOptions($option = null)
    {
        if ($option === null) {
            return $this->options;
        }

        if (! isset($this->options[$option])) {
            return null;
        }

        return $this->options[$option];
    }

    /**
     * Sets all or one option
     *
     * @return self
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $option) {
            $method = 'set' . $key;
            if (method_exists($this, $method)) {
                $this->$method($option);
            }
        }

        return $this;
    }
}
