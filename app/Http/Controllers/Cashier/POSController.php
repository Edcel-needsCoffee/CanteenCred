<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Faculty;
use Illuminate\Http\Request;

class POSController extends Controller
{
    /**
     * Show the point-of-sale interface.
     */
    public function index()
    {
        $products = Product::available()->orderBy('name')->get(['id', 'name', 'price']);
        
        return view('cashier.pos', compact('products'));
    }

    /**
     * Search faculty for point-of-sale lookup.
     */
    public function searchFaculty(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([
                'data' => [],
                'message' => 'Search query must be at least 2 characters',
            ]);
        }

        $faculty = Faculty::active()
            ->where(function ($q) use ($query) {
                $q->where('faculty_number', 'like', "%{$query}%")
                    ->orWhere('name', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'faculty_number', 'name', 'department', 'credit_limit', 'is_active']);

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
}