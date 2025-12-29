<?php

namespace App\Http\Controllers\API\EmailVerification;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\ResendVerification\ResendVerificationRequest;
use App\Services\EmailVerification\EmailVerificationService;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function __construct(protected EmailVerificationService $service) {}

    public function notice()
    {
        return ResponseFormatter::error('Debes verificar tu correo.', 403);
    }

    public function verify(Request $request, string $id, string $hash)
    {
        $response = $this->service->verify($id, $hash);

        if ($response['error']) return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? null);
    }

    public function resend(ResendVerificationRequest $request)
    {

        $data = $request->validated();

        $response = $this->service->resend($data);

        if ($response['error']) return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? null);
    }
}

