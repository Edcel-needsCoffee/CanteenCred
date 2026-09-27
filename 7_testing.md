# 07 — Testing Specification

## 1. Unit Tests

Test individual application components and business logic.

Test:

- Authentication and authorization logic
- Credit limit calculation and enforcement
- Transaction total computation (line items → transaction total)
- Faculty running balance calculation
- Payroll deduction generation logic (grouping transactions by faculty/period)
- Transaction correction logic
- Input validation
- Data transformation
- Utility functions
- Audit logging helpers

---

## 2. API Tests

Test the FACU-CRED backend API and its communication with the frontend.

Test:

- Authentication
- Authorization
- Role restrictions
- Faculty data retrieval and management
- Product/menu management
- Transaction creation (including line items)
- Transaction voiding
- Transaction corrections
- Credit limit enforcement (blocked and authorized-override cases)
- Payroll period creation/closing
- Payroll deduction generation
- Payroll deduction approval/rejection
- Payroll deduction export
- Faculty/Payroll self-service access restrictions
- Validation errors
- Invalid requests
- Unauthorized requests
- API error responses
- Pagination where applicable
- Filtering where applicable
- Rate limiting where applicable

---

## 3. Database Tests

Test the FACU-CRED database and data integrity.

Test:

- Database migrations
- Table creation
- Primary keys
- Foreign keys
- Unique constraints (e.g. `faculty_number`, `transaction_number`)
- Required fields
- Relationships (faculty → transactions → transaction_items; payroll_period → payroll_deductions)
- Transactions (atomic creation of a transaction with its line items)
- Prevention of double-linking a transaction to more than one payroll deduction
- Audit logs
- Seed data where applicable

The database must preserve important historical records (original unit prices, original transaction snapshots) and prevent invalid relationships.

---

## 4. Credit Transaction Tests

Test the canteen credit transaction process.

Test:

- Valid faculty account
- Disabled faculty account
- Faculty not found
- Product unavailable
- Successful transaction within credit limit
- Transaction blocked when exceeding credit limit
- Authorized override of credit limit, with reason and audit entry
- Unit price captured correctly at time of sale
- Transaction total computed correctly from line items
- Transaction voiding with reason
- Duplicate submission prevention (no double-charge on repeated clicks)
- Transaction timestamp

Example:

```text
Transaction Attempt
    |
    v
Validate Faculty
    |
    +---- Not Found / Disabled ----> Display Error
    |
    +---- Valid ------> Validate Credit Limit
                            |
                            +---- Exceeds, No Override ----> Reject
                            |
                            +---- Within Limit / Authorized Override ----> Record Transaction
```

---

## 5. Payroll Period and Deduction Tests

Test the payroll deduction workflow end-to-end.

Test:

- Payroll period creation
- Payroll period closing (no new transactions accepted after close)
- Draft deduction generation from a closed period's transactions
- Correct per-faculty total computation
- Administrator approval of a deduction
- Administrator rejection/hold of a deduction
- Payroll/Finance visibility restricted to approved/processed deductions
- Marking a deduction as processed
- Prevention of a transaction being included in more than one deduction report
- Export of approved deductions (CSV/PDF)

Example:

```text
Period Closed
      |
      v
Generate Draft Deductions
      |
      v
Administrator Review
      |
      v
Approve
      |
      v
Visible to Payroll/Finance
      |
      v
Mark Processed
```

---

## 6. UI Tests

Test important frontend behavior.

Test:

- Login
- Navigation
- Dashboard
- Point-of-sale faculty search and item entry
- Transaction confirmation flow
- Faculty account forms
- Product/menu forms
- CRUD operations
- Validation
- Search
- Filtering
- Pagination where applicable
- Loading states
- Success states
- Empty states
- Error states
- Confirmation dialogs
- Responsive layouts
- No unnecessary page refreshes

---

## 7. Authentication and Authorization Tests

Test that users can only access functions permitted by their roles.

