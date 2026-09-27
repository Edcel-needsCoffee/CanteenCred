<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TransactionCorrection;
use App\Models\Transaction;
use App\Services\CorrectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CorrectionController extends Controller
{
    protected CorrectionService $correctionService;

    public function __construct(CorrectionService $correctionService)
    {
        $this->correctionService = $correctionService;
    }

    /**
     * Display a listing of correction requests.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['status', 'transaction_id']);
        $perPage = min($request->get('limit', 25), 100);

        $corrections = $this->correctionService->getCorrections($filters, $perPage)->withQueryString();

        return view('admin.corrections.index', compact('corrections', 'filters'));
    }

    /**
     * Display the specified correction request.
     */
    public function show(TransactionCorrection $correction)
    {
        $correction->load('transaction.faculty', 'requestedBy', 'approvedBy');

        return view('admin.corrections.show', compact('correction'));
    }

    /**
     * Approve a correction request.
     */
    public function approve(Request $request, TransactionCorrection $correction)
    {
        try {
            $this->correctionService->approveCorrection($correction, $request->user());

            return redirect()->back()
                ->with('success', 'Correction approved successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Reject a correction request.
     */
    public function reject(Request $request, TransactionCorrection $correction)
    {
        try {
            $this->correctionService->rejectCorrection($correction, $request->user());

            return redirect()->back()
                ->with('success', 'Correction rejected successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}