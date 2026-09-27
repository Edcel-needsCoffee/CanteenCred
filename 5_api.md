# 05 — API Specification

## 1. Base URL

Development:

````text
http://localhost:8000/api/v1
````

Production:

````text
https://[FACU-CRED-DOMAIN]/api/v1
````

The API shall use versioning to allow future changes without breaking existing clients.

---

## 2. Authentication

Authentication Mechanism:

Laravel Session-based Authentication

Authorization:

Role-Based Access Control (RBAC)

Authentication shall be required for protected API endpoints.

The server shall verify the authenticated user's role before allowing access to restricted resources.

### Login

````text
POST /api/v1/auth/login
````

### Logout

````text
POST /api/v1/auth/logout
````

### Session Behavior

* Sessions shall be managed by Laravel.
* Sessions shall expire according to the configured application timeout.
* Users shall be required to authenticate again after session expiration.
* Logout shall invalidate the current session.
* CSRF protection shall be applied to browser-based state-changing requests.

---

## 3. API Conventions

### Request

JSON shall be used for API requests unless otherwise specified.

````json
{
  "username": "cashier1",
  "password": "password"
}
````

### Response

Successful response:

````json
{
  "data": {},
  "message": "Operation successful"
}
````

Collection response:

````json
{
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 25,
    "total": 100
  }
}
````

### Errors

````json
{
  "error": {
    "code": "ERROR_CODE",
    "message": "Human-readable message",
    "fields": {}
  }
}
````

The API shall not expose sensitive technical information such as:

* Database credentials
* Internal file paths
* SQL queries
* Stack traces
* Server configuration
* Authentication secrets

---

## 4. Authentication Endpoints

| Method | Endpoint | Purpose | Auth |
|---|---|---|---|
| POST | `/auth/login` | Authenticate user | Public |
| POST | `/auth/logout` | End current session | Required |
| GET | `/auth/user` | Get authenticated user information | Required |

---

## 5. User Endpoints

| Method | Endpoint | Purpose | Auth |
|---|---|---|---|
| GET | `/users` | List users | Administrator |
| GET | `/users/{id}` | Get user | Administrator |
| POST | `/users` | Create user | Administrator |
| PUT/PATCH | `/users/{id}` | Update user | Administrator |
| DELETE | `/users/{id}` | Disable/remove user | Administrator |

---

## 6. Faculty Endpoints

| Method | Endpoint | Purpose | Auth |
|---|---|---|---|
| GET | `/faculty` | List faculty accounts | Administrator / Cashier |
| GET | `/faculty/{id}` | Get faculty account | Administrator / Cashier / Own account (Faculty) |
| POST | `/faculty` | Register faculty account | Administrator |
| PUT/PATCH | `/faculty/{id}` | Update faculty account, including credit limit | Administrator |
| DELETE | `/faculty/{id}` | Disable faculty account | Administrator |
| GET | `/faculty/{id}/balance` | Get current running balance and remaining credit limit | Administrator / Cashier / Own account (Faculty) |
| GET | `/faculty/search?q=` | Search faculty by ID/name for point-of-sale lookup | Cashier / Administrator |

---

## 7. Product Endpoints

| Method | Endpoint | Purpose | Auth |
|---|---|---|---|
| GET | `/products` | List products/menu items | Cashier / Administrator |
| GET | `/products/{id}` | Get product | Cashier / Administrator |
| POST | `/products` | Create product | Administrator |
| PUT/PATCH | `/products/{id}` | Update product (name, price, status) | Administrator |
| DELETE | `/products/{id}` | Disable product | Administrator |

Changing a product's price does not retroactively change `unit_price` on any existing `transaction_items`.

---

## 8. Transaction Endpoints

| Method | Endpoint | Purpose | Auth |
|---|---|---|---|
| GET | `/transactions` | List transactions | Authorized |
| GET | `/transactions/{id}` | Get transaction with line items | Authorized |
| POST | `/transactions` | Create a credit transaction | Cashier / Administrator |
| PATCH | `/transactions/{id}/void` | Void a transaction (requires reason) | Administrator |
| GET | `/faculty/{faculty_id}/transactions` | Get a faculty member's transaction history | Authorized |

Example transaction creation request:

````json
{
  "faculty_id": 12,
  "items": [
    { "product_id": 3, "quantity": 1 },
    { "product_id": 7, "quantity": 2 }
  ],
  "override_credit_limit": false
}
````

Example response when the transaction would exceed the credit limit:

````json
{
  "error": {
    "code": "CREDIT_LIMIT_EXCEEDED",
    "message": "This transaction exceeds the faculty member's remaining credit limit.",
    "fields": {
      "remaining_credit": "45.00",
      "attempted_total": "95.00"
    }
  }
}
````

Credit transactions shall only be created through:

* Canteen Staff recording a sale at the point of sale.
* An authorized Administrator entering a transaction directly (e.g. correcting a missed entry).

---

## 9. Transaction Correction Endpoints

| Method | Endpoint | Purpose | Auth |
|---|---|---|---|
| GET | `/transaction-corrections` | List correction requests | Administrator |
| GET | `/transaction-corrections/{id}` | View correction request | Authorized |
| POST | `/transaction-corrections` | Submit correction request | Cashier / Administrator |
| PATCH | `/transaction-corrections/{id}/approve` | Approve correction | Administrator |
| PATCH | `/transaction-corrections/{id}/reject` | Reject correction | Administrator |

Every correction shall require:

* Original transaction information
* Corrected information
* Reason
* Requesting user
* Approving user when applicable
* Date and time
* Audit record

---

## 10. Payroll Period Endpoints

