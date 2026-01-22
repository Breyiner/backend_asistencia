<?php
namespace App\Http\Controllers\API\RealClass;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\RealClass\StoreRealClassRequest;
use App\Http\Requests\RealClass\UpdateRealClassRequest;
use App\Services\RealClass\RealClassService;
use Illuminate\Http\Request;

class RealClassController extends Controller
{
    protected $realClassService;

    public function __construct(RealClassService $realClassService)
    {
        $this->realClassService = $realClassService;
    }

    public function index(Request $request)
    {
        $response = $this->realClassService->getAll($request, $request->get('per_page', 10));

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function store(StoreRealClassRequest $request)
    {
        $response = $this->realClassService->create($request->validated());

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function show($id)
    {
        $response = $this->realClassService->getById($id);

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function update(UpdateRealClassRequest $request, $id)
    {
        $response = $this->realClassService->update($request->validated(), $id);

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function destroy($id)
    {
        $response = $this->realClassService->delete($id);

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }
}