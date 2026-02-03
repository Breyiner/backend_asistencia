<?php

namespace App\Http\Controllers\API\NoClassReason;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\NoClassReason\StoreNoClassReasonRequest;
use App\Http\Requests\NoClassReason\UpdateNoClassReasonRequest;
use App\Services\NoClassReason\NoClassReasonService;

class NoClassReasonController extends Controller
{
    protected $noClassReasonService;

    public function __construct(NoClassReasonService $noClassReasonService)
    {
        $this->noClassReasonService = $noClassReasonService;
    }

    public function index()
    {
        $response = $this->noClassReasonService->getAll();

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function show(string $no_class_reason_id)
    {
        $response = $this->noClassReasonService->getById($no_class_reason_id);

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function store(StoreNoClassReasonRequest $request)
    {
        $response = $this->noClassReasonService->store($request->validated());

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function update(UpdateNoClassReasonRequest $request, string $no_class_reason_id)
    {
        $response = $this->noClassReasonService->update($no_class_reason_id, $request->validated());

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function destroy(string $no_class_reason_id)
    {
        $response = $this->noClassReasonService->destroy($no_class_reason_id);

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }
}