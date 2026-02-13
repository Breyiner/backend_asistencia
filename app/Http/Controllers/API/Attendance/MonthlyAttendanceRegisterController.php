<?php

namespace App\Http\Controllers\API\Attendance;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\MonthlyAttendanceRegisterRequest;
use App\Services\Attendance\MonthlyAttendanceRegisterService;

class MonthlyAttendanceRegisterController extends Controller
{
    protected $service;

    public function __construct(MonthlyAttendanceRegisterService $service)
    {
        $this->service = $service;
    }

    public function show(MonthlyAttendanceRegisterRequest $request)
    {
        $response = $this->service->monthlyRegister($request->validated());

        if (!empty($response['error'])) {
            return ResponseFormatter::error(
                $response['message'] ?? 'Error',
                $response['code'] ?? 400,
                $response['errors'] ?? [],
                $response['errorKey'] ?? null
            );
        }

        return ResponseFormatter::success(
            $response['message'] ?? 'Operación exitosa',
            $response['code'] ?? 200,
            $response['data'] ?? [],
            $response['paginate'] ?? [],
            $response['summary'] ?? []
        );
    }
}