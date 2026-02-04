<?php

namespace App\Http\Controllers\API\NoClassDay;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\NoClassDay\CheckNoClassDayRequest;
use App\Http\Requests\NoClassDay\StoreNoClassDayRequest;
use App\Http\Requests\NoClassDay\UpdateNoClassDayRequest;
use App\Services\NoclassDay\NoClassDayService;
use Illuminate\Http\Request;

class NoClassDayController extends Controller
{
    protected $noClassDayService;

    public function __construct(NoClassDayService $noClassDayService)
    {
        $this->noClassDayService = $noClassDayService;
    }

    public function index(Request $request)
    {
        $response = $this->noClassDayService->getAll($request->get('per_page', 10));

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? [], $response['paginate']);
    }

    public function show(string $no_class_day_id)
    {
        $response = $this->noClassDayService->getById($no_class_day_id);

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function store(StoreNoClassDayRequest $request)
    {
        $response = $this->noClassDayService->store($request->validated());

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function update(UpdateNoClassDayRequest $request, string $no_class_day_id)
    {
        $response = $this->noClassDayService->update($no_class_day_id, $request->validated());

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function destroy(string $no_class_day_id)
    {
        $response = $this->noClassDayService->destroy($no_class_day_id);

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    // Endpoint para el frontend (consulta por fecha)
    public function check(CheckNoClassDayRequest $request)
    {
        $data = $request->validated();

        $response = $this->noClassDayService->checkByFichaAndDate($data['ficha_id'], $data['date']);

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }
}
