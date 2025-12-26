<?php

namespace App\Http\Controllers\API\EmailVerification;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
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
        $res = $this->service->verify($id, $hash);

        if ($res['error']) return ResponseFormatter::error($res['message'], $res['code']);

        return ResponseFormatter::success($res['message'], $res['code'], $res['data'] ?? null);
    }
    
    public function resend(Request $request)
    {
        $res = $this->service->resend($request->user());

        if ($res['error']) return ResponseFormatter::error($res['message'], $res['code']);

        return ResponseFormatter::success($res['message'], $res['code'], $res['data'] ?? null);
    }
}

