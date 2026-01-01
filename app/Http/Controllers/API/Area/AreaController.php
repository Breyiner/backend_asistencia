<?php

namespace App\Http\Controllers\API\Area;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Area\StoreAreaRequest;
use App\Http\Requests\Area\UpdateAreaRequest;
use App\Services\Area\AreaService;

class AreaController extends Controller
{
    protected $areaService;

    public function __construct(AreaService $areaService)
    {
        $this->areaService = $areaService;
    }

    public function index()
    {
        $response = $this->areaService->getAll();

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function show(string $id)
    {
        $response = $this->areaService->getArea($id);

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function store(StoreAreaRequest $request)
    {
        $data = $request->validated();

        $response = $this->areaService->createArea($data);

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function update(UpdateAreaRequest $request, string $id)
    {
        $data = $request->validated();

        $response = $this->areaService->updateArea($data, $id);

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function destroy(string $id)
    {
        $response = $this->areaService->deleteArea($id);

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }
}