Test:

- Valid login
- Invalid login
- Logout
- Session expiration
- Unauthorized access
- Administrator permissions
- Canteen Staff / Cashier permissions
- Payroll / Finance permissions
- Faculty permissions
- Restricted API endpoints
- Restricted pages
- Restricted records

Example:

```text
Administrator
    |
    +--> Full Administrative Access

Canteen Staff
    |
    +--> Point of Sale and Transaction Functions

Payroll / Finance
    |
    +--> Approved Deduction Reports Only

Faculty
    |
    +--> Personal Records Only
```

Authorization must be enforced on the backend.

---

## 8. Faculty Self-Service Tests

Test faculty access to their own information.

Test:

- Faculty login
- Viewing own balance
- Viewing own transaction history
- Viewing own deduction status
- Unauthorized access to another faculty member's records prevented

A faculty member must not be able to view records belonging to another faculty member.

---

## 9. Audit and Security Tests

Test that important system activities are properly recorded.

Test:

- Login attempts
- Successful logins
- Failed logins
- Transaction creation, voiding, correction
- Credit limit overrides
- Credit limit changes
- Product/price changes
- Payroll deduction generation, approval, rejection, processing
- User changes
- Role changes
- Unauthorized access attempts

Audit records should not be modifiable by ordinary users.

---

## 10. End-to-End Tests

Test complete FACU-CRED workflows from the user's perspective.

### Point-of-Sale Credit Transaction Workflow

```text
Login
   |
   v
Open Point of Sale
   |
   v
Search Faculty
   |
   v
Add Items
   |
   v
Confirm Transaction
   |
   v
Verify Faculty Balance Updated
```

### Payroll Deduction Workflow

```text
Administrator Login
   |
   v
Close Payroll Period
   |
   v
Generate Draft Deductions
   |
   v
Review Per-Faculty Totals
   |
   v
Approve Deduction Report
   |
   v
Payroll/Finance Views and Exports Report
   |
   v
Mark Processed
```

### Faculty Monitoring Workflow

```text
Faculty Login
   |
   v
View Balance
   |
   v
View Transaction History
   |
   v
View Deduction Status
```

### Transaction Correction Workflow

```text
Canteen Staff Notices Error
   |
   v
Submit Correction Request
   |
   v
Administrator Authorization
   |
   v
Modification Applied
   |
   v
Verify Original and Corrected Records Preserved
   |
   v
Verify Audit Log Entry
```

---

## 11. Error and Recovery Tests

Test how the application behaves when errors occur.

Test:

- Database unavailable
- Network failure
- Invalid credentials
- Invalid form data
- Duplicate transaction submission
- Credit limit exceeded
- Attempt to double-generate deductions for an already-processed period
- Server error
- Session expiration

The application shall display a useful error message without exposing sensitive technical information. Where appropriate, users shall be provided with a retry option.

---

## 12. Acceptance Criteria

The application must not be considered complete until:

- [ ] All critical functional requirements are implemented.
- [ ] Authentication works correctly.
- [ ] Authorization restrictions work correctly.
- [ ] Faculty account and product/menu management work correctly.
- [ ] Credit transaction recording works correctly.
- [ ] Credit limit enforcement, including authorized overrides, works correctly.
- [ ] Transaction correction workflow works correctly.
- [ ] Payroll period and deduction generation work correctly.
- [ ] Payroll deduction approval workflow works correctly.
- [ ] No transaction is counted in more than one deduction report.
- [ ] Payroll/Finance access is correctly restricted to approved/processed deductions.
- [ ] Faculty self-service access restrictions work correctly.
- [ ] Audit logging works correctly.
- [ ] Validation works correctly.
- [ ] Error states are handled correctly.
- [ ] Normal CRUD operations do not require unnecessary page reloads.
- [ ] Critical end-to-end workflows pass.
- [ ] No known critical errors remain.
- [ ] The production build completes successfully.
