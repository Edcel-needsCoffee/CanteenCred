<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Faculty;
use App\Services\AuditLogService;
use App\Enums\AuditAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    protected AuditLogService $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $perPage = min($request->get('limit', 25), 100);

        $users = User::with('faculty')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $facultyList = Faculty::active()->whereDoesntHave('user')->orderBy('name')->get(['id', 'faculty_number', 'name']);
        $roles = \App\Enums\UserRole::cases();

        return view('admin.users.create', compact('facultyList', 'roles'));
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email|max:255',
            'username' => 'required|string|unique:users,username|max:50',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:administrator,cashier,payroll,faculty',
            'faculty_id' => 'nullable|exists:faculty,id',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        $this->auditLogService->log(
            AuditAction::USER_CREATED,
            User::class,
            $user->id,
            "User created: {$user->name} ({$user->role})",
            $request->user(),
            ['name' => $user->name, 'role' => $user->role]
        );

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $user->load('faculty');

        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $facultyList = Faculty::active()->where(function ($q) use ($user) {
            $q->whereDoesntHave('user')->orWhere('id', $user->faculty_id);
        })->orderBy('name')->get(['id', 'faculty_number', 'name']);
        $roles = \App\Enums\UserRole::cases();

        return view('admin.users.edit', compact('user', 'facultyList', 'roles'));
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id . '|max:255',
            'username' => 'required|string|unique:users,username,' . $user->id . '|max:50',
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|in:administrator,cashier,payroll,faculty',
            'faculty_id' => 'nullable|exists:faculty,id',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $oldRole = $user->role;
        $user->update($data);

        if ($oldRole !== $user->role) {
            $this->auditLogService->log(
                AuditAction::USER_ROLE_CHANGED,
                User::class,
                $user->id,
                "User role changed from {$oldRole} to {$user->role}: {$user->name}",
                $request->user(),
                ['old_role' => $oldRole, 'new_role' => $user->role]
            );
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Toggle user active status.
     */
    public function toggleStatus(User $user)
    {
        if ($user->id === request()->user()->id) {
            return redirect()->back()
                ->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $this->auditLogService->log(
            AuditAction::USER_DELETED,
            User::class,
            $user->id,
            "User status changed to " . ($user->is_active ? 'active' : 'inactive') . ": {$user->name}",
            request()->user(),
            ['is_active' => $user->is_active]
        );

        return redirect()->back()
            ->with('success', 'User status updated successfully.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        if ($user->id === request()->user()->id) {
            return redirect()->back()
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        $this->auditLogService->log(
            AuditAction::USER_DELETED,
            User::class,
            $user->id,
            "User deleted: {$user->name}",
            request()->user()
        );

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}