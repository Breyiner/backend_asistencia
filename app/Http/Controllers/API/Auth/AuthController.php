<?php

namespace App\Http\Controllers\API\Auth;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{

    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request)
    {

        $data = $request;

        $response = $this->authService->register($data);

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success($response['message'], $response['code'], $response['data']);
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        $result = $this->authService->login($credentials);

        if ($result['error'])
            return ResponseFormatter::error($result['message'], $result['code'], $result['errors'] ?? [], $result['errorKey'] ?? null);


        $cookieToken = $result['data']['cookieToken'];
        $cookieRefresh = $result['data']['cookieRefreshToken'];

        return ResponseFormatter::success(
            $result['message'],
            $result['code'],
            array_diff_key($result['data'], array_flip(['cookieToken', 'cookieRefreshToken',]))
        )->cookie($cookieToken)
            ->cookie($cookieRefresh);
    }

    public function refreshToken(Request $request)
    {
        $user = Auth::user();

        $currentRefreshToken = $request->bearerToken();

        $result = $this->authService->refreshToken($currentRefreshToken, $user);

        if ($result['error'])
            return ResponseFormatter::error($result['message'], $result['code'], $result['errors'] ?? [], $result['errorKey'] ?? null);

        $cookieToken = $result['data']['cookieToken'];
        $cookieRefresh = $result['data']['cookieRefreshToken'];

        $accessToken = $result['data']['accessToken'];
        $refreshToken = $result['data']['refreshToken'];

        return ResponseFormatter::success(
            $result['message'],
            $result['code'],
            [
                'token' => $accessToken,
                'refreshToken' => $refreshToken
            ]
        )->cookie($cookieToken)
            ->cookie($cookieRefresh);
    }

    public function logOut(Request $request)
    {
        $user = Auth::user();

        $expiredCookies = $this->authService->createExpiredCookies();

        $result = $this->authService->logOut($user);

        return ResponseFormatter::success(
            $result['message'],
            $result['code'],
            $result['data']
        )->cookie($expiredCookies['expiredAccessToken'])
            ->cookie($expiredCookies['expiredRefreshToken']);
    }
}
