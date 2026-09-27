# 06 — UI / UX Specification

## 1. Design System

**Framework:** Bootstrap

**Frontend:** Laravel Blade + JavaScript

### Design Direction

The FACU-CRED interface shall use a clean, simple, transaction-oriented design suitable for cashiers working quickly at the point of sale, administrators reviewing figures, faculty checking their balance, and the payroll office reviewing approved reports.

The interface shall prioritize:

- Fast faculty lookup and item entry at the point of sale
- Clear display of running balance and remaining credit limit
- Readable transaction and deduction tables
- Clear status indicators (draft / approved / processed, active / voided)
- Easy navigation
- Minimal unnecessary interactions
- Responsive layouts

The interface shall use consistent:

- Typography
- Buttons
- Forms
- Tables
- Cards
- Modals
- Alerts
- Status badges
- Spacing
- Navigation components

---

## 2. Layout

### Desktop

The primary layout shall be optimized for desktop computers, since the canteen point-of-sale station, administrator office, and payroll office will primarily use desktop or laptop computers.

```text
+-------------------------------------------------------+
| Header                              User / Logout      |
+------------------+------------------------------------+
|                  |                                     |
|    Sidebar       |          Main Content               |
|                  |                                     |
|    Dashboard     |                                     |
|    Point of Sale |                                     |
|    Faculty       |                                     |
|    Products      |                                     |
|    Transactions  |                                     |
|    Payroll       |                                     |
|    Users         |                                     |
|    Audit Logs    |                                     |
|                  |                                     |
+------------------+------------------------------------+
```

### Tablet

The interface shall:

- Collapse the sidebar when necessary.
- Maintain readable tables.
- Use responsive cards.
- Allow horizontal scrolling for large tables when necessary.
- Maintain accessible touch targets, since the point-of-sale screen may be operated on a tablet at the canteen counter.

### Mobile

Faculty-facing pages should remain fully usable on mobile devices, since faculty are most likely to check their balance from a phone.

The interface shall:

- Use a collapsible navigation menu.
- Stack dashboard cards vertically.
- Use responsive layouts for tables.
- Maintain readable text.
- Provide sufficiently large buttons and controls.

---

## 3. Navigation

### Canteen Administrator Navigation

The Administrator sidebar shall include:

- Dashboard
- Point of Sale
- Faculty Accounts
- Products / Menu
- Transactions
- Transaction Corrections
- Payroll Periods
- Payroll Deductions
- Users
- Audit Logs
- Reports
- Settings

### Canteen Staff / Cashier Navigation

Canteen Staff shall have access to:

- Point of Sale
- Recent Transactions
- Faculty Lookup

Canteen Staff shall not have access to administrative functions such as user management, credit limit changes, or payroll approval.

### Payroll / Finance Navigation

Payroll/Finance shall have access to:

- Dashboard
- Approved Deduction Reports
- Export
- Profile
- Logout

Payroll/Finance shall not see draft or rejected deduction reports.

### Faculty Navigation

Faculty shall have access to:

- My Balance
- My Transactions
- My Deductions
- Profile
- Logout

### Header

The header shall provide:

- Application name/logo
- Current user
- Current role
- Logout option

### User Menu

The user menu shall provide:

- Profile
- Account information
- Logout

### Breadcrumbs

Breadcrumbs may be used on administrative pages where they improve navigation.

Example:

```text
Dashboard > Payroll > Payroll Deduction Report
```

---

## 4. Pages

| Page | Purpose | Access |
|---|---|---|
| Login | Authenticate authorized users | Public |
| Administrator Dashboard | Display system overview (recent transactions, open period totals) | Administrator |
| Point of Sale | Record credit transactions | Canteen Staff / Administrator |
| Faculty Accounts | Register and manage faculty accounts and credit limits | Administrator |
| Products / Menu | Manage the product/menu list | Administrator |
| Transactions | View and manage transaction records | Administrator / Canteen Staff |
| Transaction Corrections | Submit and process transaction corrections | Authorized |
| Payroll Periods | Manage payroll periods, open/close | Administrator |
| Payroll Deductions | Review, approve, or reject draft deduction reports | Administrator |
| Approved Deductions | View and export approved deduction reports | Payroll / Finance Officer |
| Users | Manage system users | Administrator |
| Audit Logs | Review system activity | Administrator |
| Reports | Generate credit and payroll reports | Administrator |
| Settings | Manage system configuration | Administrator |
| My Balance | View current balance and credit limit | Faculty |
| My Transactions | View own transaction history | Faculty |
| My Deductions | View own deduction status by period | Faculty |

