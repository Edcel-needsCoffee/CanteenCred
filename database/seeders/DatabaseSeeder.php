<?php

namespace Database\Seeders;

use App\Models\Faculty;
use App\Models\Product;
use App\Models\User;
use App\Enums\UserRole;
use App\Enums\ProductStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Products
        $products = [
            ['name' => 'Chicken Meal', 'price' => 150.00],
            ['name' => 'Beef Rice Bowl', 'price' => 180.00],
            ['name' => 'Fish Fillet Meal', 'price' => 170.00],
            ['name' => 'Pork Chop Meal', 'price' => 160.00],
            ['name' => 'Vegetable Stir-fry', 'price' => 120.00],
            ['name' => 'Iced Coffee', 'price' => 50.00],
            ['name' => 'Hot Coffee', 'price' => 40.00],
            ['name' => 'Iced Tea', 'price' => 35.00],
            ['name' => 'Bottled Water', 'price' => 20.00],
            ['name' => 'Soft Drinks', 'price' => 35.00],
            ['name' => 'Banana', 'price' => 15.00],
            ['name' => 'Apple', 'price' => 25.00],
            ['name' => 'Sandwich', 'price' => 80.00],
            ['name' => 'Burger', 'price' => 110.00],
            ['name' => 'Fries', 'price' => 60.00],
        ];

        foreach ($products as $p) {
            Product::create([
                'name' => $p['name'],
                'price' => $p['price'],
                'status' => ProductStatus::AVAILABLE->value,
            ]);
        }

        // 2. Create Faculty Accounts
        $facultyData = [
            ['faculty_number' => '2026-00001', 'name' => 'Juan Dela Cruz', 'department' => 'College of IT', 'credit_limit' => 2000.00],
            ['faculty_number' => '2026-00002', 'name' => 'Maria Santos', 'department' => 'College of Engineering', 'credit_limit' => 2500.00],
            ['faculty_number' => '2026-00003', 'name' => 'Pedro Garcia', 'department' => 'College of Business', 'credit_limit' => 1500.00],
            ['faculty_number' => '2026-00004', 'name' => 'Ana Reyes', 'department' => 'College of Arts', 'credit_limit' => 1800.00],
            ['faculty_number' => '2026-00005', 'name' => 'Luis Torres', 'department' => 'College of Science', 'credit_limit' => 2200.00],
            ['faculty_number' => '2026-00006', 'name' => 'Carmen Lopez', 'department' => 'College of Education', 'credit_limit' => 1600.00],
            ['faculty_number' => '2026-00007', 'name' => 'Roberto Cruz', 'department' => 'College of Law', 'credit_limit' => 3000.00],
            ['faculty_number' => '2026-00008', 'name' => 'Elena Mendoza', 'department' => 'College of Medicine', 'credit_limit' => 2800.00],
        ];

        foreach ($facultyData as $fd) {
            Faculty::create([
                'faculty_number' => $fd['faculty_number'],
                'name' => $fd['name'],
                'department' => $fd['department'],
                'credit_limit' => $fd['credit_limit'],
                'is_active' => true,
            ]);
        }

        // 3. Create System Users with Roles
        $users = [
            // Administrator
            [
                'name' => 'Admin User',
                'username' => 'admin',
                'email' => 'admin@facucred.local',
                'password' => 'password123',
                'role' => UserRole::ADMINISTRATOR->value,
                'faculty_id' => null,
            ],
            // Cashier 1
            [
                'name' => 'Cashier One',
                'username' => 'cashier1',
                'email' => 'cashier1@facucred.local',
                'password' => 'password123',
                'role' => UserRole::CASHIER->value,
                'faculty_id' => null,
            ],
            // Cashier 2
            [
                'name' => 'Cashier Two',
                'username' => 'cashier2',
                'email' => 'cashier2@facucred.local',
                'password' => 'password123',
                'role' => UserRole::CASHIER->value,
                'faculty_id' => null,
            ],
            // Payroll Officer
            [
                'name' => 'Payroll Officer',
                'username' => 'payroll',
                'email' => 'payroll@facucred.local',
                'password' => 'password123',
                'role' => UserRole::PAYROLL->value,
                'faculty_id' => null,
            ],
            // Faculty Users (linked to faculty accounts)
            [
                'name' => 'Juan Dela Cruz',
                'username' => '2026-00001',
                'email' => 'juan.delacruz@faculty.local',
                'password' => 'password123',
                'role' => UserRole::FACULTY->value,
                'faculty_id' => 1, // Will be updated after faculty creation
            ],
            [
                'name' => 'Maria Santos',
                'username' => '2026-00002',
                'email' => 'maria.santos@faculty.local',
                'password' => 'password123',
                'role' => UserRole::FACULTY->value,
                'faculty_id' => 2,
            ],
            [
                'name' => 'Pedro Garcia',
                'username' => '2026-00003',
                'email' => 'pedro.garcia@faculty.local',
                'password' => 'password123',
                'role' => UserRole::FACULTY->value,
                'faculty_id' => 3,
            ],
        ];

        foreach ($users as $u) {
            User::create([
                'name' => $u['name'],
                'username' => $u['username'],
                'email' => $u['email'],
                'password' => Hash::make($u['password']),
                'role' => $u['role'],
                'faculty_id' => $u['faculty_id'],
                'is_active' => true,
            ]);
        }

        // 4. Create a sample Payroll Period
        $payrollPeriod = \App\Models\PayrollPeriod::create([
            'label' => 'September 2026',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'status' => \App\Enums\PayrollPeriodStatus::OPEN->value,
        ]);

        // 5. Create some sample transactions for testing
        $this->createSampleTransactions();

        $this->command->info('Database seeded successfully!');
        $this->command->info('Test Accounts:');
        $this->command->info('  Administrator: admin / password123');
        $this->command->info('  Cashier: cashier1 / password123');
        $this->command->info('  Cashier: cashier2 / password123');
        $this->command->info('  Payroll: payroll / password123');
        $this->command->info('  Faculty: 2026-00001 / password123 (Juan Dela Cruz)');
        $this->command->info('  Faculty: 2026-00002 / password123 (Maria Santos)');
        $this->command->info('  Faculty: 2026-00003 / password123 (Pedro Garcia)');
    }

    private function createSampleTransactions(): void
    {
        $cashier = User::where('username', 'cashier1')->first();
        $faculty = Faculty::where('faculty_number', '2026-00001')->first();
        $products = Product::available()->get();

        if (!$cashier || !$faculty || $products->count() < 3) {
            return;
        }

        $transactionService = app(\App\Services\TransactionService::class);

        // Create a few sample transactions
        $sampleTransactions = [
            [
                'items' => [
                    ['product_id' => $products[0]->id, 'quantity' => 1],
                    ['product_id' => $products[1]->id, 'quantity' => 1],
                ],
            ],
            [
                'items' => [
                    ['product_id' => $products[2]->id, 'quantity' => 1],
                    ['product_id' => $products[3]->id, 'quantity' => 1],
                    ['product_id' => $products[4]->id, 'quantity' => 1],
                ],
            ],
            [
                'items' => [
                    ['product_id' => $products[0]->id, 'quantity' => 1],
                ],
            ],
        ];

        foreach ($sampleTransactions as $txn) {
            try {
                $transactionService->createTransaction([
                    'faculty_id' => $faculty->id,
                    'recorded_by' => $cashier->id,
                    'items' => $txn['items'],
                    'override_credit_limit' => false,
                ]);
            } catch (\Exception $e) {
                // Skip if error
            }
        }
    }
}