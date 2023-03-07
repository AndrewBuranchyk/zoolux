<?php

namespace App\Services\ExternalApiRequests;

use Illuminate\Http\Client\Response;

interface ExternalApiRequestsInterface
{
    /**
     * Get API token for making requests
     *
     * @return string
     */
    public function getToken(): string;

    /**
     * Make request to external API
     *
     * @param array $requestParams
     * @param array $data
     * @return Response|bool
     */
    public function makeRequest(array $requestParams, array $data): Response|bool;
}
