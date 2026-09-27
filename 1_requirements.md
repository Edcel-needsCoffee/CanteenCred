# Application Requirements

## 1. Application Overview

**FACU-CRED (Faculty Canteen Credit and Payroll Deduction System)** is an integrated canteen credit management system designed to replace the manual process of recording faculty food purchases on credit and computing month-end payroll deductions by hand.

When a faculty member avails of food or drinks at the canteen without paying cash, canteen staff record the purchase against that faculty member's account. The system accumulates every credit transaction throughout the payroll period, automatically computes each faculty member's outstanding balance, and — after an administrator reviews and approves the figures — produces a payroll deduction report that the Payroll/Finance office can act on.

The system also maintains a running credit balance and credit limit per faculty member, a full itemized transaction history, and an audit trail covering every correction, approval, and deduction so that disputes can always be traced back to the original transaction.

Overall, FACU-CRED aims to eliminate manual tallying, reduce computation errors, give faculty visibility into their own canteen credit, and give the canteen, administration, and payroll office a shared, auditable source of truth.

## Application Name

## FACU-CRED: Faculty Canteen Credit and Payroll Deduction System

### Purpose

*An integrated system for recording faculty canteen credit purchases, computing monthly totals, and generating approved payroll deduction reports.*

### Target Users

- **Faculty** – Avail of food/drinks on credit and view their own purchase history, running balance, and deduction status.
- **Canteen Staff (Cashier)** – Record credit transactions at the point of sale, search faculty accounts, and manage day-to-day availments.
- **Canteen Administrator** – Manage the menu/products, manage faculty accounts and credit limits, review monthly totals, and approve payroll deduction reports.
- **Payroll / Finance Officer** – Receive approved deduction reports, mark them as processed, and export them for the payroll run.

### Main Goal

To provide a centralized system that records every faculty canteen credit transaction as it happens, computes accurate monthly totals automatically, and produces a reviewed and approved payroll deduction report — removing manual end-of-month computation.

## 2. Scope

### 2.1 Faculty Accounts & Credit

- Faculty account registration and profile management
- Credit limit configuration per faculty member
- Running credit balance calculation
- Credit limit enforcement at point of sale
- Faculty self-service view of balance and history

### 2.2 Canteen Point-of-Sale (Credit Mode)

- Product/menu management (name, price, availability status)
- Faculty search/lookup at point of sale
- Line-item transaction entry (product, quantity, unit price at time of sale)
- Transaction confirmation and receipt-style summary
- Transaction voiding/cancellation with authorization and reason

#### Transaction Correction Workflow

- Correction request submission
- Authorization before modification
- Transaction record modification by authorized personnel
- Required reason for every correction
- Recording of the original transaction record
- Recording of the corrected transaction record
- Recording of the person who made the correction
- Correction date and time
- Recording of the approver, when applicable
- Audit log for all transaction corrections

**Workflow:**

`Correction Request → Authorization → Modification → Reason → Audit Log`

### 2.3 Monthly Credit Summary

- Per-faculty monthly transaction total
- Per-department monthly transaction total
- Payroll-period definition (start/end dates)
- Drill-down from a monthly total into its individual transactions

### 2.4 Payroll Deduction Workflow

- Automatic generation of a draft deduction report from a payroll period's transactions
- Administrator review of draft figures against underlying transactions
- Explicit approval step before a report is released to Payroll/Finance
- Rejection/hold of a faculty line item pending investigation
- Payroll/Finance view of approved reports only
- Marking a deduction report as processed/exported
- Prevention of double-deduction of the same transactions across periods

**Workflow:**

`Transactions Recorded → Period Closed → Draft Report Generated → Admin Review → Approval → Payroll Export → Marked Processed`

### 2.5 Faculty Self-Service Portal

- View current credit balance and remaining credit limit
- View itemized purchase history
- View current and past monthly totals
- View deduction status (pending, approved, deducted)
- View previous payroll periods

### 2.6 Administration

- User and role management
- Faculty account management (including credit limit changes)
- Product/menu management
- Audit logs and activity tracking
- Credit and payroll reports

### 2.7 Security Requirements

- Password complexity requirements for management pages
- Login attempt limits
- Account lockout after repeated failed login attempts
- Session timeout
- CSRF protection
- Input validation and sanitization
- API rate limiting
- Secure HTTPS communication
- Encryption of sensitive data
- Protection against unauthorized modification of audit logs and approved payroll reports
- Role and permission matrix
- Backup and Recovery

### Excluded Features

The following features are outside the scope of the current version of FACU-CRED:

- **Cash Payment Processing** – The system records credit availments only; cash sales at the canteen are outside scope.
- **Online/Card Payment Processing** – The system does not handle card, e-wallet, or online payments.
- **Full Payroll System** – The system produces a deduction report for Payroll/Finance; it does not compute or release actual payroll/payslips.
- **Inventory / Stock Management** – Ingredient-level inventory, stock counts, and supplier management are not included.
- **Biometric Authentication** – Fingerprint, facial recognition, or other biometric identification is not included.
- **RFID/Card-Tap Identification** – Faculty are looked up by search (ID/name) at point of sale in the current version; RFID tap identification is not included.
- **Multi-Branch/Multi-Canteen Support** – The current version assumes a single canteen location.
- **Offline POS Operation** – The current version assumes the canteen terminal has connectivity to the backend; offline transaction queuing is not included.
- **Public User Registration** – Faculty cannot freely create accounts without registration by an authorized administrator.

