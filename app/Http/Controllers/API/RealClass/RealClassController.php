<?php
namespace App\Http\Controllers\API\RealClass;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\RealClass\StoreRealClassRequest;
use App\Http\Requests\RealClass\UpdateRealClassRequest;
use App\Models\RealClass;
use App\Models\ScheduleSession;
use App\Services\RealClass\RealClassService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class RealClassController extends Controller
{
    use AuthorizesRequests;
    
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

    public function mine(Request $request)
    {
        $response = $this->realClassService->getMine($request, $request->get('per_page', 10));

        if ($response['error']) return ResponseFormatter::error($response['message'], $response['code']);
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? [], $response['paginate'] ?? []);
    }

    public function managed(Request $request)
    {
        $response = $this->realClassService->getManaged($request, $request->get('per_page', 10));

        if ($response['error']) return ResponseFormatter::error($response['message'], $response['code']);
        return ResponseFormatter::success($response['message'], $response['code'], $response['data'] ?? [], $response['paginate'] ?? []);
    }

    public function store(StoreRealClassRequest $request)
    {
        $data = $request->validated();

        $scheduleSession = ScheduleSession::with('schedule.fichaTerm.ficha')
            ->find($data['schedule_session_id']);

        $this->authorize('create', [RealClass::class, $scheduleSession, $data['instructor_id']]);

        $response = $this->realClassService->create($data);

        if ($response['error']) return ResponseFormatter::error($response['message'], $response['code']);
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