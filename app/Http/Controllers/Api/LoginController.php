<?php

namespace App\Http\Controllers\Api;

use App\Models\AuditLog;
use App\Models\DialerAgent;
use App\Responses\ErrorResponse;
use App\Validators\ApiJsonValidator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\HS256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;

class LoginController extends BaseController
{
    public function index(Request $request)
    {
        ApiJsonValidator::validate(
            $request->all(), [
                'username' => 'bail|required|string',
                'password' => 'bail|required|string',
                'access_role_id' => 'bail|nullable|numeric',
            ]
        );

        $credentials = [
            'email' => $request->input('username'),
            'password' => $request->input('password'),
            fn(Builder $query) => $query->canLogin()->select(['dialer_agents.*']),
        ];

        if (!Auth::attempt($credentials)) {
            return ErrorResponse::json('Invalid username or password.', 403);
        }

        /** @var DialerAgent $profile Profile of the authenticated user */
        $profile = Auth::user();
        if (Hash::needsRehash($profile->password)) {
            $profile->password = Hash::make($request->input('password'));
            $profile->save();
        }

        AuditLog::createFromRequest($request, 'LOGIN', [
            'user_agent' => $request->server('HTTP_USER_AGENT'),
        ]);

        return $this->setJwt($request, $profile);
    }

    public function impersonate(Request $request, $agent_id)
    {
        ApiJsonValidator::validate(
            $request->route()->parameters(), [
                'agent_id' => 'required|bail|exists:dialer_agents,id',
            ]
        );

        $from_agent_id = $request->user()->id ?? null;

        Auth::login(DialerAgent::find($agent_id));

        /** @var DialerAgent $profile Profile of the authenticated user */
        $profile = Auth::user();

        AuditLog::createFromRequest($request, 'LOGIN:IMPERSONATE', [
            'user_agent' => $request->server('HTTP_USER_AGENT'),
            'from_agent_id' => $from_agent_id,
        ]);

        return $this->setJwt($request, $profile);
    }

    private function setJwt(Request $request, DialerAgent $profile)
    {
        // The algorithm manager with the HS256 algorithm.
        $algorithmManager = new AlgorithmManager([
            new HS256(),
        ]);

        // The PNT key.
        $jwk = JWKFactory::createFromSecret(
            config('settings.jwt.secret'),       // The shared secret
            [
                'alg' => config('settings.jwt.algorithm'),
                'use' => 'sig',
            ]
        );

        // We instantiate our JWS Builder.
        $jwsBuilder = new JWSBuilder($algorithmManager);

        // Allow overriding the access role on the development site
        if (App::environment('development') && $request->filled('access_role_id')) {
            $profile->access_role_id = $request->input('access_role_id');
        }

        $payload = json_encode([
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + (60 * 60 * 12),
            'iss' => config('app.url'),
            'aud' => config('app.name'),
            'uid' => $profile->id,
            'ari' => $profile->access_role_id,
            'access_areas' => $profile->accessAreasList,
        ]);

        $jws = $jwsBuilder
            ->create()                               // We want to create a new JWS
            ->withPayload($payload)                  // We set the payload
            ->addSignature($jwk, ['alg' => config('settings.jwt.algorithm')]) // We add a signature with a simple protected header
            ->build();                               // We build it

        $serializer = new CompactSerializer(); // The serializer

        $token = $serializer->serialize($jws, 0); // We serialize the signature at index 0 (we only have one signature).

        return response([
            'token' => $token,
            'accessAreas' => $profile->accessAreasList,
            'agent' => $profile->only([
                'id',
                'team_id',
                'access_role_id',
            ]),
        ]);
    }
}
