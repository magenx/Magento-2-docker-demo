<?php

declare(strict_types=1);

namespace Laminas\Filter\Compress;

/**
 * Compression interface
 *
 * @deprecated Since 2.40.0 Compression adapters will be split into multiple interfaces to clearly separate the
 *             capability of the underlying compression or archive format. For example, tar cannot compress strings and
 *             GZ cannot be used to create multi-file archives.
 */
interface CompressionAlgorithmInterface
{
    /**
     * Compresses $value with the defined settings
     *
     * @param  string $value Data to compress
     * @return string The compressed data
     */
    public function compress($value);

    /**
     * Decompresses $value with the defined settings
     *
     * @param  string $value Data to decompress
     * @return string The decompressed data
     */
    public function decompress($value);

    /**
     * Return the adapter name
     *
     * @return string
     */
    public function toString();
}
