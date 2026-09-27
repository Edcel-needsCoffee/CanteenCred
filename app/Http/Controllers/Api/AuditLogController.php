<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    protected AuditLogService $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * List audit logs.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['user_id', 'action', 'subject_type', 'subject_id', 'date_from', 'date_to']);
        $perPage = min($request->get('limit', 25), 100);

        $logs = $this->auditLogService->getLogs($filters, $perPage);

        return response()->json([
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    /**
     * Get an audit log.
     */
    public function show(AuditLog $auditLog)
    {
        return response()->json([
            'data' => $auditLog->load('user'),
        ]);
    }
}