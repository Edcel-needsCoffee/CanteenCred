<?php

namespace App\Http\Controllers\Admin;

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
     * Display a listing of products.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['status', 'search']);
        $perPage = min($request->get('limit', 25), 100);

        $products = $this->productService->getProducts($filters, $perPage)->withQueryString();

        return view('admin.products.index', compact('products', 'filters'));
    }

    /**
     * Show the form for creating a new product.
     */
    public function create()
    {
        return view('admin.products.create');
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0|max:9999.99',
            'status' => 'sometimes|in:available,unavailable',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $product = $this->productService->createProduct($validator->validated(), $request->user());

        return redirect()->route('admin.products.index')
            ->with('success', 'Product created successfully.');
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product)
    {
        return view('admin.products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product)
    {
        return view('admin.products.edit', compact('product'));
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0|max:9999.99',
            'status' => 'required|in:available,unavailable',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $this->productService->updateProduct($product, $validator->validated(), $request->user());

        return redirect()->route('admin.products.index')
            ->with('success', 'Product updated successfully.');
    }

    /**
     * Toggle product status.
     */
    public function toggleStatus(Product $product)
    {
        $this->productService->toggleStatus($product, request()->user());

        return redirect()->back()
            ->with('success', 'Product status updated successfully.');
    }

    /**
     * Remove the specified product (disable).
     */
    public function destroy(Product $product)
    {
        $this->productService->updateProduct($product, ['status' => 'unavailable'], request()->user());

        return redirect()->route('admin.products.index')
            ->with('success', 'Product disabled successfully.');
    }
}