| Method | Endpoint | Purpose | Auth |
|---|---|---|---|
| GET | `/payroll-periods` | List payroll periods | Administrator / Payroll |
| GET | `/payroll-periods/{id}` | Get payroll period | Administrator / Payroll |
| POST | `/payroll-periods` | Create a payroll period | Administrator |
| PATCH | `/payroll-periods/{id}/close` | Close a period to new transactions | Administrator |

---

## 11. Payroll Deduction Endpoints

| Method | Endpoint | Purpose | Auth |
|---|---|---|---|
| GET | `/payroll-periods/{id}/deductions` | List draft/approved deductions for a period | Administrator / Payroll (approved only) |
| GET | `/payroll-deductions/{id}` | Get a deduction record, with its underlying transactions | Administrator / Payroll (if approved) |
| POST | `/payroll-periods/{id}/generate-deductions` | Generate draft deduction report from the period's transactions | Administrator |
| PATCH | `/payroll-deductions/{id}/approve` | Approve a deduction record | Administrator |
| PATCH | `/payroll-deductions/{id}/reject` | Reject/hold a deduction record | Administrator |
| PATCH | `/payroll-deductions/{id}/processed` | Mark an approved deduction as processed | Payroll / Finance Officer |
| GET | `/payroll-periods/{id}/export` | Export approved deductions (CSV/PDF) | Payroll / Finance Officer |

Payroll/Finance shall only be able to view and export deductions with status `approved` or `processed`; `draft` and `rejected` deductions are visible to the Administrator only.

---

## 12. Faculty Self-Service Endpoints

| Method | Endpoint | Purpose | Auth |
|---|---|---|---|
| GET | `/faculty/me` | View own faculty profile | Faculty |
| GET | `/faculty/me/balance` | View own current balance and remaining credit | Faculty |
| GET | `/faculty/me/transactions` | View own transaction history | Faculty |
| GET | `/faculty/me/deductions` | View own deduction status by period | Faculty |

Faculty shall only be able to access records belonging to their own linked account.

---

## 13. Audit Log Endpoints

| Method | Endpoint | Purpose | Auth |
|---|---|---|---|
| GET | `/audit-logs` | View audit records | Administrator |
| GET | `/audit-logs/{id}` | View audit record | Administrator |

Audit logs shall record important administrative and financial operations, including:

* Login attempts
* User creation/modification
* Faculty account changes, especially credit limit changes
* Product/price changes
* Transaction creation, voiding, and correction
* Credit limit overrides
* Payroll deduction generation, approval, rejection, and processing

Audit logs shall not be editable through normal API operations.

---

## 14. Pagination

List endpoints shall support pagination where appropriate.

````text
?page=1&limit=25
````

Default:

````text
page = 1
limit = 25
````

Maximum limit:

````text
100
````

Pagination shall be applied to potentially large datasets such as transactions, audit logs, users, and faculty.

---

## 15. Filtering

### Transactions

````text
?faculty_id=12
?date_from=2026-09-01&date_to=2026-09-30
?status=active
````

### Faculty

````text
?department=College of IT
?is_active=true
````

### Payroll Deductions

````text
?payroll_period_id=4
?status=approved
````

The API shall only support filters that are implemented and validated by the backend.

---

## 16. Sorting

````text
?sort=transaction_date
?order=desc
````

Common sortable fields include `created_at`, `updated_at`, `transaction_date`, `name`, `total_amount`, and `status`. The backend shall validate sorting fields to prevent unauthorized database query manipulation.

---

## 17. Rate Limiting

Rate limiting shall be applied to sensitive endpoints, including login, transaction creation, and deduction approval. Excessive requests shall return:

````text
429 Too Many Requests
````

---

## 18. HTTP Status Codes

| Status | Meaning |
|---|---|
| 200 | Success |
| 201 | Resource Created |
| 204 | Success with No Content |
| 400 | Bad Request |
| 401 | Unauthenticated |
| 403 | Forbidden |
| 404 | Resource Not Found |
| 409 | Conflict |
| 422 | Validation Error |
| 429 | Rate Limited |
| 500 | Internal Server Error |
| 503 | Service Unavailable |

---

## 19. Point-of-Sale Transaction Processing

The normal credit transaction flow shall be:

````text
Canteen Staff
    |
    v
Search Faculty
    |
    v
Add Line Items
    |
    v
Campass FACU-CRED Transaction Endpoint
    |
    v
Validate Faculty / Credit Limit
    |
    v
Create Transaction + Line Items
    |
    v
Update Faculty Running Balance
    |
    v
Return Confirmation / Receipt Summary
````

---

## 20. Payroll Deduction Generation

````text
Payroll Period Closed
        |
        v
Generate Draft Deductions Endpoint
        |
        v
Group Transactions by Faculty
        |
        v
Compute Per-Faculty Totals
        |
        v
Create Draft payroll_deductions Records
        |
        v
Administrator Review
        |
        v
Approve / Reject
        |
        v
Visible to Payroll/Finance
````

---

## 21. API Security

The API shall:

* Require authentication for protected resources.
* Enforce authorization server-side.
* Validate all incoming data.
* Apply rate limiting where necessary.
* Use HTTPS in production.
* Protect against CSRF for browser-based state-changing requests.
* Avoid exposing sensitive internal information.
* Restrict Faculty access to their own account only.
* Restrict Payroll/Finance access to approved/processed deductions only.
* Record security- and finance-sensitive operations in audit logs.

---

## 22. API Versioning

The initial API version shall be:

````text
/api/v1
````

Breaking changes shall require a new API version. Existing clients should continue to function while an older API version remains supported.
