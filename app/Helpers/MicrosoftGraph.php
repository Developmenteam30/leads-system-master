<?php

namespace App\Helpers;

use App\Models\OAuthToken;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Microsoft\Graph\Exception\GraphException;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model\UploadSession;

class MicrosoftGraph
{
    const PROVIDER_NAME = 'microsoft';

    public static function getProvider(): \League\OAuth2\Client\Provider\GenericProvider
    {
        return new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId' => config('microsoft.oauth.app_id'),
            'clientSecret' => config('microsoft.oauth.app_secret'),
            'redirectUri' => config('microsoft.oauth.redirect_uri'),
            'urlAuthorize' => config('microsoft.oauth.authority').config('microsoft.oauth.authorize_endpoint'),
            'urlAccessToken' => config('microsoft.oauth.authority').config('microsoft.oauth.token_endpoint'),
            'urlResourceOwnerDetails' => '',
            'scopes' => config('microsoft.oauth.scopes'),
        ]);
    }

    /**
     * @param  Graph  $graph  Graph client
     * @param  string  $siteId  site Id
     * @param  string  $itemId  item Id
     * @param  string  $inputPath  input path for a file
     * @param  string  $destinationFile  name of the file on SharePoint
     * @throws GraphException
     * @throws GuzzleException
     */
    public static function uploadLargeFile(Graph $graph, string $siteId, string $itemId, string $inputPath, string $destinationFile): void
    {
        /** @var UploadSession $uploadSession */
        $uploadSession = $graph->createRequest("POST",
            "/sites/$siteId/drive/items/$itemId:/$destinationFile:/createUploadSession")
            ->addHeaders(["Content-Type" => "application/json"])
            ->attachBody([
                "item" => [
                    "@microsoft.graph.conflictBehavior" => "replace",
                ],
            ])
            ->setReturnType(UploadSession::class)
            ->execute();


        $handle = fopen($inputPath, 'rb');
        $fileSize = fileSize($inputPath);
        $prevBytesRead = 0;
        $chunkSize = 1024 * 1024;

        while (!feof($handle)) {
            $bytes = fread($handle, $chunkSize + 1);
            $bytesRead = ftell($handle);

            $resp = $graph->createRequest("PUT", $uploadSession->getUploadUrl())
                ->addHeaders([
                    'Connection' => "keep-alive",
                    'Content-Length' => ($bytesRead - $prevBytesRead),
                    'Content-Range' => "bytes ".$prevBytesRead."-".($bytesRead - 1)."/".$fileSize,
                ])
                ->setReturnType(UploadSession::class)
                ->attachBody($bytes)
                ->execute();

            $prevBytesRead = $bytesRead;
        }
        fclose($handle);
    }

    /**
     * @throws IdentityProviderException
     * @throws Exception
     */
    public static function getOrRefreshAccessToken($identifier): \League\OAuth2\Client\Token\AccessTokenInterface|AccessToken
    {
        $provider = self::getProvider();

        $oauth_token = OAuthToken::where('provider', self::PROVIDER_NAME)
            ->where('identifier', $identifier)
            ->first();

        if (empty($oauth_token)) {
            $authorizationUrl = $provider->getAuthorizationUrl();

            $oauth_token = new OAuthToken();
            $oauth_token->provider = self::PROVIDER_NAME;
            $oauth_token->identifier = $identifier;
            $oauth_token->state = $provider->getState();
            $oauth_token->save();

            throw new Exception("API Token not found in database.  Please authenticate at: {$authorizationUrl}");
        }

        if (empty($oauth_token->access_token) || empty($oauth_token->refresh_token) || empty($oauth_token->expires_at)) {
            throw new Exception('Invalid token in database.');
        }

        $access_token = new AccessToken([
            'access_token' => $oauth_token->access_token,
            'refresh_token' => $oauth_token->refresh_token,
            'expires' => $oauth_token->expires_at->getTimestamp(),
        ]);

        if ($access_token->hasExpired()) {
            $access_token = $provider->getAccessToken('refresh_token', [
                'refresh_token' => $access_token->getRefreshToken(),
            ]);

            $oauth_token->state = null;
            $oauth_token->access_token = $access_token->getToken();
            $oauth_token->refresh_token = $access_token->getRefreshToken();
            $oauth_token->expires_at = $access_token->getExpires();
            $oauth_token->save();
        }

        return $access_token;
    }
}
