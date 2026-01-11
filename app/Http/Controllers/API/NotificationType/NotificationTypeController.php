<?php

namespace App\Http\Controllers\API\NotificationType;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\FichaStatus\StoreFichaStatusRequest;
use App\Http\Requests\FichaStatus\updateFichaStatusRequest;
use App\Services\FichaStatus\FichaStatusService;
use Illuminate\Http\Request;

class NotificationTypeController extends Controller
{

    protected $statusService;
    
    public function __construct(FichaStatusService $statusService)
    {
        $this->statusService = $statusService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $response = $this->statusService->getAll();

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success(
            $response['message'],
            $response['code'],
            $response['data'] ?? []
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $response = $this->statusService->getStatus($id);

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success(
            $response['message'],
            $response['code'],
            $response['data'] ?? []
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFichaStatusRequest $request)
    {
        $response = $this->statusService->createStatus($request->validated());

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success(
            $response['message'],
            $response['code'],
            $response['data'] ?? []
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(updateFichaStatusRequest $request, string $id)
    {
        $response = $this->statusService->updateStatus($request->validated(), $id);

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success(
            $response['message'],
            $response['code'],
            $response['data'] ?? []
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $response = $this->statusService->deleteStatus($id);

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success(
            $response['message'],
            $response['code'],
            $response['data'] ?? []
        );
    }
}
