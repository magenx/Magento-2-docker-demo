<?php

declare(strict_types=1);

namespace Laminas\Filter;

/**
 * Implement this interface within Module classes to indicate that your module
 * provides filter configuration for the FilterPluginManager.
 *
 * @deprecated Since 2.40.0 This interface will be removed in version 3.0 without replacement
 */
interface FilterProviderInterface
{
    /**
     * Provide plugin manager configuration for filters.
     *
     * @return array
     */
    public function getFilterConfig();
}
