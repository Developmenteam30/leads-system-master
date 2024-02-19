<?php

namespace App\External\OnScript;

use Illuminate\Support\Facades\Log;

class APIRequest
{
    private $baseUrl = 'https://app.onscript.ai/api/';

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendRequest($endpoint, $payload): \Psr\Http\Message\ResponseInterface
    {
        $client = new \GuzzleHttp\Client();

        return $client->get($this->baseUrl.$endpoint.'?'.http_build_query($payload), [
            'http_errors' => false,
        ]);
    }

    /**
     * @throws \Exception
     */
    public function submitRecording($payload)
    {
        $response = $this->sendRequest('create_process_dialog', $payload);
        if (!in_array($response->getStatusCode(), [200, 201])) {
            Log::error('APIRequest - ', [$response->getBody()]);
            throw new \Exception("API Error: {$response->getStatusCode()} {$response->getBody()}");
        }

        $json = @json_decode($response->getBody());

        if (empty($json->id)) {
            throw new \Exception('Unable to decode create_process_dialog response.');
        }

        return $json;
    }
}
