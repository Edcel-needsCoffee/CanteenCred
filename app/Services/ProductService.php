<?php

namespace App\Services;

use App\Models\Product;
use App\Models\AuditLog;
use App\Enums\AuditAction;
use App\Enums\ProductStatus;
use Illuminate\Support\Facades\DB;

class ProductService
{
    protected AuditLogService $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Create a new product.
     */
    public function createProduct(array $data, $createdBy = null): Product
    {
        $product = Product::create([
            'name' => $data['name'],
            'price' => $data['price'],
            'status' => $data['status'] ?? ProductStatus::AVAILABLE->value,
        ]);

        if ($createdBy) {
            $this->auditLogService->log(
                AuditAction::PRODUCT_CREATED,
                Product::class,
                $product->id,
                "Product created: {$product->name} at {$product->price}",
                $createdBy,
                ['name' => $product->name, 'price' => $product->price, 'status' => $product->status]
            );
        }

        return $product;
    }

    /**
     * Update a product.
     */
    public function updateProduct(Product $product, array $data, $updatedBy = null): Product
    {
        $oldData = $product->only(['name', 'price', 'status']);

        $product->update($data);

        if ($updatedBy && $oldData !== $product->only(['name', 'price', 'status'])) {
            $changes = [];

            foreach (['name', 'price', 'status'] as $field) {
                if ($oldData[$field] !== $product->$field) {
                    $changes[$field] = ['old' => $oldData[$field], 'new' => $product->$field];
                }
            }

            if (!empty($changes)) {
                $action = AuditAction::PRODUCT_UPDATED;
                if (isset($changes['price'])) {
                    $action = AuditAction::PRODUCT_PRICE_CHANGED;
                } elseif (isset($changes['status'])) {
                    $action = AuditAction::PRODUCT_STATUS_CHANGED;
                }

                $this->auditLogService->log(
                    $action,
                    Product::class,
                    $product->id,
                    "Product updated: {$product->name}",
                    $updatedBy,
                    ['changes' => $changes]
                );
            }
        }

        return $product->fresh();
    }

    /**
     * Toggle product status.
     */
    public function toggleStatus(Product $product, $changedBy = null): Product
    {
        $newStatus = $product->status === ProductStatus::AVAILABLE->value
            ? ProductStatus::UNAVAILABLE->value
            : ProductStatus::AVAILABLE->value;

        $product->update(['status' => $newStatus]);

        if ($changedBy) {
            $this->auditLogService->log(
                AuditAction::PRODUCT_STATUS_CHANGED,
                Product::class,
                $product->id,
                "Product status changed to {$newStatus}: {$product->name}",
                $changedBy,
                ['status' => $newStatus]
            );
        }

        return $product->fresh();
    }

    /**
     * Get available products for point-of-sale.
     */
    public function getAvailableProducts(): \Illuminate\Database\Eloquent\Collection
    {
        return Product::where('status', ProductStatus::AVAILABLE->value)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get all products with pagination.
     */
    public function getProducts(array $filters = [], int $perPage = 25)
    {
        $query = Product::orderBy('name');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $query->where('name', 'like', "%{$filters['search']}%");
        }

        return $query->paginate($perPage);
    }
}