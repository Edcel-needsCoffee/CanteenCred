<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Models\PayrollPeriod;
use App\Models\PayrollDeduction;
use App\Enums\UserRole;
use App\Enums\TransactionStatus;
use App\Enums\PayrollPeriodStatus;
use App\Enums\PayrollDeductionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class POSTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $cashier;
    protected Faculty $faculty;
    protected Product $product1;
    protected Product $product2;

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

        $this->product1 = Product::factory()->create([
            'name' => 'Chicken Meal',
            'price' => 150.00,
            'status' => 'available',
        ]);

        $this->product2 = Product::factory()->create([
            'name' => 'Iced Coffee',
            'price' => 50.00,
            'status' => 'available',
        ]);
    }

    /** @test */
    public function testCashierCanAccessPosPage()
    {
        $response = $this->actingAs($this->cashier)->get('/cashier/pos');
        $response->assertStatus(200);
        $response->assertSee('Faculty Lookup');
    }

    /** @test */
    public function testAdminCanAccessPosPage()
    {
        $response = $this->actingAs($this->admin)->get('/cashier/pos');
        $response->assertStatus(200);
    }

    /** @test */
    public function testFacultyCannotAccessPosPage()
    {
        $facultyUser = User::factory()->create([
            'role' => UserRole::FACULTY->value,
            'faculty_id' => $this->faculty->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($facultyUser)->get('/cashier/pos');
        $response->assertStatus(403);
    }

    /** @test */
    public function testUnauthenticatedUserCannotAccessPosPage()
    {
        $response = $this->get('/cashier/pos');
        $response->assertRedirect('/login');
    }

    /** @test */
    public function testPosApiCreatesTransactionSuccessfully()
    {
        $response = $this->actingAs($this->cashier)
            ->postJson('/api/v1/transactions', [
                'faculty_id' => $this->faculty->id,
                'items' => [
                    ['product_id' => $this->product1->id, 'quantity' => 1],
                    ['product_id' => $this->product2->id, 'quantity' => 2],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'transaction_number',
                    'faculty_id',
                    'total_amount',
                    'status',
                    'items',
                ],
                'message',
            ]);

        $this->assertEquals(250.00, $response->json('data.total_amount'));
        $this->assertEquals('active', $response->json('data.status'));
    }

    /** @test */
    public function testPosApiRejectsTransactionExceedingCreditLimit()
    {
        $expensiveProduct = Product::factory()->create([
            'name' => 'Expensive Item',
            'price' => 2500.00,
            'status' => 'available',
        ]);

        $response = $this->actingAs($this->cashier)
            ->postJson('/api/v1/transactions', [
                'faculty_id' => $this->faculty->id,
                'items' => [
                    ['product_id' => $expensiveProduct->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(409)
            ->assertJsonStructure([
                'error' => [
                    'code',
                    'message',
                    'fields' => [
                        'remaining_credit',
                        'attempted_total',
                    ],
                ],
            ]);

        $this->assertEquals('CREDIT_LIMIT_EXCEEDED', $response->json('error.code'));
    }

    /** @test */
    public function testPosApiAllowsAdminOverride()
    {
        $expensiveProduct = Product::factory()->create([
            'name' => 'Expensive Item',
            'price' => 2500.00,
            'status' => 'available',
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/transactions', [
                'faculty_id' => $this->faculty->id,
                'items' => [
                    ['product_id' => $expensiveProduct->id, 'quantity' => 1],
                ],
                'override_credit_limit' => true,
                'override_reason' => 'Admin approved',
            ]);

        $response->assertStatus(201);
        $this->assertTrue($response->json('data.credit_limit_override'));
        $this->assertEquals('Admin approved', $response->json('data.override_reason'));
    }

    /** @test */
    public function testPosApiRejectsCashierOverride()
    {
        $expensiveProduct = Product::factory()->create([
            'name' => 'Expensive Item',
            'price' => 2500.00,
            'status' => 'available',
        ]);

        $response = $this->actingAs($this->cashier)
            ->postJson('/api/v1/transactions', [
                'faculty_id' => $this->faculty->id,
                'items' => [
                    ['product_id' => $expensiveProduct->id, 'quantity' => 1],
                ],
                'override_credit_limit' => true,
                'override_reason' => 'Cashier tried',
            ]);

        $response->assertStatus(409);
        $this->assertEquals('CREDIT_LIMIT_EXCEEDED', $response->json('error.code'));
    }

    /** @test */
    public function testPosApiRejectsDisabledFaculty()
    {
        $this->faculty->update(['is_active' => false]);

        $response = $this->actingAs($this->cashier)
            ->postJson('/api/v1/transactions', [
                'faculty_id' => $this->faculty->id,
                'items' => [
                    ['product_id' => $this->product1->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function testPosApiRejectsUnavailableProduct()
    {
        $unavailableProduct = Product::factory()->create([
            'name' => 'Unavailable',
            'price' => 100.00,
            'status' => 'unavailable',
        ]);

        $response = $this->actingAs($this->cashier)
            ->postJson('/api/v1/transactions', [
                'faculty_id' => $this->faculty->id,
                'items' => [
                    ['product_id' => $unavailableProduct->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function testPosApiValidatesRequiredFields()
    {
        $response = $this->actingAs($this->cashier)
            ->postJson('/api/v1/transactions', [
                'faculty_id' => $this->faculty->id,
                'items' => [],
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function testPosApiReturnsTransactionWithLineItems()
    {
        $response = $this->actingAs($this->cashier)
            ->postJson('/api/v1/transactions', [
                'faculty_id' => $this->faculty->id,
                'items' => [
                    ['product_id' => $this->product1->id, 'quantity' => 2],
                ],
            ]);

        $response->assertStatus(201);
        $this->assertCount(1, $response->json('data.items'));
        $this->assertEquals(2, $response->json('data.items.0.quantity'));
        $this->assertEquals(150.00, $response->json('data.items.0.unit_price'));
        $this->assertEquals(300.00, $response->json('data.items.0.subtotal'));
        $this->assertEquals('Chicken Meal', $response->json('data.items.0.product_name_snapshot'));
    }

    /** @test */
    public function testFacultySearchApiWorks()
    {
        $response = $this->actingAs($this->cashier)
            ->getJson('/api/v1/faculty/search?q=Test');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'faculty_number',
                        'name',
                        'department',
                        'credit_limit',
                        'balance',
                        'remaining_credit',
                        'is_active',
                    ],
                ],
            ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('2026-00001', $response->json('data.0.faculty_number'));
    }

    /** @test */
    public function testFacultySearchRequiresMinimumQueryLength()
    {
        $response = $this->actingAs($this->cashier)
            ->getJson('/api/v1/faculty/search?q=T');

        $response->assertStatus(200);
        $this->assertEquals('Search query must be at least 2 characters', $response->json('message'));
    }

    /** @test */
    public function testFacultyBalanceApiWorks()
    {
        $response = $this->actingAs($this->cashier)
            ->getJson('/api/v1/faculty/' . $this->faculty->id . '/balance');
            dump('Response: ' . $response->getContent());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'credit_limit',
                    'current_balance',
                    'remaining_credit',
                    'percentage_used',
                ],
            ]);

        $this->assertEquals(2000.00, $response->json('data.credit_limit'));
        $this->assertEquals(0, $response->json('data.current_balance'));
        $this->assertEquals(2000.00, $response->json('data.remaining_credit'));
    }

    /** @test */
    public function testTransactionCreatesAuditLog()
    {
        $this->actingAs($this->cashier)
            ->postJson('/api/v1/transactions', [
                'faculty_id' => $this->faculty->id,
                'items' => [
                    ['product_id' => $this->product1->id, 'quantity' => 1],
                ],
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'transaction.created',
            'subject_type' => 'App\Models\Transaction',
            'user_id' => $this->cashier->id,
        ]);
    }

    /** @test */
    public function testCreditLimitOverrideCreatesAuditLog()
    {
        $expensiveProduct = Product::factory()->create([
            'name' => 'Expensive Item',
            'price' => 2500.00,
            'status' => 'available',
        ]);

        $this->actingAs($this->admin)
            ->postJson('/api/v1/transactions', [
                'faculty_id' => $this->faculty->id,
                'items' => [
                    ['product_id' => $expensiveProduct->id, 'quantity' => 1],
                ],
                'override_credit_limit' => true,
                'override_reason' => 'Admin override',
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'transaction.credit_limit_override',
            'user_id' => $this->admin->id,
        ]);
    }

    /** @test */
    public function testTransactionGeneratesUniqueTransactionNumber()
    {
        $response1 = $this->actingAs($this->cashier)
            ->postJson('/api/v1/transactions', [
                'faculty_id' => $this->faculty->id,
                'items' => [['product_id' => $this->product1->id, 'quantity' => 1]],
            ]);

        $response2 = $this->actingAs($this->cashier)
            ->postJson('/api/v1/transactions', [
                'faculty_id' => $this->faculty->id,
                'items' => [['product_id' => $this->product2->id, 'quantity' => 1]],
            ]);

        $this->assertNotEquals(
            $response1->json('data.transaction_number'),
            $response2->json('data.transaction_number')
        );
    }

    /** @test */
    public function testVoidTransactionViaApi()
    {
        $response = $this->actingAs($this->cashier)
            ->postJson('/api/v1/transactions', [
                'faculty_id' => $this->faculty->id,
                'items' => [['product_id' => $this->product1->id, 'quantity' => 1]],
            ]);

        $transactionId = $response->json('data.id');

        $voidResponse = $this->actingAs($this->admin)
            ->patchJson("/api/v1/transactions/{$transactionId}/void", [
                'reason' => 'Wrong item',
            ]);
            dump('Void Response: ' . $voidResponse->getContent());

        $voidResponse->assertStatus(200);
        $this->assertEquals('voided', $voidResponse->json('data.status'));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'transaction.voided',
            'subject_id' => $transactionId,
        ]);
    }

    /** @test */
    public function testCashierCannotVoidTransactionOnlyAdmin()
    {
        $response = $this->actingAs($this->cashier)
            ->postJson('/api/v1/transactions', [
                'faculty_id' => $this->faculty->id,
                'items' => [['product_id' => $this->product1->id, 'quantity' => 1]],
            ]);

        $transactionId = $response->json('data.id');

        $voidResponse = $this->actingAs($this->cashier)
            ->patchJson("/api/v1/transactions/{$transactionId}/void", [
                'reason' => 'Wrong item',
            ]);

        $voidResponse->assertStatus(403);
    }

    /** @test */
    public function testCannotVoidTransactionLinkedToPayrollDeduction()
    {
        // Create a payroll period and deduction to link to the transaction
        $payrollPeriod = PayrollPeriod::factory()->create([
            'label' => 'Test Period',
            'start_date' => now()->subMonth()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
            'status' => PayrollPeriodStatus::CLOSED->value,
        ]);

        $deduction = PayrollDeduction::factory()->create([
            'payroll_period_id' => $payrollPeriod->id,
            'faculty_id' => $this->faculty->id,
            'total_amount' => 100.00,
            'status' => PayrollDeductionStatus::APPROVED->value,
        ]);

        $transaction = Transaction::factory()->create([
            'faculty_id' => $this->faculty->id,
            'recorded_by' => $this->cashier->id,
            'payroll_deduction_id' => $deduction->id,
            'status' => TransactionStatus::ACTIVE->value,
        ]);

        $service = app(\App\Services\TransactionService::class);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('included in a payroll deduction');

        $service->voidTransaction($transaction, $this->admin, 'Test');
    }
}