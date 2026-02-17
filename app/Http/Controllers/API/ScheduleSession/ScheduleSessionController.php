<?php

namespace App\Http\Controllers\API\ScheduleSession;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\ScheduleSession\StoreScheduleSessionRequest;
use App\Http\Requests\ScheduleSession\UpdateScheduleSessionRequest;
use App\Services\ScheduleSession\ScheduleSessionService;

class ScheduleSessionController extends Controller
{
    protected $scheduleSessionService;

    public function __construct(ScheduleSessionService $scheduleSessionService)
    {
        $this->scheduleSessionService = $scheduleSessionService;
    }

    public function index()
    {
        $response = $this->scheduleSessionService->getAll();

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function store(StoreScheduleSessionRequest $request)
    {
        $response = $this->scheduleSessionService->create($request->validated());

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function show(int $id)
    {
        $response = $this->scheduleSessionService->getById($id);

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function showByFicha(int $ficha_id)
    {
        $response = $this->scheduleSessionService->getByFichaId($ficha_id);

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }


    public function update(UpdateScheduleSessionRequest $request, int $id)
    {
        $response = $this->scheduleSessionService->update($request->validated(), $id);

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function destroy(int $id)
    {
        $response = $this->scheduleSessionService->delete($id);

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }
}
