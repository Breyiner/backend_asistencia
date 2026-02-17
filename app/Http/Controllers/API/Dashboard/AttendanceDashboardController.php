<?php

namespace App\Http\Controllers\API\Dashboard;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\AttendanceDashboardRequest;
use App\Services\Dashboard\AttendanceDashboardService;

class AttendanceDashboardController extends Controller
{
    public function __construct(private AttendanceDashboardService $service) {}

    public function __invoke(AttendanceDashboardRequest $request)
    {
        $result = $this->service->get($request->validated());

        if (!empty($result['error'])) {
            return ResponseFormatter::error($result['message'] ?? 'Error', $result['code']);
        }

        return ResponseFormatter::success(
            $result['message'],
            $result['code'],
            $result['data'] ?? [],
            $result['paginate'] ?? [],
            $result['meta'] ?? []
        );
    }
}