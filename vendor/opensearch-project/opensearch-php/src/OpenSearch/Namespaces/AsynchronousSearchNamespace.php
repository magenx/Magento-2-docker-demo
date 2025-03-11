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

namespace OpenSearch\Namespaces;

use OpenSearch\Endpoints\AsynchronousSearch\Delete;
use OpenSearch\Endpoints\AsynchronousSearch\Get;
use OpenSearch\Endpoints\AsynchronousSearch\Search;
use OpenSearch\Endpoints\AsynchronousSearch\Stats;

/**
 * Class AsynchronousSearchNamespace
 *
 * NOTE: This file is autogenerated using util/GenerateEndpoints.php
 */
class AsynchronousSearchNamespace extends AbstractNamespace
{
    /**
     * Deletes any responses from an asynchronous search.
     *
     * $params['id']          = (string)  (Required)
     * $params['pretty']      = (boolean) Whether to pretty format the returned JSON response. (Default = false)
     * $params['human']       = (boolean) Whether to return human readable values for statistics. (Default = true)
     * $params['error_trace'] = (boolean) Whether to include the stack trace of returned errors. (Default = false)
     * $params['source']      = (string) The URL-encoded request definition. Useful for libraries that do not accept a request body for non-POST requests.
     * $params['filter_path'] = (any) Used to reduce the response. This parameter takes a comma-separated list of filters. It supports using wildcards to match any field or part of a field’s name. You can also exclude fields with "-".
     *
     * @param array $params Associative array of parameters
     * @return array
     */
    public function delete(array $params = [])
    {
        $id = $this->extractArgument($params, 'id');

        $endpoint = $this->endpointFactory->getEndpoint(Delete::class);
        $endpoint->setParams($params);
        $endpoint->setId($id);

        return $this->performRequest($endpoint);
    }

    /**
     * Gets partial responses from an asynchronous search.
     *
     * $params['id']          = (string)  (Required)
     * $params['pretty']      = (boolean) Whether to pretty format the returned JSON response. (Default = false)
     * $params['human']       = (boolean) Whether to return human readable values for statistics. (Default = true)
     * $params['error_trace'] = (boolean) Whether to include the stack trace of returned errors. (Default = false)
     * $params['source']      = (string) The URL-encoded request definition. Useful for libraries that do not accept a request body for non-POST requests.
     * $params['filter_path'] = (any) Used to reduce the response. This parameter takes a comma-separated list of filters. It supports using wildcards to match any field or part of a field’s name. You can also exclude fields with "-".
     *
     * @param array $params Associative array of parameters
     * @return array
     */
    public function get(array $params = [])
    {
        $id = $this->extractArgument($params, 'id');

        $endpoint = $this->endpointFactory->getEndpoint(Get::class);
        $endpoint->setParams($params);
        $endpoint->setId($id);

        return $this->performRequest($endpoint);
    }

    /**
     * Performs an asynchronous search.
     *
     * $params['index']                       = (string) The name of the index to be searched. Can be an individual name, a comma-separated list of indexes, or a wildcard expression of index names.
     * $params['keep_alive']                  = (string) The amount of time that the result is saved in the cluster. For example, `2d` means that the results are stored in the cluster for 48 hours. The saved search results are deleted after this period or if the search is canceled. Note that this includes the query execution time. If the query exceeds this amount of time, the process cancels this query automatically.
     * $params['keep_on_completion']          = (boolean) Whether to save the results in the cluster after the search is complete. You can examine the stored results at a later time.
     * $params['wait_for_completion_timeout'] = (string) The amount of time to wait for the results. You can poll the remaining results based on an ID. The maximum value is 300 seconds. Default is `1s`.
     * $params['pretty']                      = (boolean) Whether to pretty format the returned JSON response. (Default = false)
     * $params['human']                       = (boolean) Whether to return human readable values for statistics. (Default = true)
     * $params['error_trace']                 = (boolean) Whether to include the stack trace of returned errors. (Default = false)
     * $params['source']                      = (string) The URL-encoded request definition. Useful for libraries that do not accept a request body for non-POST requests.
     * $params['filter_path']                 = (any) Used to reduce the response. This parameter takes a comma-separated list of filters. It supports using wildcards to match any field or part of a field’s name. You can also exclude fields with "-".
     *
     * @param array $params Associative array of parameters
     * @return array
     */
    public function search(array $params = [])
    {
        $body = $this->extractArgument($params, 'body');

        $endpoint = $this->endpointFactory->getEndpoint(Search::class);
        $endpoint->setParams($params);
        $endpoint->setBody($body);

        return $this->performRequest($endpoint);
    }

    /**
     * Monitors any asynchronous searches that are `running`, `completed`, or `persisted`.
     *
     * $params['pretty']      = (boolean) Whether to pretty format the returned JSON response. (Default = false)
     * $params['human']       = (boolean) Whether to return human readable values for statistics. (Default = true)
     * $params['error_trace'] = (boolean) Whether to include the stack trace of returned errors. (Default = false)
     * $params['source']      = (string) The URL-encoded request definition. Useful for libraries that do not accept a request body for non-POST requests.
     * $params['filter_path'] = (any) Used to reduce the response. This parameter takes a comma-separated list of filters. It supports using wildcards to match any field or part of a field’s name. You can also exclude fields with "-".
     *
     * @param array $params Associative array of parameters
     * @return array
     */
    public function stats(array $params = [])
    {
        $endpoint = $this->endpointFactory->getEndpoint(Stats::class);
        $endpoint->setParams($params);

        return $this->performRequest($endpoint);
    }

}
