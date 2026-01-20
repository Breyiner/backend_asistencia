<?php

namespace App\Http\Controllers\API\Apprentice;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Apprentice\StoreApprenticeRequest;
use App\Http\Requests\Apprentice\UpdateApprenticeRequest;
use App\Http\Requests\ImportApprentice\ImportApprenticeRequest;
use App\Services\Apprentice\ApprenticeService;
use App\Services\ImportExcel\ImportExcelService;
use Illuminate\Http\Request;

class ApprenticeController extends Controller
{

    protected $apprenticeService;
    protected $importService;

    public function __construct(ApprenticeService $apprenticeService, ImportExcelService $importExcelService)
    {
        $this->apprenticeService = $apprenticeService;
        $this->importService = $importExcelService;
    }

    public function index(Request $request)
    {

        $response = $this->apprenticeService->getAll($request->get('per_page', 10));

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? [], $response['paginate']);
    }

    public function show(string $id)
    {

        $response = $this->apprenticeService->getById($id);

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function store(StoreApprenticeRequest $request)
    {

        $data = $request->validated();

        $response = $this->apprenticeService->create($data);

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function update(UpdateApprenticeRequest $request, string $id)
    {

        $data = $request->validated();

        $response = $this->apprenticeService->update($data, $id);

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function destroy(string $id)
    {

        $response = $this->apprenticeService->destroy($id);

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function import(ImportApprenticeRequest $request)
    {

        $data = $request->validated();

        $response = $this->importService->importApprentices($data);

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code'], $response['errors']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }
}
