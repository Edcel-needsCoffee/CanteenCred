# Application Architecture

## 1. Architecture Style

The FACU-CRED application shall use a modular client-server architecture.

````text
Browser
   |
   | HTTP / REST API
   v
Backend Application
   |
   +---- Authentication & Authorization
   |
   +---- Services / Business Logic
   |
   +---- Database (MySQL)
````

The architecture separates the user interface, backend logic, and persistent data storage to improve maintainability, security, and organization.

## 2. Frontend

The frontend is responsible for:

* User interface and presentation.
* User interaction.
* Form handling.
* Client-side validation for user experience.
* Sending requests to the backend.
* Displaying API responses.
* Updating UI state after successful operations.
* Displaying loading, success, empty, and error states.

The frontend must not:

* Access the database directly.
* Contain database credentials.
* Bypass backend authorization.
* Be the authoritative source of security or business rules.
* Compute deduction totals on its own — totals shown to the user must come from the backend.

## 3. Backend

The backend is responsible for:

* Authentication.
* Authorization.
* Server-side validation.
* Business logic.
* Database operations.
* API endpoints.
* Transaction processing (credit availments).
* Credit limit enforcement.
* Faculty account management.
* Product/menu management.
* Payroll period and deduction report generation.
* Error handling and logging.

Business logic should be separated from route handling where practical to keep the application organized and maintainable. In particular, credit-balance computation and payroll-report generation should live in dedicated service classes rather than in controllers, since both are used from more than one entry point (point-of-sale, admin review, faculty portal).

## 4. Database

MySQL shall be used as the database for the current version of FACU-CRED.

The database is the authoritative persistent storage for critical application data, including:

* Faculty accounts and credit limits.
* User accounts and roles.
* Products/menu items.
* Transactions and transaction line items.
* Payroll periods and deduction reports.
* Audit logs.

Critical application data must not depend solely on:

* Browser storage.
* Temporary client-side state.
* Cached data.

## 5. REST API

REST API communication shall be used for all application operations, including:

* Authentication.
* Faculty CRUD operations.
* Product/menu CRUD operations.
* User management.
* Transaction recording and retrieval.
* Payroll period and deduction report operations.
* Data retrieval.
* Data modification.

REST shall be the primary — and, in the current version, the only — communication method for application operations. The current version does not require real-time (WebSocket) communication; there is no equivalent of live terminal/gate monitoring in this system. Should a future requirement emerge (for example, a live "recent transactions" feed shared across multiple cashier stations), it should be evaluated on its own merits before any real-time transport is introduced, following the same justification approach as section 10 below.

## 6. Credit Transaction and Deduction Flow

A credit transaction is the primary unit of business logic in FACU-CRED.

````text
Canteen Staff
     |
     v
Point of Sale (Credit Mode)
     |
     +---- Search Faculty
     |
     +---- Add Line Items (Product, Qty)
     |
     v
Backend
     |
     +---- Validate Faculty / Credit Limit
     |
     v
Transaction Record (with Line Items)
     |
     v
Faculty Running Balance Updated
````

If a transaction would exceed the faculty member's remaining credit limit, the backend shall reject it unless an authorized override is explicitly requested and recorded.

````text
Credit Transaction Attempt
    |
    +---- Within Limit ---> Record Transaction
    |
    +---- Over Limit
                |
                +---- No Override ----> Reject, Display Error
                |
                +---- Authorized Override ----> Record Transaction + Audit Entry
````

At the end of a payroll period, transactions roll up into a deduction report:

````text
Payroll Period Closed
        |
        v
Draft Deduction Report Generated (per faculty totals)
        |
        v
Administrator Review
        |
        v
Approval
        |
        v
Visible to Payroll/Finance
        |
        v
Marked Processed
````

Once a transaction has been included in an approved deduction report, it must be locked from further correction; any dispute after approval must be handled through a separate adjustment recorded against a later period, never by editing an already-approved period's figures.

## 7. Manual/Administrative Correction

Corrections to already-recorded transactions must still be processed through the backend and must follow the same authorization and validation requirements as normal transactions.

````text
Correction Request
        |
        v
Authorization
        |
        v
Modification (Original Preserved)
        |
        v
Reason Recorded
        |
        v
Audit Log
````

## 8. Frontend Interaction

Normal application operations should use asynchronous requests where practical.

````text
User Action
    ↓
Frontend
    ↓
Async API Request
    ↓
Backend
    ↓
Database
    ↓
Response
    ↓
Update UI
````

Normal CRUD operations should not require unnecessary full-page browser reloads.

Avoid using:

````javascript
window.location.reload()
````

as a shortcut for refreshing application data.

Instead, the frontend should update the affected UI after receiving a successful response from the backend — for example, updating a faculty's displayed running balance immediately after a transaction is confirmed at the point of sale.

## 9. Error Handling

Errors must be handled appropriately at each application layer.

### Frontend

The frontend should:

* Display useful error messages, including a clear message when a transaction is blocked by a credit limit.
* Display appropriate loading and empty states.
* Preserve user input where appropriate (e.g. a partially built transaction's line items).
* Allow the user to retry failed operations when applicable.
* Avoid exposing technical or sensitive information.

### Backend

The backend should:

* Validate incoming requests.
* Return consistent error responses.
* Handle authentication and authorization failures.
* Log useful diagnostic information.
* Avoid exposing sensitive internal information.

### Database

The application should:

* Handle database constraint failures safely.
* Handle failed database operations, particularly during multi-row transaction inserts (a transaction and its line items must be saved atomically).
* Validate data before insertion or modification.
* Prevent invalid records from being stored.

## 10. Scalability and Infrastructure

The current version of FACU-CRED shall prioritize simplicity and maintainability.

The system should be designed so that additional infrastructure can be introduced later if required.

The current implementation does not require:

* Background workers.
* Message queues.
* External caching systems.
* Microservices.
* Redis or any other pub/sub transport.
* WebSocket/real-time communication.

Additional infrastructure should only be introduced when a demonstrated system requirement justifies it — for example, if a future requirement calls for a live shared transaction feed across multiple simultaneous cashier terminals, a real-time transport could be added and justified at that time, following the same reasoning process used to justify any infrastructure addition.

The database shall remain the authoritative source of persistent application data.
