<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Enums\UserRole;
use App\Services\CreditBalanceService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditLimitEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $cashier;
    protected Faculty $faculty;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMINISTRATOR->value,
            'is_active' => true,
        ]);

        $this->cashier = User::factory()->create([
            'role' => UserRole::CASHIER->value,
            'is_active' => true,
        ]);

        $this->faculty = Faculty::factory()->create([
            'faculty_number' => '2026-00001',
            'name' => 'Test Faculty',
            'department' => 'College of IT',
            'credit_limit' => 2000.00,
            'is_active' => true,
        ]);

        $this->product = Product::factory()->create([
            'name' => 'Chicken Meal',
            'price' => 150.00,
            'status' => 'available',
        ]);
    }

    /** @test */
    public function faculty_balance_starts_at_zero()
    {
        $service = new CreditBalanceService();
        $balance = $service->getBalance($this->faculty);
        
        $this->assertEquals(0, $balance);
        $this->assertEquals(2000.00, $service->getRemainingCredit($this->faculty));
    }

    /** @test */
    public function transaction_within_credit_limit_is_allowed()
    {
        $service = new TransactionService(new CreditBalanceService());
        
        $transaction = $service->createTransaction([
            'faculty_id' => $this->faculty->id,
            'recorded_by' => $this->cashier->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2], // 300.00
            ],
            'override_credit_limit' => false,
        ]);

        $this->assertNotNull($transaction);
        $this->assertEquals(300.00, $transaction->total_amount);
        $this->assertEquals('active', $transaction->status->value);
        $this->assertFalse($transaction->credit_limit_override);
    }

    /** @test */
    public function transaction_exceeding_credit_limit_is_rejected()
    {
        $service = new TransactionService(new CreditBalanceService());
        
        // Create a transaction that would exceed limit (2500 > 2000)
        $expensiveProduct = Product::factory()->create([
            'name' => 'Expensive Item',
            'price' => 2500.00,
            'status' => 'available',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('CREDIT_LIMIT_EXCEEDED');

        $service->createTransaction([
            'faculty_id' => $this->faculty->id,
            'recorded_by' => $this->cashier->id,
            'items' => [
                ['product_id' => $expensiveProduct->id, 'quantity' => 1],
            ],
            'override_credit_limit' => false,
        ]);
    }

    /** @test */
    public function authorized_override_allows_exceeding_credit_limit()
    {
        $service = new TransactionService(new CreditBalanceService());
        
        $expensiveProduct = Product::factory()->create([
            'name' => 'Expensive Item',
            'price' => 2500.00,
            'status' => 'available',
        ]);

        $transaction = $service->createTransaction([
            'faculty_id' => $this->faculty->id,
            'recorded_by' => $this->admin->id, // Admin can override
            'items' => [
                ['product_id' => $expensiveProduct->id, 'quantity' => 1],
            ],
            'override_credit_limit' => true,
            'override_reason' => 'Special occasion approved by admin',
        ]);

        $this->assertNotNull($transaction);
        $this->assertEquals(2500.00, $transaction->total_amount);
        $this->assertTrue($transaction->credit_limit_override);
        $this->assertEquals('Special occasion approved by admin', $transaction->override_reason);
        $this->assertEquals($this->admin->id, $transaction->override_authorized_by);
    }

    /** @test */
    public function testCashierCannotOverrideCreditLimit()
    {
        $service = new TransactionService(new CreditBalanceService());
        
        $expensiveProduct = Product::factory()->create([
            'name' => 'Expensive Item',
            'price' => 2500.00,
            'status' => 'available',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('CREDIT_LIMIT_EXCEEDED');

        // Cashier tries to override but should still be rejected
        $service->createTransaction([
            'faculty_id' => $this->faculty->id,
            'recorded_by' => $this->cashier->id,
            'items' => [
                ['product_id' => $expensiveProduct->id, 'quantity' => 1],
            ],
            'override_credit_limit' => true,
            'override_reason' => 'Cashier tried to override',
        ]);
    }

    /** @test */
    public function multiple_transactions_accumulate_balance()
    {
        $service = new TransactionService(new CreditBalanceService());
        
        // First transaction: 300
        $service->createTransaction([
            'faculty_id' => $this->faculty->id,
            'recorded_by' => $this->cashier->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2],
            ],
            'override_credit_limit' => false,
        ]);

        // Second transaction: 450
        $service->createTransaction([
            'faculty_id' => $this->faculty->id,
            'recorded_by' => $this->cashier->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 3],
            ],
            'override_credit_limit' => false,
        ]);

        $balanceService = new CreditBalanceService();
        $this->assertEquals(750.00, $balanceService->getBalance($this->faculty));
        $this->assertEquals(1250.00, $balanceService->getRemainingCredit($this->faculty));
    }

    /** @test */
    public function transaction_at_exact_credit_limit_is_allowed()
    {
        $service = new TransactionService(new CreditBalanceService());
        
        // Create product that exactly matches remaining credit
        $exactProduct = Product::factory()->create([
            'name' => 'Exact Limit Item',
            'price' => 2000.00,
            'status' => 'available',
        ]);

        $transaction = $service->createTransaction([
            'faculty_id' => $this->faculty->id,
            'recorded_by' => $this->cashier->id,
            'items' => [
                ['product_id' => $exactProduct->id, 'quantity' => 1],
            ],
            'override_credit_limit' => false,
        ]);

        $this->assertNotNull($transaction);
        $this->assertEquals(2000.00, $transaction->total_amount);
    }

    /** @test */
    public function disabled_faculty_cannot_make_transactions()
    {
        $service = new TransactionService(new CreditBalanceService());
        
        $this->faculty->update(['is_active' => false]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('not available');

        $service->createTransaction([
            'faculty_id' => $this->faculty->id,
            'recorded_by' => $this->cashier->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
            'override_credit_limit' => false,
        ]);
    }

    /** @test */
    public function unavailable_product_cannot_be_purchased()
    {
        $service = new TransactionService(new CreditBalanceService());
        
        $unavailableProduct = Product::factory()->create([
            'name' => 'Unavailable Item',
            'price' => 100.00,
            'status' => 'unavailable',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('not available');

        $service->createTransaction([
            'faculty_id' => $this->faculty->id,
            'recorded_by' => $this->cashier->id,
            'items' => [
                ['product_id' => $unavailableProduct->id, 'quantity' => 1],
            ],
            'override_credit_limit' => false,
        ]);
    }

    /** @test */
    public function voided_transactions_do_not_count_toward_balance()
    {
        $service = new TransactionService(new CreditBalanceService());
        
        // Create transaction
        $transaction = $service->createTransaction([
            'faculty_id' => $this->faculty->id,
            'recorded_by' => $this->cashier->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2], // 300
            ],
            'override_credit_limit' => false,
        ]);

        $balanceService = new CreditBalanceService();
        $this->assertEquals(300.00, $balanceService->getBalance($this->faculty));

        // Void the transaction
        $service->voidTransaction($transaction, $this->admin, 'Wrong item entered');

        // Balance should be back to zero
        $this->assertEquals(0, $balanceService->getBalance($this->faculty));
        $transaction->refresh();
        $this->assertEquals('voided', $transaction->status->value);
    }

    /** @test */
    public function testTransactionCapturesUnitPriceAtTimeOfSale()
    {
        $service = new TransactionService(new CreditBalanceService());
        
        $transaction = $service->createTransaction([
            'faculty_id' => $this->faculty->id,
            'recorded_by' => $this->cashier->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
            'override_credit_limit' => false,
        ]);

        $item = $transaction->items->first();
        $this->assertEquals(150.00, $item->unit_price);
        $this->assertEquals('Chicken Meal', $item->product_name_snapshot);

        // Change product price
        $this->product->update(['price' => 200.00]);

        // Original transaction should still have old price
        $transaction->refresh();
        $item = $transaction->items->first();
        $this->assertEquals(150.00, $item->unit_price);
        $this->assertEquals('Chicken Meal', $item->product_name_snapshot);
    }
}