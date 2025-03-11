<?php

declare(strict_types=1);

/**
 * SPDX-License-Identifier: Apache-2.0
 *
 * The OpenSearch Contributors require contributions made to
 * this file be licensed under the Apache-2.0 license or a
 * compatible open source license.
 *
 * Modifications Copyright OpenSearch Contributors. See
 * GitHub history for details.
 */

namespace OpenSearch\Endpoints\Ml;

use OpenSearch\Exception\RuntimeException;
use OpenSearch\Endpoints\AbstractEndpoint;

/**
 * NOTE: This file is autogenerated using util/GenerateEndpoints.php
 */
class GetTool extends AbstractEndpoint
{
    protected $tool_name;

    public function getURI(): string
    {
        $tool_name = $this->tool_name ?? null;
        if (isset($tool_name)) {
            return "/_plugins/_ml/tools/$tool_name";
        }
        throw new RuntimeException('Missing parameter for the endpoint ml.get_tool');
    }

    public function getParamWhitelist(): array
    {
        return [
            'pretty',
            'human',
            'error_trace',
            'source',
            'filter_path'
        ];
    }

    public function getMethod(): string
    {
        return 'GET';
    }

    public function setToolName($tool_name): static
    {
        if (is_null($tool_name)) {
            return $this;
        }
        $this->tool_name = $tool_name;

        return $this;
    }
}
