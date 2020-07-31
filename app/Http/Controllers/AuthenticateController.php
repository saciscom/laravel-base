<?php

namespace App\Http\Controllers;

use App\Http\Repositories\UserRepository;
use App\Http\Requests\Core\Request;
use Exception;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateController extends Controller
{
    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Login route
     *
     * @param Request $request
     * @return mixed
     */
    public function login(Request $request)
    {
        $authenticate = $this->userRepository->authenticate($request->all());

        $this->setMeta(__('messages.request_success'))
            ->setData($authenticate['data']);

        return $this->jsonOut();
    }

    /**
     * Logout route
     *
     * @param Request $request
     * @return mixed
     */
    public function logout(Request $request)
    {
        $user = Auth::user();
        $this->userRepository->logout($user);
        return $this->setStatus(Response::HTTP_NO_CONTENT)->jsonOut();
    }

    /**
     * Me info route
     *
     * @return mixed
     */
    public function me()
    {
        $user = Auth::user();
        return $this->setStatus(Response::HTTP_OK)
            ->setMeta(__('messages.request_success'))
            ->setData($user)
            ->jsonOut();
    }

    /**
     * Store route
     *
     * @param Request $request request
     *
     * @return mixed
     * @throws Exception
     */
    public function store(Request $request)
    {
        $authenticate = $this->userRepository->checkRegister($request->all());
        $this->setMeta(__('messages.request_success'))
            ->setData($authenticate['data']);

        return $this->jsonOut();
    }
}
