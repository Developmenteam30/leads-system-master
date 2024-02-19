<?php

namespace App\Http\Middleware;

use App\Models\DialerAgent;
use App\Responses\ErrorResponse;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Jose\Component\Checker\AlgorithmChecker;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Checker\ExpirationTimeChecker;
use Jose\Component\Checker\HeaderCheckerManager;
use Jose\Component\Checker\IssuedAtChecker;
use Jose\Component\Checker\IssuerChecker;
use Jose\Component\Checker\NotBeforeChecker;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\HS256;
use Jose\Component\Signature\JWSLoader;
use Jose\Component\Signature\JWSTokenSupport;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Signature\Serializer\JWSSerializerManager;

class JwtMiddleware
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  mixed  ...$area_slugs
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$area_slugs)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return ErrorResponse::json('Token not provided.', 401);
        }

        try {
            $jwk = JWKFactory::createFromSecret(
                config('settings.jwt.secret'),       // The shared secret
                [
                    'alg' => config('settings.jwt.algorithm'),
                    'use' => 'sig',
                ]
            );

            // Verify the headers of the token
            $headerCheckerManager = new HeaderCheckerManager(
                [
                    new AlgorithmChecker([config('settings.jwt.algorithm')]), // We check the header "alg" (algorithm)
                ],
                [
                    new JWSTokenSupport(), // Adds JWS token type support
                ]
            );

            $claimCheckerManager = new ClaimCheckerManager([
                new IssuedAtChecker(),
                new NotBeforeChecker(),
                new ExpirationTimeChecker(),
                new IssuerChecker([config('app.url')]),
            ]);

            // Unserialize the token
            $serializerManager = new JWSSerializerManager(
                [
                    new CompactSerializer(),
                ]
            );

            // Specify the signature algorithm
            $algorithmManager = new AlgorithmManager(
                [
                    new HS256(),
                ]
            );

            // We instantiate our JWS Verifier.
            $jwsVerifier = new JWSVerifier(
                $algorithmManager
            );

            $jwsLoader = new JWSLoader(
                $serializerManager,
                $jwsVerifier,
                $headerCheckerManager
            );

            // Load and verify the token against the keyset
            $jws = $jwsLoader->loadAndVerifyWithKey($token, $jwk, $signature);

            $claims = json_decode($jws->getPayload(), true);

            $claimCheckerManager->check($claims, ['iat', 'nbf', 'exp', 'iss', 'aud', 'uid']);

            $profile = DialerAgent::query()
                ->where('dialer_agents.id', $claims['uid'])
                ->whereNotNull('dialer_agents.access_role_id')
                ->isActiveForDate(now(config('settings.timezone.local'))->format('Y-m-d'))
                ->select(['dialer_agents.*'])
                ->first();

            if (!$profile) {
                return ErrorResponse::json('User account not found.', 403);
            }

            if (!empty($area_slugs)) {

                // Allow overriding the access role on the development site
                if (App::environment('development') && !empty($claims['ari'])) {
                    $profile->access_role_id = $claims['ari'];
                }

                $hasAccess = false;
                foreach ($area_slugs as $area_slug) {
                    if ($profile->hasAccessToArea($area_slug)) {
                        $hasAccess = true;
                        break;
                    }
                }

                if (!$hasAccess) {
                    return ErrorResponse::json('Unauthorized by access flags', 401);
                }
            }

            Auth::login($profile);
        } catch (Exception $e) {
            return ErrorResponse::json('Token error: '.$e->getMessage(), 401);
        }

        return $next($request);
    }
}
