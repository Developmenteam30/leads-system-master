<?php

namespace App\External\Convoso;

use Illuminate\Support\Facades\Log;

class APIRequest
{
    private $baseUrl = 'https://api.convoso.com/v1/';

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendRequest($endpoint, $payload)
    {
        $client = new \GuzzleHttp\Client();

        $response = $client->post($this->baseUrl.$endpoint, [
            'form_params' => array_merge([
                'auth_token' => config('convoso.api_key'),
            ], $payload),
        ]);

        return $response;
    }

    /**
     * @throws \Exception
     */
    public function getAgentPerformance($payload)
    {
        $response = $this->sendRequest('agent-performance/search', $payload);
        if (200 !== $response->getStatusCode()) {
            Log::error('APIRequest - ', [$response->getBody()]);
            throw new \Exception('Unable to get Agent Performance response.');
        }

        $json = json_decode($response->getBody());

        if (empty($json->success)) {
            throw new \Exception('Unable to decode Agent Performance response.');
        }

        if (!isset($json->data)) {
            throw new \Exception('Unable to decode Agent Performance response data.');
        }

        return $json->data;
    }

    /**
     * @throws \Exception
     */
    public function getCallLogs($payload)
    {
        $response = $this->sendRequest('log/retrieve', $payload);
        if (200 !== $response->getStatusCode()) {
            Log::error('APIRequest - ', [$response->getBody()]);
            throw new \Exception('Unable to get Call Logs response.');
        }

        $json = json_decode($response->getBody());

        if (empty($json->success)) {
            throw new \Exception('Unable to decode Call Logs response - '.($json->code ?? '').':'.($json->respnse ?? ''));
        }

        if (!isset($json->data)) {
            throw new \Exception('Unable to decode Call Logs response data.');
        }

        return $json->data;
    }

    /**
     * @throws \Exception
     */
    public function getAgentProductivity($payload)
    {
        $response = $this->sendRequest('agent-productivity/search', $payload);
        if (200 !== $response->getStatusCode()) {
            Log::error('APIRequest - ', [$response->getBody()]);
            throw new \Exception('Unable to get Agent Productivity response.');
        }

        $json = json_decode($response->getBody());

        if (empty($json->success)) {
            throw new \Exception('Unable to decode Agent Productivity response.');
        }

        if (!isset($json->data)) {
            throw new \Exception('Unable to decode Agent Productivity response data.');
        }

        return $json->data;
    }
}
