<?php

namespace App\Http\Controllers\API\Schedule;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Schedule\StoreScheduleRequest;
use App\Http\Requests\Schedule\UpdateScheduleRequest;
use App\Services\Schedule\ScheduleService;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    protected $scheduleService;

    public function __construct(ScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    public function index()
    {
        $response = $this->scheduleService->getAll();

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'],  $response['code'],  $response['data'] ?? []);
    }

    public function show(int $id)
    {
        $response = $this->scheduleService->getById($id);

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'],  $response['code'],  $response['data'] ?? []);
    }

    public function showByFichaTerm(int $fichaTermId)
    {
        $response = $this->scheduleService->getByFichaTermId($fichaTermId);

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success(
            $response['message'],
            $response['code'],
            $response['data'] ?? []
        );
    }

    public function store(StoreScheduleRequest $request)
    {

        $data = $request->validated();

        $response = $this->scheduleService->create($data);

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'],  $response['code'],  $response['data'] ?? []);
    }

    public function update(UpdateScheduleRequest $request, int $id)
    {

        $data = $request->validated();

        $response = $this->scheduleService->update($data, $id);

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'],  $response['code'],  $response['data'] ?? []);
    }

    public function destroy(int $id)
    {
        $response = $this->scheduleService->delete($id);

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'],  $response['code'],  $response['data'] ?? []);
    }
}
