<?php

namespace App\Http\Controllers\API\Phase;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Phase\StorePhaseRequest;
use App\Http\Requests\Phase\UpdatePhaseRequest;
use App\Services\Phase\PhaseService;
use Illuminate\Http\Request;

class PhaseController extends Controller
{
    protected $phaseService;

    public function __construct(PhaseService $phaseService)
    {
        $this->phaseService = $phaseService;
    }

    public function index()
    {
        $response = $this->phaseService->getAll();

        if ($response['error']) 
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function show(string $id)
    {
        $response = $this->phaseService->getById($id);

        if ($response['error']) 
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function store(StorePhaseRequest $request)
    {
        $data = $request->validated();

        $response = $this->phaseService->create($data);

        if ($response['error']) 
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function update(UpdatePhaseRequest $request, string $id)
    {
        $data = $request->validated();

        $response = $this->phaseService->update($data, $id);

        if ($response['error']) 
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function destroy(string $id)
    {
        $response = $this->phaseService->delete($id);

        if ($response['error']) 
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }
}
