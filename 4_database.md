# 04 — Database Specification

## 1. Database Engine

MySQL shall be used as the database for FACU-CRED, accessed through Eloquent ORM and managed through Laravel Migrations.

The database is the authoritative persistent storage for all critical application data. Critical application data must not depend solely on browser storage, temporary client-side state, or cached data.

## 2. Entity Overview

````text
users
  │
  ├── (has one, if role = faculty) faculty
  │
  └── (creates) transactions, corrections, payroll_deductions (as actor)

faculty
  │
  └── 1 ── * transactions

products
  │
  └── 1 ── * transaction_items

transactions
  │
  └── 1 ── * transaction_items

payroll_periods
  │
  └── 1 ── * payroll_deductions
  │
  └── * ── * transactions (via payroll_deductions linkage)

payroll_deductions
  │
  └── belongs to faculty, payroll_period

audit_logs
  │
  └── references any of the above via a polymorphic/loose reference
````

## 3. Tables

### 3.1 `users`

| Column | Type | Notes |
|---|---|---|
| id | bigint, PK | |
| name | string | |
| email | string, unique | |
| username | string, unique | used for login |
| password | string | hashed |
| role | enum | `administrator`, `cashier`, `payroll`, `faculty` |
| faculty_id | bigint, FK → faculty.id, nullable | set when role = `faculty` |
| is_active | boolean | default true |
| failed_login_attempts | integer | default 0 |
| locked_until | timestamp, nullable | |
| created_at / updated_at | timestamp | |

### 3.2 `faculty`

| Column | Type | Notes |
|---|---|---|
| id | bigint, PK | |
| faculty_number | string, unique | e.g. `2026-00125` |
| name | string | |
| department | string | |
| credit_limit | decimal(10,2) | |
| is_active | boolean | default true |
| created_at / updated_at | timestamp | |

### 3.3 `products`

| Column | Type | Notes |
|---|---|---|
| id | bigint, PK | |
| name | string | |
| price | decimal(10,2) | current price |
| status | enum | `available`, `unavailable` |
| created_at / updated_at | timestamp | |

### 3.4 `transactions`

| Column | Type | Notes |
|---|---|---|
| id | bigint, PK | |
| transaction_number | string, unique | e.g. `TXN-20260926-0001` |
| faculty_id | bigint, FK → faculty.id | |
| recorded_by | bigint, FK → users.id | cashier who recorded it |
| transaction_date | datetime | |
| total_amount | decimal(10,2) | sum of line items, denormalized for fast reporting |
| status | enum | `active`, `voided`, `corrected` |
| credit_limit_override | boolean | default false |
| override_reason | string, nullable | required if override = true |
| override_authorized_by | bigint, FK → users.id, nullable | |
| payroll_deduction_id | bigint, FK → payroll_deductions.id, nullable | set once locked into an approved report |
| created_at / updated_at | timestamp | |

### 3.5 `transaction_items`

| Column | Type | Notes |
|---|---|---|
| id | bigint, PK | |
| transaction_id | bigint, FK → transactions.id | |
| product_id | bigint, FK → products.id | |
| product_name_snapshot | string | product name at time of sale |
| quantity | integer | |
| unit_price | decimal(10,2) | price at time of sale — never recalculated from `products.price` |
| subtotal | decimal(10,2) | `quantity * unit_price` |
| created_at / updated_at | timestamp | |

### 3.6 `payroll_periods`

| Column | Type | Notes |
|---|---|---|
| id | bigint, PK | |
| label | string | e.g. `September 2026` |
| start_date | date | |
| end_date | date | |
| status | enum | `open`, `closed`, `finalized` |
| created_at / updated_at | timestamp | |

### 3.7 `payroll_deductions`

| Column | Type | Notes |
|---|---|---|
| id | bigint, PK | |
| payroll_period_id | bigint, FK → payroll_periods.id | |
| faculty_id | bigint, FK → faculty.id | |
| total_amount | decimal(10,2) | computed from linked transactions |
| status | enum | `draft`, `approved`, `rejected`, `processed` |
| generated_at | timestamp | |
| approved_by | bigint, FK → users.id, nullable | |
| approved_at | timestamp, nullable | |
| processed_at | timestamp, nullable | |
| notes | text, nullable | |
| created_at / updated_at | timestamp | |

### 3.8 `transaction_corrections`

| Column | Type | Notes |
|---|---|---|
| id | bigint, PK | |
| transaction_id | bigint, FK → transactions.id | |
| requested_by | bigint, FK → users.id | |
| original_snapshot | json | full original transaction + items |
| corrected_snapshot | json | full corrected transaction + items |
| reason | text | required |
| approved_by | bigint, FK → users.id, nullable | |
| approved_at | timestamp, nullable | |
| status | enum | `pending`, `approved`, `rejected` |
| created_at / updated_at | timestamp | |

### 3.9 `audit_logs`

| Column | Type | Notes |
|---|---|---|
| id | bigint, PK | |
| user_id | bigint, FK → users.id, nullable | actor; nullable for system actions |
| action | string | e.g. `transaction.created`, `transaction.corrected`, `deduction.approved`, `faculty.credit_limit_changed` |
| subject_type | string | e.g. `Transaction`, `PayrollDeduction` |
| subject_id | bigint | |
| description | text | human-readable summary |
| metadata | json, nullable | before/after values where relevant |
| created_at | timestamp | |

## 4. Relationships

- A `faculty` record has many `transactions`.
- A `transaction` has many `transaction_items`, each referencing a `product`.
- A `payroll_period` has many `payroll_deductions`, one per faculty member with activity in that period.
- A `transaction` is linked to at most one `payroll_deduction` once it has been included in an approved report (`payroll_deduction_id`), enforcing that a transaction can never be counted twice.
- A `transaction_correction` references the `transaction` it modifies and preserves both the original and corrected state.
- `audit_logs` reference the acting `user` and the affected record generically through `subject_type` / `subject_id`.

## 5. Data Integrity Rules

- `transaction_items.unit_price` and `product_name_snapshot` must be captured at the time of sale and must never be overwritten by later changes to `products`.
- `transactions.total_amount` must always equal the sum of its `transaction_items.subtotal` values; this should be enforced in the service layer that creates/updates transactions, not left to the client.
- A `transaction` cannot be linked to more than one `payroll_deduction`.
- A `payroll_deduction` cannot move to `approved` unless its underlying transactions are consistent with its `total_amount`.
- An `approved` or `processed` `payroll_deduction` and its linked transactions must not be editable; disputes after approval must be handled via a new correction/adjustment recorded against a later period.
- Audit log records must not be editable or deletable through normal application operations.
- Foreign keys must use `RESTRICT` or `NO ACTION` on delete for financial records (`transactions`, `transaction_items`, `payroll_deductions`) to prevent silent data loss; soft deletes (`is_active` / `status` flags) should be preferred over hard deletes for `faculty`, `products`, and `users`.

## 6. Seed Data

Development seed data should include:

- A small set of departments and faculty members with varying credit limits, for exercising the credit-limit-enforcement logic.
- A representative product/menu list with prices.
- One default administrator, one cashier, one payroll officer, and at least one faculty-linked login, for exercising role-based access during development.

## 7. Migrations

All schema changes must be made through Laravel Migrations. Migrations must be reversible (`up`/`down`) where practical, and must not be edited after they have been run against a shared environment — a new migration should be created instead.