---

## 5. Point-of-Sale Interface

The point-of-sale interface shall prioritize fast and clear faculty identification and item entry.

### Transaction Flow

```text
Canteen Staff
    |
    v
Search Faculty (ID / Name)
    |
    v
Select Faculty Account
    |
    v
Add Products (Product, Quantity)
    |
    v
Review Line Items and Running Total
    |
    v
Confirm Credit Transaction
    |
    v
Update Faculty Running Balance
```

When a faculty account is selected, the interface should display:

- Faculty number
- Name
- Department
- Current credit balance
- Credit limit
- Remaining credit

### Example screen (wireframe)

```text
+--------------------------------------------------+
| Point of Sale                                     |
+--------------------------------------------------+
| Search Faculty: [ Juan Dela Cruz          ] [Go]  |
+--------------------------------------------------+
| Faculty: Juan Dela Cruz (2026-00125)              |
| Department: College of IT                         |
| Credit Limit: P2,000.00   Remaining: P1,350.00     |
+--------------------------------------------------+
| Product          | Qty | Price  | Subtotal        |
|-------------------------------------------------- |
| Chicken Meal      |  1  | P65.00 | P65.00          |
| Iced Coffee       |  1  | P30.00 | P30.00          |
+--------------------------------------------------+
|                                   TOTAL: P95.00    |
+--------------------------------------------------+
|            [ Add Item ]     [ Confirm Credit ]     |
+--------------------------------------------------+
```

The interface shall provide clear feedback for:

- Faculty not found
- Disabled faculty account
- Product unavailable
- Transaction would exceed credit limit (with the option, for authorized users, to override with a reason)
- Server/validation error

A transaction shall not be considered recorded until the backend confirms it; the interface must not optimistically assume success before receiving a response.

---

## 6. Faculty Self-Service Portal

The Faculty portal shall present, in order of importance:

1. Current running balance and remaining credit limit.
2. Current payroll period's total so far.
3. Itemized transaction history, most recent first.
4. Deduction status for past periods (pending / approved / deducted).

### Example screen (wireframe)

```text
+--------------------------------------------------+
| My Canteen Credit                                  |
+--------------------------------------------------+
| Credit Limit: P2,000.00   Remaining: P1,350.00     |
| This Period's Total (Sept 2026): P650.00           |
+--------------------------------------------------+
| Date       | Items                  | Total        |
|-------------------------------------------------- |
| Sept 26    | Chicken Meal, Coffee   | P95.00        |
| Sept 24    | Rice Meal              | P85.00        |
+--------------------------------------------------+
```

---

## 7. Payroll Deduction Review Interface

The Administrator's deduction review screen shall let the reviewer drill down from a per-faculty total into the underlying transactions before approving.

```text
+--------------------------------------------------+
| Payroll Period: September 2026        [ Approve ] |
+--------------------------------------------------+
| Faculty            | Transactions | Total         |
|-------------------------------------------------- |
| Juan Dela Cruz      |     18        | P1,250.00     |
| Maria Santos        |     12        | P875.00       |
| Pedro Garcia        |     25        | P1,540.00     |
+--------------------------------------------------+
|                                TOTAL: P3,665.00    |
+--------------------------------------------------+
```

Selecting a row shall expand or navigate to that faculty member's individual transactions for the period.

---

## 8. States

Every page that loads or submits data shall handle:

- Loading state (spinner or skeleton while a request is in flight)
- Success state (confirmation message, updated UI)
- Empty state (e.g. "No transactions recorded for this period yet")
- Error state (clear message, retry option where applicable, no technical detail exposed)

Destructive actions (voiding a transaction, disabling a faculty account, rejecting a deduction) require a confirmation dialog before execution.

---

## 9. Forms

Forms (faculty registration, product management, transaction correction, credit limit changes) shall:

- Validate required fields client-side for immediate feedback.
- Always be re-validated server-side.
- Preserve entered values on validation failure.
- Clearly mark required fields.
- Disable the submit button while a request is in flight to prevent duplicate submissions — this is especially important for transaction creation, to avoid double-charging a faculty member for the same order.

---

## 10. Accessibility

The interface shall:

- Use sufficient color contrast, particularly for status badges (draft/approved/processed, active/voided).
- Use semantic HTML elements and labels for form fields.
- Support keyboard navigation for common actions.
- Avoid relying on color alone to convey status (pair color with text/icon).
