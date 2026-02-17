<?php

namespace App\Http\Controllers\API\FichaTerm;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\FichaTerm\StoreFichaTermRequest;
use App\Http\Requests\FichaTerm\UpdateFichaTermRequest;
use App\Services\FichaTerm\FichaTermService;
use Illuminate\Http\Request;

class FichaTermController extends Controller
{
    protected $fichaTermService;

    public function __construct(FichaTermService $fichaTermService)
    {
        $this->fichaTermService = $fichaTermService;
    }

    public function index()
    {
        $response = $this->fichaTermService->getAll();

        if ($response['error']) 
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function show($id)
    {
        $response = $this->fichaTermService->getById($id);

        if ($response['error']) 
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function store(StoreFichaTermRequest $request)
    {
        $data = $request->validated();

        $response = $this->fichaTermService->create($data);

        if ($response['error']) 
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function update(UpdateFichaTermRequest $request, int $id)
    {
        $data = $request->validated();

        $response = $this->fichaTermService->update($data, $id);

        if ($response['error']) 
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function setCurrent($id)
{
    $response = $this->fichaTermService->setCurrent($id);
    
    if ($response['error']) {
        return ResponseFormatter::error($response['message'], $response['code']);
    }
    
    return ResponseFormatter::success( $response['message'], $response['code'], $response['data'] ?? []);
}


    public function destroy(string $id)
    {
        $response = $this->fichaTermService->delete($id);

        if ($response['error']) 
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }
}
