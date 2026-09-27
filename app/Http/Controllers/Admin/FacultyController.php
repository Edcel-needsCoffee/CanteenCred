<?php

namespace App\Http\Controllers\Admin;

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
     * Display a listing of faculty accounts.
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

        $faculty = $query->with('user')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $departments = Faculty::distinct()->pluck('department')->filter()->sort()->values();

        return view('admin.faculty.index', compact('faculty', 'filters', 'departments'));
    }

    /**
     * Show the form for creating a new faculty account.
     */
    public function create()
    {
        return view('admin.faculty.create');
    }

    /**
     * Store a newly created faculty account.
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
            'password' => 'required_if:create_user,true|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        $faculty = $this->facultyService->createFaculty($data, $request->user());

        return redirect()->route('admin.faculty.index')
            ->with('success', 'Faculty account created successfully.');
    }

    /**
     * Display the specified faculty account.
     */
    public function show(Faculty $faculty)
    {
        $balanceDetails = $this->creditBalanceService->getBalanceDetails($faculty);
        $transactions = $faculty->transactions()
            ->with('items.product', 'recordedBy')
            ->orderBy('transaction_date', 'desc')
            ->paginate(20);

        return view('admin.faculty.show', compact('faculty', 'balanceDetails', 'transactions'));
    }

    /**
     * Show the form for editing the specified faculty account.
     */
    public function edit(Faculty $faculty)
    {
        return view('admin.faculty.edit', compact('faculty'));
    }

    /**
     * Update the specified faculty account.
     */
    public function update(Request $request, Faculty $faculty)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'credit_limit' => 'required|numeric|min:0|max:50000',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $this->facultyService->updateFaculty($faculty, $validator->validated(), $request->user());

        return redirect()->route('admin.faculty.index')
            ->with('success', 'Faculty account updated successfully.');
    }

    /**
     * Toggle faculty active status.
     */
    public function toggleStatus(Faculty $faculty)
    {
        $this->facultyService->toggleStatus($faculty, request()->user());

        return redirect()->back()
            ->with('success', 'Faculty status updated successfully.');
    }

    /**
     * Change credit limit.
     */
    public function changeCreditLimit(Request $request, Faculty $faculty)
    {
        $validator = Validator::make($request->all(), [
            'credit_limit' => 'required|numeric|min:0|max:50000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $this->facultyService->changeCreditLimit($faculty, $validator->validated()['credit_limit'], $request->user());

        return redirect()->back()
            ->with('success', 'Credit limit updated successfully.');
    }

    /**
     * Remove the specified faculty account (soft delete).
     */
    public function destroy(Faculty $faculty)
    {
        $faculty->update(['is_active' => false]);

        return redirect()->route('admin.faculty.index')
            ->with('success', 'Faculty account deactivated successfully.');
    }
}