## 3. User Roles

| Role | Description | Main Access |
|---|---|---|
| Canteen Administrator | Manages the overall system, users, faculty accounts, products, credit limits, monthly reports, and payroll deduction approval. | Full system management, user/role management, product management, faculty management, reports, audit logs, approval |
| Canteen Staff / Cashier | Operates the canteen point-of-sale interface and records credit transactions. | Faculty lookup, product selection, transaction recording, transaction history, voiding with authorization |
| Payroll / Finance Officer | Receives and processes approved payroll deduction reports. | View approved deduction reports, export deduction data, mark deductions as processed |
| Faculty | Avails of canteen credit and views their own account. | Personal balance, personal transaction history, deduction status |

---

## 4. Functional Requirements

| ID | Requirement | Priority |
|---|---|---|
| FR-001 | The system shall allow the Canteen Administrator to securely log in and access administrative functions. | Critical |
| FR-002 | The system shall allow the Canteen Administrator to manage faculty accounts, including name, department, and credit limit. | Critical |
| FR-003 | The system shall allow authorized users to log in using their registered credentials. | High |
| FR-004 | The system shall allow Canteen Staff to record a credit transaction against a faculty account, capturing each line item's product, quantity, and unit price at time of sale. | Critical |
| FR-005 | The system shall prevent a credit transaction from being recorded if it would exceed the faculty member's remaining credit limit, unless explicitly overridden by an authorized user. | Critical |
| FR-006 | The system shall record a unique transaction number, timestamp, and recording cashier for every transaction. | Critical |
| FR-007 | The system shall prevent unauthorized users from accessing restricted system functions. | Critical |
| FR-008 | The system shall allow the Canteen Administrator to view faculty transaction history and monthly totals. | High |
| FR-009 | The system shall allow the Canteen Administrator to manage system users and their assigned roles. | High |
| FR-010 | The system shall allow the Canteen Administrator to manage the product/menu list, including name, price, and availability status. | High |
| FR-011 | The system shall automatically compute each faculty member's total credit for a given payroll period from their recorded transactions. | Critical |
| FR-012 | The system shall allow the Canteen Administrator to generate a draft payroll deduction report for a closed payroll period. | High |
| FR-013 | The system shall require Canteen Administrator approval before a payroll deduction report becomes visible to Payroll/Finance. | Critical |
| FR-014 | The system shall provide feedback when a transaction, correction, or user credential is invalid. | Medium |
| FR-015 | The system shall maintain transaction and deduction records for later viewing and management by authorized users. | High |
| FR-016 | The system shall allow Faculty to view their own current balance, transaction history, and deduction status. | High |
| FR-017 | The system shall allow the Payroll/Finance Officer to view only approved deduction reports and mark them as processed. | High |
| FR-018 | The system shall prevent the same transaction from being included in more than one payroll deduction report. | Critical |

## 5. General Application Behavior

- Authentication must be enforced for all protected system functions.
- Authorization must be enforced server-side based on the user's assigned role.
- Users must only access features and records permitted by their role.
- CRUD operations should be performed asynchronously where applicable to minimize unnecessary full-page browser refreshes.
- Forms should provide appropriate feedback after submission.
- The UI state should be updated after successful operations.
- Loading, success, empty, and error states must be handled appropriately.
- Destructive operations such as voiding a transaction or disabling a faculty account require confirmation.
- Server-side validation must be performed before processing submitted data.
- Client-side validation may be used to improve user experience but must not replace server-side validation.
- Transactions must record the unit price at the time of sale rather than relying on the current product price, so historical totals remain accurate even after price changes.
- Credit transactions must not be recorded if they would exceed a faculty member's credit limit, except through an explicit, audited override.
- Payroll deduction reports must only be released to Payroll/Finance after administrator approval.
- System errors must be handled without exposing sensitive technical information to users.

## 6. Acceptance Criteria

The application is considered functionally complete when:

- [ ] All required FACU-CRED features are implemented and functional.
- [ ] All defined user roles work correctly.
- [ ] Faculty account CRUD operations work correctly, including credit limit management.
- [ ] Product/menu CRUD operations work correctly.
- [ ] Authentication and server-side authorization restrictions work correctly.
- [ ] Credit transactions are recorded correctly, including line items and unit price at time of sale.
- [ ] Credit limit enforcement works correctly, including authorized overrides.
- [ ] Monthly totals are computed correctly from recorded transactions.
- [ ] Payroll deduction reports are generated, reviewed, and approved correctly.
- [ ] Approved deduction reports are visible to Payroll/Finance and can be marked processed.
- [ ] No transaction is included in more than one deduction report.
- [ ] Required input validation works correctly on the server.
- [ ] Invalid transactions, login attempts, and form submissions are handled appropriately.
- [ ] Loading, success, empty, and error states are properly handled.
- [ ] Destructive operations require confirmation before execution.
- [ ] Normal system operations work without unnecessary full-page browser reloads.
- [ ] Unauthorized users cannot access restricted features or resources.
- [ ] All defined functional tests pass successfully.
