<?php

namespace App\Http\Controllers\API\Permission;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Services\Permission\PermissionService;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function select(Request $request)
    {
        $response = $this->permissionService->select();

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