<?php

namespace App\Http\Controllers\API\AttendanceStatus;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceStatus\StoreAttendanceStatusRequest;
use App\Http\Requests\AttendanceStatus\UpdateAttendanceStatusRequest;
use App\Services\AttendanceStatus\AttendanceStatusService;

class AttendanceStatusController extends Controller
{
    protected $attendanceStatusService;

    public function __construct(AttendanceStatusService $attendanceStatusService)
    {
        $this->attendanceStatusService = $attendanceStatusService;
    }

    public function index()
    {
        $response = $this->attendanceStatusService->getAll();
        if ($response['error']) return ResponseFormatter::error($response['message'], $response['code']);
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function store(StoreAttendanceStatusRequest $request)
    {
        $response = $this->attendanceStatusService->create($request->validated());
        if ($response['error']) return ResponseFormatter::error($response['message'], $response['code']);
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function show(int $id)
    {
        $response = $this->attendanceStatusService->getById($id);
        if ($response['error']) return ResponseFormatter::error($response['message'], $response['code']);
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function update(UpdateAttendanceStatusRequest $request, int $id)
    {
        $response = $this->attendanceStatusService->update($request->validated(), $id);
        if ($response['error']) return ResponseFormatter::error($response['message'], $response['code']);
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function destroy(int $id)
    {
        $response = $this->attendanceStatusService->delete($id);
        if ($response['error']) return ResponseFormatter::error($response['message'], $response['code']);
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }
}