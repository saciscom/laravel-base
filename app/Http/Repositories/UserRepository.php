<?php

namespace App\Http\Repositories;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Prettus\Repository\Eloquent\BaseRepository;

class UserRepository extends BaseRepository
{
    /**
     * Define model
     *
     * @return string
     */
    public function model()
    {
        return User::class;
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

    private function checkAuth(array $credentials)
    {
        $loginFailCount = $this->loginFailCount($credentials['uuid']);

        // Block if user login fail 5 times in 30 minutes
        if ($loginFailCount >= User::LOGIN_FAIL_LIMIT_TIMES) {
            throw new AuthenticationException(trans('auth.errors.login-fail-limited'));
        }

        $credentials['created_at'] = date('Y-m-d H:i:s');

        $user = User::where('email', $credentials['email'])->first();

        if (!isset($user)) {
            $credentials['type'] = User::LOGIN_FAIL;
            DB::table('login_logs')->insert($credentials);
            throw new AuthenticationException(trans('auth.errors.user-not-exist'));
        }

        if (!Hash::check($credentials['password'], $user->password)) {
            $credentials['type'] = User::LOGIN_FAIL;
            DB::table('login_logs')->insert($credentials);
            throw new AuthenticationException(trans('auth.errors.wrong-password'));
        }

        $credentials['password'] = Hash::make($credentials['password']);
        DB::table('login_logs')->insert($credentials);

        $objToken = $user->createToken(config('token.token.key'));
        $token = $objToken->accessToken;
        return [
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => $objToken->token->expires_at->timestamp,
                'user_id' => $user->id
            ]
        ];
    }

    private function loginFailCount($uuid)
    {
        DB::table('login_logs')
            ->where('uuid', $uuid)
            ->where('type', User::LOGIN_FAIL)
            ->where('created_at', '>=', date('Y-m-d H:i:s', strtotime("-30 minutes")))
            ->count();
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

    public function logout(User $user)
    {
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
    }
}
