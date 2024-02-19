<?php

namespace App\Http\Controllers;

use App\Helpers\MicrosoftGraph;
use App\Models\OAuthToken;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

class MicrosoftController extends BaseController
{
    public function index(Request $request)
    {
        $oauth_token = OAuthToken::where('provider', 'microsoft')
            ->where('identifier', 'sharepoint')
            ->first();
        if (empty($oauth_token)) {
            return 'No existing state found.';
        }

        if (empty($oauth_token->state)) {
            return 'Invalid state found.';
        }

        if (!$request->filled('state') || $request->input('state') !== $oauth_token->state) {
            return 'State does not match.';
        }

        $provider = MicrosoftGraph::getProvider();

        try {
            $access_token = $provider->getAccessToken('authorization_code', [
                'code' => $request->input('code'),
            ]);

            $oauth_token->state = null;
            $oauth_token->access_token = $access_token->getToken();
            $oauth_token->refresh_token = $access_token->getRefreshToken();
            $oauth_token->expires_at = $access_token->getExpires();
            $oauth_token->save();

            return 'Token saved!';
        } catch (IdentityProviderException $e) {
            return $e;
        }

    }
}
