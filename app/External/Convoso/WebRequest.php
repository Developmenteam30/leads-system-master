<?php

namespace App\External\Convoso;

use Illuminate\Support\Facades\Log;

class WebRequest
{
    private $baseUrl = 'https://admin-dt.convoso.com/';

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendRequest($endpoint, $payload)
    {
        $client = new \GuzzleHttp\Client();

        $jar = new \GuzzleHttp\Cookie\CookieJar;

        $response = $client->post('https://admin.convoso.com/login/check-login', [
            'cookies' => $jar,
            'form_params' => [
                'username' => config('convoso.username'),
                'password' => config('convoso.password'),
            ],
        ]);

        return $client->post($this->baseUrl.$endpoint, [
            'http_errors' => false,
            'cookies' => $jar,
            'json' => $payload,
            'allow_redirects' => false,
        ]);
    }

    /**
     * @throws \Exception
     */
    public function getLicenses()
    {
        $payload = [
            'page' => 0,
            'type' => 'AGENT',
            'limit' => 1000,
            'selectOptions' => [
                [
                    'name' => '',
                    'operator' => 'IN',
                    'value' => [],
                ],
            ],
            'orderOptions' => [
                'name' => 'last_assigned_at',
                'value' => 'DESC',
            ],
            'inputValue' => '',
        ];

        $response = $this->sendRequest('user/account/subscription/users-licenses', $payload);
        if (200 !== $response->getStatusCode()) {
            Log::error('APIRequest - ', [$response->getBody()]);
            throw new \Exception('Unable to get licenses response.');
        }

        $json = json_decode($response->getBody());

        if (empty($json->success)) {
            throw new \Exception('Unable to decode licenses response.');
        }

        return (object) [
            'summary' => $json->summary,
            'licences' => collect($json->licenses),
            'users' => collect($json->users),
            'assigned_licenses' => collect($json->assigned_licenses),
            'unassigned_licenses' => collect($json->unassigned_licenses),
        ];
    }
}
