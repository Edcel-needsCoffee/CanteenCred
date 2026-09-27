<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\User;
use App\Services\FacultyService;
use App\Services\CreditBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class FacultyController extends Controller
{
    protected FacultyService $facultyService;
    protected CreditBalanceService $creditBalanceService;

    public function __construct(FacultyService $facultyService, CreditBalanceService $creditBalanceService)
    {
        $this->facultyService = $facultyService;
        $this->creditBalanceService = $creditBalanceService;
    }

    /**
     * List faculty accounts.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['department', 'is_active', 'search']);
        $perPage = min($request->get('limit', 25), 100);

        $query = Faculty::query();

        if (!empty($filters['department'])) {
            $query->where('department', $filters['department']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        $faculty = $query->orderBy('name')->paginate($perPage);

        return response()->json([
            'data' => $faculty->items(),
            'meta' => [
                'current_page' => $faculty->currentPage(),
                'per_page' => $faculty->perPage(),
                'total' => $faculty->total(),
            ],
        ]);
    }

    /**
     * Create a new faculty account.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'faculty_number' => 'required|string|unique:faculty,faculty_number|max:50',
            'name' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'credit_limit' => 'required|numeric|min:0|max:50000',
            'is_active' => 'boolean',
            'create_user' => 'boolean',
            'username' => 'required_if:create_user,true|string|unique:users,username|max:50',
            'email' => 'required_if:create_user,true|email|unique:users,email|max:255',
            'password' => 'required_if:create_user,true|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Validation failed.',
                    'fields' => $validator->errors(),
                ]
            ], 422);
        }

        $data = $validator->validated();
        $faculty = $this->facultyService->createFaculty($data, $request->user());

        return response()->json([
            'data' => $faculty->load('user'),
            'message' => 'Faculty account created successfully',
        ], 201);
    }

    /**
     * Get a faculty account.
     */
    public function show(Faculty $faculty)
    {
        return response()->json([
            'data' => $faculty->load('user'),
        ]);
    }

    /**
     * Update a faculty account.
     */
    public function update(Request $request, Faculty $faculty)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'department' => 'sometimes|string|max:255',
            'credit_limit' => 'sometimes|numeric|min:0|max:50000',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Validation failed.',
                    'fields' => $validator->errors(),
                ]
            ], 422);
        }

        $faculty = $this->facultyService->updateFaculty($faculty, $validator->validated(), $request->user());

        return response()->json([
            'data' => $faculty,
            'message' => 'Faculty account updated successfully',
        ]);
    }

    /**
     * Delete (deactivate) a faculty account.
     */
    public function destroy(Faculty $faculty)
    {
        $faculty->update(['is_active' => false]);

        return response()->json([
            'message' => 'Faculty account deactivated successfully',
        ]);
    }

    /**
     * Search faculty for point-of-sale lookup.
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([
                'data' => [],
                'message' => 'Search query must be at least 2 characters',
            ]);
        }

        $faculty = $this->facultyService->searchFaculty($query);

        return response()->json([
            'data' => $faculty->map(fn($f) => [
                'id' => $f->id,
                'faculty_number' => $f->faculty_number,
                'name' => $f->name,
                'department' => $f->department,
                'credit_limit' => $f->credit_limit,
                'balance' => $f->balance,
                'remaining_credit' => $f->remaining_credit,
                'is_active' => $f->is_active,
            ]),
        ]);
    }

    /**
     * Get faculty balance.
     */
    public function balance(Faculty $faculty)
    {
        $details = $this->creditBalanceService->getBalanceDetails($faculty);

        return response()->json([
            'data' => $details,
        ]);
    }

    /**
     * Get authenticated faculty's own profile.
     */
    public function me(Request $request)
    {
        $user = $request->user();
        
        if (!$user->faculty_id) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'No faculty account linked to this user.',
                ]
            ], 404);
        }

        $faculty = Faculty::findOrFail($user->faculty_id);

        return response()->json([
            'data' => $faculty->load('user'),
        ]);
    }

    /**
     * Get authenticated faculty's own balance.
     */
    public function myBalance(Request $request)
    {
        $user = $request->user();
        
        if (!$user->faculty_id) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'No faculty account linked to this user.',
                ]
            ], 404);
        }

        $faculty = Faculty::findOrFail($user->faculty_id);
        $details = $this->creditBalanceService->getBalanceDetails($faculty);

        return response()->json([
            'data' => $details,
        ]);
    }

    /**
     * Get authenticated faculty's own transactions.
     */
    public function myTransactions(Request $request)
    {
        $user = $request->user();
        
        if (!$user->faculty_id) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'No faculty account linked to this user.',
                ]
            ], 404);
        }

        $faculty = Faculty::findOrFail($user->faculty_id);
        $perPage = min($request->get('limit', 25), 100);
        $filters = $request->only(['status', 'date_from', 'date_to']);

        $transactions = $faculty->transactions()
            ->with('items.product')
            ->when($filters['status'] ?? null, fn($q, $s) => $q->where('status', $s))
            ->when($filters['date_from'] ?? null, fn($q, $d) => $q->whereDate('transaction_date', '>=', $d))
            ->when($filters['date_to'] ?? null, fn($q, $d) => $q->whereDate('transaction_date', '<=', $d))
            ->orderBy('transaction_date', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => $transactions->items(),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    /**
     * Get authenticated faculty's own deductions.
     */
    public function myDeductions(Request $request)
    {
        $user = $request->user();
        
        if (!$user->faculty_id) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'No faculty account linked to this user.',
                ]
            ], 404);
        }

        $faculty = Faculty::findOrFail($user->faculty_id);
        $perPage = min($request->get('limit', 25), 100);

        $deductions = $faculty->payrollDeductions()
            ->with('payrollPeriod')
            ->whereIn('status', ['approved', 'processed'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => $deductions->items(),
            'meta' => [
                'current_page' => $deductions->currentPage(),
                'per_page' => $deductions->perPage(),
                'total' => $deductions->total(),
            ],
        ]);
    }
}