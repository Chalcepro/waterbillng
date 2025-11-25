# Project Summary: WaterBill NG

## Root Files
- `config.php`: Global configuration, DB credentials, session setup, constants.
- `index.php`: Entry point. Redirects users to login or dashboard based on session/role.
- `Job_Setup.txt`: (Purpose unknown, likely project notes or setup instructions.)

## Folders & Key Files

### assets/
- `css/`: Stylesheets for main site, admin, and authentication pages.
- `js/`: JavaScript for app logic, payment, and pump status.
- `images/`: Logos and UI images (used in UI, e.g., login/register pages).

### includes/
- `auth_check.php`: Session/role verification for protected pages.
- `db_connect.php`: PDO connection to MySQL using config.php.
- `footer.php` & `header.php`: Shared HTML layout for all pages.
- `functions.php`: Helper functions (settings, notifications, etc).
- `notification_helper.php`: Sends notifications (SMS/WhatsApp) and reminders.

### auth/
- `login.php`: User login form and logic (mobile-first UI).
- `register.php`: User registration form and logic (mobile-first UI).
- `logout.php`: Destroys session, logs user out.
- `forgot-password.php`, `reset-password.php`: Password recovery (not detailed above).

### user/
- `dashboard.php`: User dashboard, subscription/payment status, etc.
- `payment.php`: Make payments, view payment options.
- `history.php`: Payment history table.
- `profile.php`: User profile management.
- `report-fault.php`: Report faults/issues to admin.
- `upload_receipt.php`: Upload payment receipts.

### admin/
- `dashboard.php`: Admin dashboard (stats, recent payments, system settings, pump status, quick actions).
- `payments.php`: Approve/reject user payments.
- `users.php`: Manage user accounts (activate/suspend/delete).
- `settings.php`: Edit system settings (min payment, company info, etc).
- `pump-status.php`: View/update pump health status.

### api/
- `paystack.php`: Payment gateway integration (Paystack).
- `ocr.php`: Receipt image processing (OCR).

### cron/
- `subscription-reminder.php`: Scheduled script for sending subscription reminders.

### lib/
- `paystack-php/`, `tesseract/`: Third-party libraries for payment and OCR (not user-authored).

### system_design/
- UI/UX design images for reference (not code).

---

**General Notes:**
- All PHP pages use includes for DB and session/auth checks.
- Mobile-first design for auth pages, responsive elsewhere.
- Admin/user separation by role, enforced in code.
- Notification system for reminders and admin alerts.
- Payment and receipt upload integrated with Paystack and OCR.

This summary provides a high-level map of the project for onboarding or analysis by another AI or developer.
