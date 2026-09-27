<?php

namespace App\Enums;

enum AuditAction: string
{
    // Auth
    case LOGIN_ATTEMPT = 'login.attempt';
    case LOGIN_SUCCESS = 'login.success';
    case LOGIN_FAILED = 'login.failed';
    case LOGOUT = 'logout';
    case SESSION_EXPIRED = 'session.expired';

    // Users
    case USER_CREATED = 'user.created';
    case USER_UPDATED = 'user.updated';
    case USER_DELETED = 'user.deleted';
    case USER_ROLE_CHANGED = 'user.role_changed';

    // Faculty
    case FACULTY_CREATED = 'faculty.created';
    case FACULTY_UPDATED = 'faculty.updated';
    case FACULTY_DELETED = 'faculty.deleted';
    case FACULTY_CREDIT_LIMIT_CHANGED = 'faculty.credit_limit_changed';
    case FACULTY_STATUS_CHANGED = 'faculty.status_changed';

    // Products
    case PRODUCT_CREATED = 'product.created';
    case PRODUCT_UPDATED = 'product.updated';
    case PRODUCT_DELETED = 'product.deleted';
    case PRODUCT_PRICE_CHANGED = 'product.price_changed';
    case PRODUCT_STATUS_CHANGED = 'product.status_changed';

    // Transactions
    case TRANSACTION_CREATED = 'transaction.created';
    case TRANSACTION_VOIDED = 'transaction.voided';
    case TRANSACTION_CORRECTED = 'transaction.corrected';
    case TRANSACTION_CREDIT_LIMIT_OVERRIDE = 'transaction.credit_limit_override';

    // Corrections
    case CORRECTION_REQUESTED = 'correction.requested';
    case CORRECTION_APPROVED = 'correction.approved';
    case CORRECTION_REJECTED = 'correction.rejected';

    // Payroll
    case PAYROLL_PERIOD_CREATED = 'payroll_period.created';
    case PAYROLL_PERIOD_CLOSED = 'payroll_period.closed';
    case PAYROLL_PERIOD_FINALIZED = 'payroll_period.finalized';
    case DEDUCTION_GENERATED = 'deduction.generated';
    case DEDUCTION_APPROVED = 'deduction.approved';
    case DEDUCTION_REJECTED = 'deduction.rejected';
    case DEDUCTION_PROCESSED = 'deduction.processed';
    case DEDUCTION_EXPORTED = 'deduction.exported';

    public function label(): string
    {
        return match ($this) {
            self::LOGIN_ATTEMPT => 'Login Attempt',
            self::LOGIN_SUCCESS => 'Login Success',
            self::LOGIN_FAILED => 'Login Failed',
            self::LOGOUT => 'Logout',
            self::SESSION_EXPIRED => 'Session Expired',
            self::USER_CREATED => 'User Created',
            self::USER_UPDATED => 'User Updated',
            self::USER_DELETED => 'User Deleted',
            self::USER_ROLE_CHANGED => 'User Role Changed',
            self::FACULTY_CREATED => 'Faculty Created',
            self::FACULTY_UPDATED => 'Faculty Updated',
            self::FACULTY_DELETED => 'Faculty Deleted',
            self::FACULTY_CREDIT_LIMIT_CHANGED => 'Faculty Credit Limit Changed',
            self::FACULTY_STATUS_CHANGED => 'Faculty Status Changed',
            self::PRODUCT_CREATED => 'Product Created',
            self::PRODUCT_UPDATED => 'Product Updated',
            self::PRODUCT_DELETED => 'Product Deleted',
            self::PRODUCT_PRICE_CHANGED => 'Product Price Changed',
            self::PRODUCT_STATUS_CHANGED => 'Product Status Changed',
            self::TRANSACTION_CREATED => 'Transaction Created',
            self::TRANSACTION_VOIDED => 'Transaction Voided',
            self::TRANSACTION_CORRECTED => 'Transaction Corrected',
            self::TRANSACTION_CREDIT_LIMIT_OVERRIDE => 'Transaction Credit Limit Override',
            self::CORRECTION_REQUESTED => 'Correction Requested',
            self::CORRECTION_APPROVED => 'Correction Approved',
            self::CORRECTION_REJECTED => 'Correction Rejected',
            self::PAYROLL_PERIOD_CREATED => 'Payroll Period Created',
            self::PAYROLL_PERIOD_CLOSED => 'Payroll Period Closed',
            self::PAYROLL_PERIOD_FINALIZED => 'Payroll Period Finalized',
            self::DEDUCTION_GENERATED => 'Deduction Generated',
            self::DEDUCTION_APPROVED => 'Deduction Approved',
            self::DEDUCTION_REJECTED => 'Deduction Rejected',
            self::DEDUCTION_PROCESSED => 'Deduction Processed',
            self::DEDUCTION_EXPORTED => 'Deduction Exported',
        };
    }
}