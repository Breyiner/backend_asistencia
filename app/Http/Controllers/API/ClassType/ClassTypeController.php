<?php

namespace App\Http\Controllers\API\ClassType;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClassType\StoreClassTypeRequest;
use App\Http\Requests\ClassType\UpdateClassTypeRequest;
use App\Services\ClassType\ClassTypeService;

class ClassTypeController extends Controller
{
    protected $classTypeService;

    public function __construct(ClassTypeService $classTypeService)
    {
        $this->classTypeService = $classTypeService;
    }

    public function index()
    {
        $response = $this->classTypeService->getAll();

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function store(StoreClassTypeRequest $request)
    {
        $response = $this->classTypeService->create($request->validated());

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function show(int $id)
    {
        $response = $this->classTypeService->getById($id);

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function update(UpdateClassTypeRequest $request, int $id)
    {
        $response = $this->classTypeService->update($request->validated(), $id);

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function destroy(int $id)
    {
        $response = $this->classTypeService->delete($id);

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }
}