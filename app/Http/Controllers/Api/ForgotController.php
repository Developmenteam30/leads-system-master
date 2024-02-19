<?php

namespace App\Http\Controllers\Api;

use App\Models\AuditLog;
use App\Models\DialerAgent;
use App\Models\DialerForgotToken;
use App\Notifications\ForgotPassword;
use App\Notifications\WelcomeMessage;
use App\Responses\ErrorResponse;
use App\Validators\ApiJsonValidator;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ForgotController extends BaseController
{
    public function index(Request $request)
    {
        ApiJsonValidator::validate(
            $request->all(), [
                'email' => 'bail|required|email',
            ]
        );

        $agent = DialerAgent::query()
            ->where('email', $request->input('email'))
            ->canLogin()
            ->select(['dialer_agents.*'])
            ->first();

        try {
            self::generateForgotToken(
                agent: $agent,
            );
        } catch (\Throwable $e) {
            return ErrorResponse::json($e->getMessage(), 400);
        }

        return response([]);
    }

    public function validate(Request $request)
    {
        ApiJsonValidator::validate(
            $request->all(), [
                'token' => 'bail|required|string',
            ]
        );

        // Try to find the entry in the token table
        $token = DialerForgotToken::query()
            ->where('token', $request->input('token'))
            ->where('created_at', '>=', DB::raw('DATE_SUB(NOW(),INTERVAL 12 HOUR)'))
            ->select(['agent_id'])
            ->first();

        if (!$token) {
            return ErrorResponse::json('The reset link you provided was not found or has expired.', 400);
        }

        return response([]);
    }

    public function reset(Request $request)
    {
        ApiJsonValidator::validate(
            $request->all(), [
                'token' => 'bail|required|string',
                'password' => 'bail|required|string|min:8|max:64',
                'passwordConfirmation' => 'bail|required|string|same:password',
            ]
        );

        // Try to find the entry in the token table
        $token = DialerForgotToken::query()
            ->where('token', $request->input('token'))
            ->where('created_at', '>=', DB::raw('DATE_SUB(NOW(),INTERVAL 12 HOUR)'))
            ->select(['agent_id'])
            ->first();

        if (!$token) {
            return ErrorResponse::json('The reset link you provided was not found or has expired.', 400);
        }

        // Try to find the entry in the user table
        $agent = DialerAgent::query()
            ->where('dialer_agents.id', $token->agent_id)
            ->canLogin()
            ->select(['dialer_agents.*'])
            ->first();

        // If the agent was not found, throw an error and abort
        if (empty($agent)) {
            return ErrorResponse::json('That account was not found in our database.', 400);
        }

        try {
            DB::beginTransaction();

            // Remove all recovery tokens for this user
            DialerForgotToken::where('agent_id', $agent->id)
                ->delete();

            // Update the password
            $agent->password = Hash::make($request->input('password'));
            $agent->save();

            DB::commit();

            return response([]);
        } catch (\Exception $e) {
            DB::rollback();

            return ErrorResponse::json($e->getMessage(), 400);
        }
    }

    /**
     * Generate a forgot password token
     *
     * @param  DialerAgent  $agent
     * @param  bool  $welcome_message
     * @throws \Throwable
     */
    public static function generateForgotToken(DialerAgent $agent, bool $welcome_message = false): void
    {
        // Randomly generate a token for the user
        try {
            $token = strtr(base64_encode(Str::random(128)), '+/', '-_');
        } catch (\Throwable $e) {
            throw $e;
        }

        if ($agent) {
            try {
                DB::beginTransaction();

                // Remove any unused recovery tokens for this user
                DialerForgotToken::where('agent_id', $agent->id)
                    ->delete();

                $request = request();
                AuditLog::createFromRequest($request, 'DIALER-PASSWORD:FORGOT', [
                    'agent_id' => $agent->id,
                    'user_agent' => $request->server('HTTP_USER_AGENT'),
                ]);

                // Add the new recovery token to the database
                $forgot = new DialerForgotToken();
                $forgot->agent_id = $agent->id;
                $forgot->token = $token;
                $forgot->ip_address = $request->ip();
                $forgot->save();

                if ($welcome_message) {
                    $agent->notify(new WelcomeMessage($token));
                } else {
                    $agent->notify(new ForgotPassword($token));
                }

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollback();

                throw $e;
            }
        }
    }
}
