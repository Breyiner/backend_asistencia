<?php

namespace App\Http\Controllers\API\Auth;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\PasswordReset\ForgotPasswordRequest;
use App\Http\Requests\PasswordReset\ResetPasswordRequest;
use App\Services\PasswordReset\PasswordResetService;
use Illuminate\Http\Request;

class PasswordResetController extends Controller
{
    public function __construct(protected PasswordResetService $service) {}

    public function forgot(ForgotPasswordRequest $request)
    {
        $response = $this->service->forgot($request->validated());

        return $response['error']
            ? ResponseFormatter::error($response['message'], $response['code'])
            : ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function reset(ResetPasswordRequest $request)
    {
        $response = $this->service->reset($request->validated());

        return $response['error']
            ? ResponseFormatter::error($response['message'], $response['code'])
            : ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }
}