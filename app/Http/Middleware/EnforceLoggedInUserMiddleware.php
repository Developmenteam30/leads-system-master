<?php

namespace App\Http\Middleware;

use App\Models\DialerAgent;
use App\Responses\ErrorResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnforceLoggedInUserMiddleware
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
        // If they do NOT have access to the $area_slugs provided, check the $agent_id parameter against the current logged-in user.
        if (empty($request->route('agent_id'))) {
            return $next($request);
        }

        /** @var DialerAgent $profile Profile of the authenticated user */
        $profile = Auth::user();
        $agent_id = intval($request->route('agent_id'));

        foreach ($area_slugs as $area_slug) {
            if ($profile->hasAccessToArea($area_slug)) {
                return $next($request);
            }
        }

        if (empty($profile->id) || $agent_id !== $profile->id) {
            return ErrorResponse::json('Access denied.', 403);
        }

        return $next($request);
    }
}
