<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\PayrollDeduction;
use Illuminate\Http\Request;

class DeductionController extends Controller
{
    /**
     * Display the faculty's deduction status by period.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        if (!$user->faculty_id) {
            abort(404, 'No faculty account linked to this user.');
        }

        $perPage = min($request->get('limit', 25), 100);

        $deductions = PayrollDeduction::with('payrollPeriod')
            ->where('faculty_id', $user->faculty_id)
            ->whereIn('status', ['approved', 'processed'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return view('faculty.deductions', compact('deductions'));
    }
}