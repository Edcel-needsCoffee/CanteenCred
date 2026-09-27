# 03 — Technology Stack

## Frontend

Framework:

Laravel Blade

Language:

JavaScript

Build Tool:

Vite

UI Framework:

Bootstrap

State Management:

Native JavaScript state where required

Routing:

Laravel Routing

---

## Backend

Language:

PHP

Framework:

Laravel

ORM:

Eloquent ORM

Validation:

Laravel Validation

---

## Database

Database:

MySQL

ORM:

Eloquent ORM

Migration System:

Laravel Migrations

Purpose:

The FACU-CRED database stores all operational data for the canteen credit and payroll deduction workflow: faculty accounts, credit limits, products, transactions and their line items, payroll periods, deduction reports, users/roles, and audit logs.

FACU-CRED is the authoritative source for canteen credit data. It does not depend on any external HR/faculty-information system in the current version; faculty accounts are registered directly by the Canteen Administrator.

---

## Point of Sale Input

Technology:

Standard web form input (faculty search by ID/name, product selection)

Purpose:

Canteen Staff identify the purchasing faculty member by searching their registered Faculty ID or name at the point-of-sale screen, then add line items from the product/menu list.

RFID or other tap-based identification is not part of the current version. Should tap-based identification be introduced later, it can reuse the same faculty-lookup service that the search-based flow already depends on.

---

## Real-Time

The current version does not use WebSocket or any other real-time transport. All screens (point of sale, faculty portal, admin dashboards, payroll views) are updated through standard REST requests and page-local UI updates after a successful response.

This keeps the initial implementation simple. A real-time layer (and, if introduced, a pub/sub transport such as Redis) should only be added later if a demonstrated requirement — for example, multiple simultaneous cashier terminals needing a live shared transaction feed — justifies it.

---

## Cache / Queue

Cache:

None initially

Queue:

None initially

Workers:

None initially

Reason:

The initial FACU-CRED implementation shall avoid unnecessary infrastructure. Queues, background workers, and caching may be introduced in a future version if a demonstrated requirement justifies them (for example, generating very large deduction exports asynchronously).

---

## Authentication

Authentication:

Laravel Session-based Authentication

Authorization:

Role-Based Access Control (RBAC)

Roles:

- Canteen Administrator
- Canteen Staff / Cashier
- Payroll / Finance Officer
- Faculty

---

## Reporting / Export

Technology:

Laravel-generated CSV/PDF export for approved payroll deduction reports

Purpose:

Once a payroll deduction report is approved, the Payroll/Finance Officer shall be able to export it in a format suitable for the payroll run (CSV at minimum; PDF optional for a printable summary).

---

## File Storage

Development:

Local Storage

Production:

Local or institution-approved storage

Purpose:

File storage may be used for exported deduction reports and any product images, if used.

---

## Testing

Unit:

PHPUnit

Integration:

Laravel Testing Tools

API:

Laravel HTTP Client Testing / Postman

E2E:

Manual Browser Testing initially

Testing shall cover:

- Authentication
- Authorization
- Faculty account management
- Credit limit enforcement
- Transaction recording and correction
- Payroll period and deduction report generation
- Deduction approval workflow
- Audit logging

---

## Deployment

Runtime:

PHP

Web Server:

Apache

Reverse Proxy:

None initially

Development Environment:

Windows + XAMPP (or `php artisan serve` + `npm run build` for assets)

Production Environment:

Linux-based PHP hosting or institution-approved server

---

## Development Tools

Code Editor:

Visual Studio Code

Version Control:

Git + GitHub

Database Management:

MySQL (e.g. via phpMyAdmin or a GUI client)

API Testing:

Postman

---

## Technology Stack Summary

| Layer | Technology |
|---|---|
| Frontend | Laravel Blade |
| Styling | Bootstrap |
| Client-side Logic | JavaScript |
| Build Tool | Vite |
| Backend | PHP + Laravel |
| ORM | Eloquent |
| Validation | Laravel Validation |
| Database | MySQL |
| Migration | Laravel Migrations |
| Faculty Lookup | Search-based (ID/name) |
| Real-Time | None initially |
| Authentication | Laravel Session |
| Authorization | RBAC |
| Reporting/Export | CSV/PDF export |
| Cache | None initially |
| Queue | None initially |
| Workers | None initially |
| Testing | PHPUnit + Laravel Testing Tools |
| API Testing | Postman |
| Web Server | Apache |
| Development | Windows + XAMPP / `php artisan serve` |
| Version Control | Git + GitHub |

---

## Development Approach

The technology stack is intentionally kept manageable for the development team's current skill level.

The primary learning areas are:

- Laravel project structure
- Laravel routing
- Controllers
- Blade templates
- Forms and requests
- Laravel validation
- Eloquent ORM
- Database migrations
- Authentication
- Middleware
- Role-based authorization
- JavaScript/AJAX
- Transaction/line-item data modeling
- Payroll-period and report generation logic
- Audit logging

Unnecessary infrastructure such as microservices, message queues, real-time transports, and complex cloud services shall not be introduced unless a demonstrated requirement justifies them.
