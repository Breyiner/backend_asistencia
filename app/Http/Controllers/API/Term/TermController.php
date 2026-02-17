<?php

namespace App\Http\Controllers\API\Term;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Term\StoreTermRequest;
use App\Http\Requests\Term\UpdateTermRequest;
use App\Services\Term\TermService;
use Illuminate\Http\Request;

class TermController extends Controller
{
    protected $termService;

    public function __construct(TermService $termService)
    {
        $this->termService = $termService;
    }

    public function index()
    {
        $response = $this->termService->getAll();

        if ($response['error']) 
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function show(string $id)
    {
        $response = $this->termService->getById($id);

        if ($response['error']) 
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function store(StoreTermRequest $request)
    {
        $data = $request->validated();

        $response = $this->termService->create($data);

        if ($response['error']) 
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function update(UpdateTermRequest $request, string $id)
    {
        $data = $request->validated();

        $response = $this->termService->update($data, $id);

        if ($response['error']) 
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function destroy(string $id)
    {
        $response = $this->termService->delete($id);

        if ($response['error']) 
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }
}
