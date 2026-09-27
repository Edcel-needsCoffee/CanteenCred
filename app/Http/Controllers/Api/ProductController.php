<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * List products.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['status', 'search']);
        $perPage = min($request->get('limit', 25), 100);

        $products = $this->productService->getProducts($filters, $perPage);

        return response()->json([
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Create a new product.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0|max:9999.99',
            'status' => 'sometimes|in:available,unavailable',
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

        $product = $this->productService->createProduct($validator->validated(), $request->user());

        return response()->json([
            'data' => $product,
            'message' => 'Product created successfully',
        ], 201);
    }

    /**
     * Get a product.
     */
    public function show(Product $product)
    {
        return response()->json([
            'data' => $product,
        ]);
    }

    /**
     * Update a product.
     */
    public function update(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0|max:9999.99',
            'status' => 'sometimes|in:available,unavailable',
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

        $product = $this->productService->updateProduct($product, $validator->validated(), $request->user());

        return response()->json([
            'data' => $product,
            'message' => 'Product updated successfully',
        ]);
    }

    /**
     * Delete (disable) a product.
     */
    public function destroy(Product $product)
    {
        $product = $this->productService->updateProduct($product, [
            'status' => 'unavailable',
        ], $request->user());

        return response()->json([
            'message' => 'Product disabled successfully',
        ]);
    }
}