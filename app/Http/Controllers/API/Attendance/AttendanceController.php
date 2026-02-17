<?php

namespace App\Http\Controllers\API\Attendance;

use App\Exports\MonthlyRegisterExport;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\MonthlyAttendanceRegisterRequest;
use App\Http\Requests\Attendance\ScanAttendanceRequest;
use App\Http\Requests\Attendance\StoreAttendanceRequest;
use App\Http\Requests\Attendance\UpdateAttendanceRequest;
use App\Services\Attendance\AttendanceService;
use App\Services\Attendance\MonthlyAttendanceRegisterService;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

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

    public function exportMonthlyRegister(MonthlyAttendanceRegisterRequest $request, MonthlyAttendanceRegisterService $service)
    {
        $data = $request->validated();

        $result = $service->monthlyRegister($data);

        if (!empty($result['error'])) {
            return response()->json($result, $result['code'] ?? 400);
        }

        $payload = $result['data'];
        $fileName = "registro_mensual_{$payload['period']['year']}_{$payload['period']['month']}_ficha_{$payload['ficha']['id']}.xlsx";

        return Excel::download(new MonthlyRegisterExport($payload), $fileName);
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
