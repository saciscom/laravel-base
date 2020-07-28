<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateController extends Controller
{

    public function login(Request $request)
    {
        $authenticate = $this->authenticate($request->all());

        $this->setMeta(__('messages.request_success'))
            ->setData($authenticate['data']);

        return $this->jsonOut();
    }

    public function authenticate(array $data)
    {
        $credentials = [
            'uuid' => $data['uuid'] ?? null,
            'email' => $data['email'],
            'password' => $data['password']
        ];
        return $this->checkAuth($credentials);
    }

    private function checkAuth(array $credentials)
    {
        $loginLogs = DB::table('login_logs')
            ->where('uuid', $credentials['uuid'])
            ->where('type', User::LOGIN_FAIL)
            ->where('created_at', '>=', date('Y-m-d H:i:s', strtotime("-30 minutes")))
            ->count();

        // Block if user login fail 5 times in 30 minutes
        if ($loginLogs >= User::LOGIN_FAIL_LIMIT_TIMES) {
            throw new AuthenticationException(trans('auth.errors.login-fail-limited'));
        }

        $user = User::where('email', $credentials['email'])->first();
        $authenticateAble = $user ? true : false;

        if ($user && !Hash::check($credentials['password'], $user->password)) {
            // Check for old user login
            if (md5($credentials['password']) == $user->password) {
                // Update hash for old user
                $user->password = Hash::make($credentials['password']);
                $user->save();
            } else {
                $authenticateAble = false;
            }
        }

        $credentials['password'] = Hash::make($credentials['password']);
        $credentials['created_at'] = date('Y-m-d H:i:s');
        if ($authenticateAble) {
            $objToken = $user->createToken(config('token.token.key'));
            $token = $objToken->accessToken;
            DB::table('login_logs')->insert($credentials);

            return [
                'data' => [
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => $objToken->token->expires_at->timestamp,
                    'user_id' => $user->id
                ]
            ];
        }

        // Save login fail to logs DB
        $credentials['type'] = User::LOGIN_FAIL;
        DB::table('login_logs')->insert($credentials);

        throw new AuthenticationException(trans('auth.errors.login-unauthenticated'));
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        $accessToken = $user->token();

        DB::table('oauth_refresh_tokens')
            ->where('access_token_id', $accessToken->id)
            ->update([
                'revoked' => true
            ]);
        $accessToken->revoke();
        DB::table('login_logs')->insert([
            'email' => $user->email,
            'password' => $user->password,
            'type' => User::LOGOUT,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->setStatus(Response::HTTP_NO_CONTENT)->jsonOut();
    }

    public function me()
    {
        $user = Auth::user();

        return $this->setStatus(Response::HTTP_OK)
            ->setMeta(__('messages.request_success'))
            ->setData($user)
            ->jsonOut();
    }

    public function register(Request $request)
    {
        $authenticate = $this->checkRegister($request->all());

        $this->setMeta(__('messages.request_success'))
            ->setData($authenticate['data']);

        return $this->jsonOut();
    }

    public function checkRegister(array $data)
    {
        $name = $data['name'];
        $email = $data['email'];
        $password = $data['password'];
        $credentials = [
            'email' => $email,
            'password' => $password,
            'uuid' => null
        ];
        DB::beginTransaction();
        try {
            $user = new User();
            $user['name'] = $name;
            $user['email'] = $email;
            $user['password'] = Hash::make($password);;
            $user->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }

        return $this->checkAuth($credentials);
    }
}
