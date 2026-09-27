<?php

namespace App\Http\Controllers\Admin;

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
     * Display a listing of audit logs.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['user_id', 'action', 'subject_type', 'subject_id', 'date_from', 'date_to']);
        $perPage = min($request->get('limit', 25), 100);

        $logs = $this->auditLogService->getLogs($filters, $perPage)->withQueryString();

        // Get filter options
        $users = \App\Models\User::orderBy('name')->get(['id', 'name']);
        $actions = \App\Enums\AuditAction::cases();

        return view('admin.audit-logs.index', compact('logs', 'filters', 'users', 'actions'));
    }

    /**
     * Display the specified audit log.
     */
    public function show(AuditLog $auditLog)
    {
        $auditLog->load('user');

        return view('admin.audit-logs.show', compact('auditLog'));
    }
}