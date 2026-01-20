<?php

namespace App\Http\Controllers\API\Ficha;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ficha\StoreFichaRequest;
use App\Http\Requests\Ficha\UpdateFichaRequest;
use App\Services\Ficha\FichaService;
use Illuminate\Http\Request;

class FichaController extends Controller
{
    private $fichaService;

    public function __construct(FichaService $fichaService)
    {
        $this->fichaService = $fichaService;
    }

    public function index(Request $request)
    {
        $response = $this->fichaService->getAll($request->get('per_page', 10));

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? [], $response['paginate']);
    }

    public function show(string $id)
    {
        $response = $this->fichaService->getById($id);

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function showByTrainingProgram(string $trining_progra_id)
    {
        $response = $this->fichaService->getByTrainingProgram($trining_progra_id);

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function store(StoreFichaRequest $request)
    {
        $data = $request->validated();

        $response = $this->fichaService->create($data);

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function update(UpdateFichaRequest $request, string $id)
    {
        $data = $request->validated();

        $response = $this->fichaService->update($id, $data);

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function destroy(string $id)
    {
        $response = $this->fichaService->delete($id);

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }
}
