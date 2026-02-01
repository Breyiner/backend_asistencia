<?php

namespace App\Http\Controllers\API\Attendance;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\ScanAttendanceRequest;
use App\Http\Requests\Attendance\StoreAttendanceRequest;
use App\Http\Requests\Attendance\UpdateAttendanceRequest;
use App\Services\Attendance\AttendanceService;

class AttendanceController extends Controller
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function index()
    {
        $response = $this->attendanceService->getAll();

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function store(StoreAttendanceRequest $request)
    {
        $response = $this->attendanceService->create($request->validated());

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function show(int $id)
    {
        $response = $this->attendanceService->getById($id);

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function byClassRealId(int $realClassId)
    {
        $response = $this->attendanceService->byClassRealId($realClassId);

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success(
            $response['message'],
            $response['code'],
            $response['data'] ?? [],
            $response['paginate'] ?? [],
            $response['summary']
        );
    }

    public function update(UpdateAttendanceRequest $request, $id)
    {
        $result = $this->attendanceService->update($request->all(), $id);

        if (!empty($result['error'])) {
            return ResponseFormatter::error(
                $result['message'] ?? 'Error',
                $result['code'] ?? 400,
                $result['errors'] ?? [],
                $result['errorKey'] ?? null
            );
        }

        return ResponseFormatter::success(
            $result['message'] ?? 'Operación exitosa',
            $result['code'] ?? 200,
            $result['data'] ?? null,
            $result['paginate'] ?? [],
            $result['summary'] ?? []
        );
    }

    public function scan(ScanAttendanceRequest $request)
    {
        $response = $this->attendanceService->scanCheckIn(
            $request->validated()
        );

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function destroy(int $id)
    {
        $response = $this->attendanceService->delete($id);

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }
}
