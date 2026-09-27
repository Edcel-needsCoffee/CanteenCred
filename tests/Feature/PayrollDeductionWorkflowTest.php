<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\PayrollDeduction;
use App\Models\PayrollPeriod;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Enums\PayrollDeductionStatus;
use App\Enums\PayrollPeriodStatus;
use App\Enums\TransactionStatus;
use App\Enums\UserRole;
use App\Services\PayrollDeductionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollDeductionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $cashier;
    protected User $payroll;
    protected Faculty $faculty1;
    protected Faculty $faculty2;
    protected Product $product1;
    protected Product $product2;
    protected PayrollPeriod $period;

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

        $this->payroll = User::factory()->create([
            'role' => UserRole::PAYROLL->value,
            'is_active' => true,
        ]);

        $this->faculty1 = Faculty::factory()->create([
            'faculty_number' => '2026-00001',
            'name' => 'Juan Dela Cruz',
            'department' => 'College of IT',
            'credit_limit' => 2000.00,
            'is_active' => true,
        ]);

        $this->faculty2 = Faculty::factory()->create([
            'faculty_number' => '2026-00002',
            'name' => 'Maria Santos',
            'department' => 'College of Engineering',
            'credit_limit' => 2500.00,
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

        $this->period = PayrollPeriod::create([
            'label' => 'September 2026',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'status' => PayrollPeriodStatus::CLOSED->value,
        ]);
    }

    /** @test */
    public function testAdminCanGenerateDraftDeductionsForClosedPeriod()
    {
        // Create some transactions for faculty1
        Transaction::create([
            'transaction_number' => 'TXN-20260901-0001',
            'faculty_id' => $this->faculty1->id,
            'recorded_by' => $this->cashier->id,
            'transaction_date' => '2026-09-15 12:00:00',
            'total_amount' => 300.00,
            'status' => TransactionStatus::ACTIVE->value,
        ])->items()->create([
            'product_id' => $this->product1->id,
            'product_name_snapshot' => $this->product1->name,
            'quantity' => 2,
            'unit_price' => 150.00,
            'subtotal' => 300.00,
        ]);

        Transaction::create([
            'transaction_number' => 'TXN-20260920-0001',
            'faculty_id' => $this->faculty2->id,
            'recorded_by' => $this->cashier->id,
            'transaction_date' => '2026-09-20 12:00:00',
            'total_amount' => 200.00,
            'status' => TransactionStatus::ACTIVE->value,
        ])->items()->create([
            'product_id' => $this->product2->id,
            'product_name_snapshot' => $this->product2->name,
            'quantity' => 4,
            'unit_price' => 50.00,
            'subtotal' => 200.00,
        ]);

        $service = app(PayrollDeductionService::class);
        $deductions = $service->generateDeductions($this->period, $this->admin);

        $this->assertCount(2, $deductions);
        $this->assertEquals(PayrollDeductionStatus::DRAFT, $deductions[0]->status);
        $this->assertEquals(300.00, $deductions->firstWhere('faculty_id', $this->faculty1->id)->total_amount);
        $this->assertEquals(200.00, $deductions->firstWhere('faculty_id', $this->faculty2->id)->total_amount);

        // Verify transactions are linked
        $this->assertNotNull(Transaction::where('payroll_deduction_id', $deductions[0]->id)->first());
    }

    /** @test */
    public function testCannotGenerateDeductionsForOpenPeriod()
    {
        $openPeriod = PayrollPeriod::create([
            'label' => 'October 2026',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'status' => PayrollPeriodStatus::OPEN->value,
        ]);

        $service = app(PayrollDeductionService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Can only generate deductions for closed payroll periods.');

        $service->generateDeductions($openPeriod, $this->admin);
    }

    /** @test */
    public function testCannotGenerateDeductionsTwiceForSamePeriod()
    {
        Transaction::create([
            'transaction_number' => 'TXN-20260901-0001',
            'faculty_id' => $this->faculty1->id,
            'recorded_by' => $this->cashier->id,
            'transaction_date' => '2026-09-15 12:00:00',
            'total_amount' => 300.00,
            'status' => TransactionStatus::ACTIVE->value,
        ])->items()->create([
            'product_id' => $this->product1->id,
            'product_name_snapshot' => $this->product1->name,
            'quantity' => 2,
            'unit_price' => 150.00,
            'subtotal' => 300.00,
        ]);

        $service = app(PayrollDeductionService::class);
        $service->generateDeductions($this->period, $this->admin);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Deductions have already been generated for this period.');

        $service->generateDeductions($this->period, $this->admin);
    }

    /** @test */
    public function testAdminCanApproveDraftDeduction()
    {
        Transaction::create([
            'transaction_number' => 'TXN-20260901-0001',
            'faculty_id' => $this->faculty1->id,
            'recorded_by' => $this->cashier->id,
            'transaction_date' => '2026-09-15 12:00:00',
            'total_amount' => 300.00,
            'status' => TransactionStatus::ACTIVE->value,
        ])->items()->create([
            'product_id' => $this->product1->id,
            'product_name_snapshot' => $this->product1->name,
            'quantity' => 2,
            'unit_price' => 150.00,
            'subtotal' => 300.00,
        ]);

        $service = app(PayrollDeductionService::class);
        $deductions = $service->generateDeductions($this->period, $this->admin);
        $deduction = $deductions->first();

        $service->approveDeduction($deduction, $this->admin);

        $deduction->refresh();
        $this->assertEquals(PayrollDeductionStatus::APPROVED, $deduction->status);
        $this->assertNotNull($deduction->approved_at);
        $this->assertEquals($this->admin->id, $deduction->approved_by);
    }

    /** @test */
    public function testAdminCanRejectDraftDeduction()
    {
        Transaction::create([
            'transaction_number' => 'TXN-20260901-0001',
            'faculty_id' => $this->faculty1->id,
            'recorded_by' => $this->cashier->id,
            'transaction_date' => '2026-09-15 12:00:00',
            'total_amount' => 300.00,
            'status' => TransactionStatus::ACTIVE->value,
        ])->items()->create([
            'product_id' => $this->product1->id,
            'product_name_snapshot' => $this->product1->name,
            'quantity' => 2,
            'unit_price' => 150.00,
            'subtotal' => 300.00,
        ]);

        $service = app(PayrollDeductionService::class);
        $deductions = $service->generateDeductions($this->period, $this->admin);
        $deduction = $deductions->first();

        $transactionId = $deduction->transactions->first()->id;

        $service->rejectDeduction($deduction, $this->admin, 'Incorrect amount');

        $deduction->refresh();
        $this->assertEquals(PayrollDeductionStatus::REJECTED, $deduction->status);
        $this->assertEquals('Incorrect amount', $deduction->notes);

        // Transactions should be unlinked
        $this->assertNull(Transaction::find($transactionId)->payroll_deduction_id);
    }

    /** @test */
    public function testPayrollCanMarkApprovedDeductionAsProcessed()
    {
        Transaction::create([
            'transaction_number' => 'TXN-20260901-0001',
            'faculty_id' => $this->faculty1->id,
            'recorded_by' => $this->cashier->id,
            'transaction_date' => '2026-09-15 12:00:00',
            'total_amount' => 300.00,
            'status' => TransactionStatus::ACTIVE->value,
        ])->items()->create([
            'product_id' => $this->product1->id,
            'product_name_snapshot' => $this->product1->name,
            'quantity' => 2,
            'unit_price' => 150.00,
            'subtotal' => 300.00,
        ]);

        $service = app(PayrollDeductionService::class);
        $deductions = $service->generateDeductions($this->period, $this->admin);
        $deduction = $deductions->first();

        $service->approveDeduction($deduction, $this->admin);
        $service->markProcessed($deduction, $this->payroll);

        $deduction->refresh();
        $this->assertEquals(PayrollDeductionStatus::PROCESSED, $deduction->status);
        $this->assertNotNull($deduction->processed_at);
    }

    /** @test */
    public function testCannotProcessUnapprovedDeduction()
    {
        Transaction::create([
            'transaction_number' => 'TXN-20260901-0001',
            'faculty_id' => $this->faculty1->id,
            'recorded_by' => $this->cashier->id,
            'transaction_date' => '2026-09-15 12:00:00',
            'total_amount' => 300.00,
            'status' => TransactionStatus::ACTIVE->value,
        ])->items()->create([
            'product_id' => $this->product1->id,
            'product_name_snapshot' => $this->product1->name,
            'quantity' => 2,
            'unit_price' => 150.00,
            'subtotal' => 300.00,
        ]);

        $service = app(PayrollDeductionService::class);
        $deductions = $service->generateDeductions($this->period, $this->admin);
        $deduction = $deductions->first();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only approved deductions can be marked as processed.');

        $service->markProcessed($deduction, $this->payroll);
    }

    /** @test */
    public function testTransactionCannotBeInMultipleDeductions()
    {
        Transaction::create([
            'transaction_number' => 'TXN-20260901-0001',
            'faculty_id' => $this->faculty1->id,
            'recorded_by' => $this->cashier->id,
            'transaction_date' => '2026-09-15 12:00:00',
            'total_amount' => 300.00,
            'status' => TransactionStatus::ACTIVE->value,
        ])->items()->create([
            'product_id' => $this->product1->id,
            'product_name_snapshot' => $this->product1->name,
            'quantity' => 2,
            'unit_price' => 150.00,
            'subtotal' => 300.00,
        ]);

        $service = app(PayrollDeductionService::class);
        $deductions = $service->generateDeductions($this->period, $this->admin);
        $deduction = $deductions->first();
        $service->approveDeduction($deduction, $this->admin);

        // Create another period
        $period2 = PayrollPeriod::create([
            'label' => 'October 2026',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'status' => PayrollPeriodStatus::CLOSED->value,
        ]);

        // Try to create a transaction with the same faculty but it should not be included
        // since it's already linked to a deduction
        $service2 = app(PayrollDeductionService::class);
        
        // The transaction is already linked, so it shouldn't appear in the new period's deductions
        $newTransaction = Transaction::create([
            'transaction_number' => 'TXN-20261001-0001',
            'faculty_id' => $this->faculty1->id,
            'recorded_by' => $this->cashier->id,
            'transaction_date' => '2026-10-15 12:00:00',
            'total_amount' => 200.00,
            'status' => TransactionStatus::ACTIVE->value,
        ]);
        $newTransaction->items()->create([
            'product_id' => $this->product2->id,
            'product_name_snapshot' => $this->product2->name,
            'quantity' => 4,
            'unit_price' => 50.00,
            'subtotal' => 200.00,
        ]);

        $deductions2 = $service2->generateDeductions($period2, $this->admin);
        
        // Should only have the new transaction
        $this->assertCount(1, $deductions2);
        $this->assertEquals(200.00, $deductions2->first()->total_amount);
    }

    /** @test */
    public function testExportCsvWorks()
    {
        Transaction::create([
            'transaction_number' => 'TXN-20260901-0001',
            'faculty_id' => $this->faculty1->id,
            'recorded_by' => $this->cashier->id,
            'transaction_date' => '2026-09-15 12:00:00',
            'total_amount' => 300.00,
            'status' => TransactionStatus::ACTIVE->value,
        ])->items()->create([
            'product_id' => $this->product1->id,
            'product_name_snapshot' => $this->product1->name,
            'quantity' => 2,
            'unit_price' => 150.00,
            'subtotal' => 300.00,
        ]);

        $service = app(PayrollDeductionService::class);
        $deductions = $service->generateDeductions($this->period, $this->admin);
        $service->approveDeduction($deductions->first(), $this->admin);

        $exportData = $service->exportApprovedDeductions($this->period);

        $this->assertEquals(1, $exportData['count']);
        $this->assertEquals(300.00, $exportData['total']);
        $this->assertEquals(['Faculty Number', 'Faculty Name', 'Department', 'Total Amount', 'Status', 'Approved At', 'Processed At'], $exportData['headers']);
        $this->assertEquals('2026-00001', $exportData['rows'][0][0]);
        $this->assertEquals('Juan Dela Cruz', $exportData['rows'][0][1]);
    }

    /** @test */
    public function testExportPdfWorks()
    {
        Transaction::create([
            'transaction_number' => 'TXN-20260901-0001',
            'faculty_id' => $this->faculty1->id,
            'recorded_by' => $this->cashier->id,
            'transaction_date' => '2026-09-15 12:00:00',
            'total_amount' => 300.00,
            'status' => TransactionStatus::ACTIVE->value,
        ])->items()->create([
            'product_id' => $this->product1->id,
            'product_name_snapshot' => $this->product1->name,
            'quantity' => 2,
            'unit_price' => 150.00,
            'subtotal' => 300.00,
        ]);

        $service = app(PayrollDeductionService::class);
        $deductions = $service->generateDeductions($this->period, $this->admin);
        $service->approveDeduction($deductions->first(), $this->admin);

        $pdfContent = $service->exportApprovedDeductionsPdf($this->period);

        $this->assertIsString($pdfContent);
        $this->assertStringStartsWith('%PDF', $pdfContent);
    }

    /** @test */
    public function testFacultyCannotSeeUnapprovedDeductions()
    {
        $facultyUser = User::factory()->create([
            'role' => UserRole::FACULTY->value,
            'faculty_id' => $this->faculty1->id,
            'is_active' => true,
        ]);

        Transaction::create([
            'transaction_number' => 'TXN-20260901-0001',
            'faculty_id' => $this->faculty1->id,
            'recorded_by' => $this->cashier->id,
            'transaction_date' => '2026-09-15 12:00:00',
            'total_amount' => 300.00,
            'status' => TransactionStatus::ACTIVE->value,
        ])->items()->create([
            'product_id' => $this->product1->id,
            'product_name_snapshot' => $this->product1->name,
            'quantity' => 2,
            'unit_price' => 150.00,
            'subtotal' => 300.00,
        ]);

        $service = app(PayrollDeductionService::class);
        $deductions = $service->generateDeductions($this->period, $this->admin);
        // Don't approve - leave as draft

        $response = $this->actingAs($facultyUser)
            ->getJson('/api/v1/faculty/me/deductions');

        $response->assertStatus(200);
        $this->assertEmpty($response->json('data'));
    }

    /** @test */
    public function testFacultyCanSeeApprovedDeductions()
    {
        $facultyUser = User::factory()->create([
            'role' => UserRole::FACULTY->value,
            'faculty_id' => $this->faculty1->id,
            'is_active' => true,
        ]);

        Transaction::create([
            'transaction_number' => 'TXN-20260901-0001',
            'faculty_id' => $this->faculty1->id,
            'recorded_by' => $this->cashier->id,
            'transaction_date' => '2026-09-15 12:00:00',
            'total_amount' => 300.00,
            'status' => TransactionStatus::ACTIVE->value,
        ])->items()->create([
            'product_id' => $this->product1->id,
            'product_name_snapshot' => $this->product1->name,
            'quantity' => 2,
            'unit_price' => 150.00,
            'subtotal' => 300.00,
        ]);

        $service = app(PayrollDeductionService::class);
        $deductions = $service->generateDeductions($this->period, $this->admin);
        $service->approveDeduction($deductions->first(), $this->admin);

        $response = $this->actingAs($facultyUser)
            ->getJson('/api/v1/faculty/me/deductions');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals(300.00, $response->json('data.0.total_amount'));
    }

    /** @test */
    public function testRejectedDeductionUnlinksTransactions()
    {
        Transaction::create([
            'transaction_number' => 'TXN-20260901-0001',
            'faculty_id' => $this->faculty1->id,
            'recorded_by' => $this->cashier->id,
            'transaction_date' => '2026-09-15 12:00:00',
            'total_amount' => 300.00,
            'status' => TransactionStatus::ACTIVE->value,
        ])->items()->create([
            'product_id' => $this->product1->id,
            'product_name_snapshot' => $this->product1->name,
            'quantity' => 2,
            'unit_price' => 150.00,
            'subtotal' => 300.00,
        ]);

        $service = app(PayrollDeductionService::class);
        $deductions = $service->generateDeductions($this->period, $this->admin);
        $deduction = $deductions->first();

        // Verify transaction is linked
        $this->assertNotNull(Transaction::where('payroll_deduction_id', $deduction->id)->first());

        $service->rejectDeduction($deduction, $this->admin, 'Incorrect amount');

        // Transaction should be unlinked
        $this->assertNull(Transaction::where('payroll_deduction_id', $deduction->id)->first());
    }
}