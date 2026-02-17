<?php

namespace App\Http\Controllers\API\TrainingProgram;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\TrainingProgram\StoreTrainingProgramRequest;
use App\Http\Requests\TrainingProgram\UpdateTrainingProgramRequest;
use App\Services\TrainingProgram\TrainingProgramService;
use Illuminate\Http\Request;

class TrainingProgramController extends Controller
{

    protected $trainingProgramService;

    public function __construct(TrainingProgramService $trainingProgramService)
    {
        $this->trainingProgramService = $trainingProgramService;
    }

    public function index(Request $request)
    {
        $reponse = $this->trainingProgramService->getAll($request->get('per_page', 10));

        if ($reponse['error'])
            return ResponseFormatter::error($reponse['message'], $reponse['code']);

        return ResponseFormatter::success($reponse['message'], $reponse['code'], $reponse['data'] ?? [], $reponse['paginate']);
    }

    public function select(Request $request)
    {
        $response = $this->trainingProgramService->getAllForSelect();

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function show(string $id)
    {
        $response = $this->trainingProgramService->getById($id);

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function store(StoreTrainingProgramRequest $request)
    {
        $data = $request->validated();

        $response = $this->trainingProgramService->create($data);

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function update(UpdateTrainingProgramRequest $request, string $id)
    {
        $data = $request->validated();

        $response = $this->trainingProgramService->update($data, $id);

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }

    public function destroy(string $id)
    {
        $response = $this->trainingProgramService->delete($id);

        if ($response['error'])
            return ResponseFormatter::error($response['message'], $response['code']);

        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? []);
    }
}
