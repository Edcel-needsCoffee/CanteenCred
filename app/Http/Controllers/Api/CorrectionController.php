<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionCorrection;
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
     * List correction requests.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['status', 'transaction_id']);
        $perPage = min($request->get('limit', 25), 100);

        $corrections = $this->correctionService->getCorrections($filters, $perPage);

        return response()->json([
            'data' => $corrections->items(),
            'meta' => [
                'current_page' => $corrections->currentPage(),
                'per_page' => $corrections->perPage(),
                'total' => $corrections->total(),
            ],
        ]);
    }

    /**
     * Submit a correction request.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|exists:transactions,id',
            'corrected_data' => 'required|array',
            'corrected_data.transaction' => 'sometimes|array',
            'corrected_data.items' => 'sometimes|array',
            'reason' => 'required|string|max:1000',
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

        try {
            $transaction = Transaction::findOrFail($validator->validated()['transaction_id']);
            $correction = $this->correctionService->requestCorrection(
                $transaction,
                $request->user(),
                $validator->validated()['corrected_data'],
                $validator->validated()['reason']
            );

            return response()->json([
                'data' => $correction->load('transaction.faculty', 'requestedBy'),
                'message' => 'Correction request submitted successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'CORRECTION_FAILED',
                    'message' => $e->getMessage(),
                ]
            ], 422);
        }
    }

    /**
     * Get a correction request.
     */
    public function show(TransactionCorrection $correction)
    {
        return response()->json([
            'data' => $correction->load('transaction.faculty', 'requestedBy', 'approvedBy'),
        ]);
    }

    /**
     * Approve a correction request.
     */
    public function approve(Request $request, TransactionCorrection $correction)
    {
        try {
            $correction = $this->correctionService->approveCorrection($correction, $request->user());

            return response()->json([
                'data' => $correction->load('transaction.faculty', 'requestedBy', 'approvedBy'),
                'message' => 'Correction approved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'APPROVE_FAILED',
                    'message' => $e->getMessage(),
                ]
            ], 422);
        }
    }

    /**
     * Reject a correction request.
     */
    public function reject(Request $request, TransactionCorrection $correction)
    {
        try {
            $correction = $this->correctionService->rejectCorrection($correction, $request->user());

            return response()->json([
                'data' => $correction->load('transaction.faculty', 'requestedBy', 'approvedBy'),
                'message' => 'Correction rejected successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'REJECT_FAILED',
                    'message' => $e->getMessage(),
                ]
            ], 422);
        }
    }
}